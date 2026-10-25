"""RegressÃµes executÃ¡veis da infra isolada; somente fixtures sintÃ©ticas."""
import base64
import copy
import fnmatch
import importlib.util
import io
import json
import os
from pathlib import Path
import shutil
import subprocess
import tarfile
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[2]
SCRIPT = ROOT / 'scripts/portal-homologacao.py'
BASH = shutil.which('bash') if os.name != 'nt' else 'C:/Program Files/Git/bin/bash.exe'


def infra():
    if not SCRIPT.exists():
        raise AssertionError('Preparador/validador de homologaÃ§Ã£o ainda nÃ£o implementado')
    spec = importlib.util.spec_from_file_location('portal_infra', SCRIPT)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


class EntrypointTest(unittest.TestCase):
    def run_entrypoint(self, mode, failure='0', overrides=None, prepared=True, followup=None):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            binary = root / 'bin'
            binary.mkdir()
            (root / 'public').mkdir()
            for name, body in {
                'php': 'echo "$*" >> "$TRACE"; case "$*" in "artisan migrate "*) exit "$MIGRATE_EXIT";; esac',
                'apache2-foreground': 'echo apache >> "$TRACE"',
                'pg_isready': 'exit 0',
                'timeout': 'exit 0',
                'chown': 'exit 0',
                'chmod': 'exit 0',
                'sleep': 'exit 73',
            }.items():
                file = binary / name
                file.write_text('#!/bin/bash\n' + body + '\n', encoding='utf-8', newline='\n')
                file.chmod(0o755)
            marker = root / 'storage/app/.portal84-migrated-portal84-test1234'
            marker.parent.mkdir(parents=True)
            if prepared:
                marker.write_text('portal84-test1234')
            env = dict(os.environ, PORTAL84_RELEASE='portal84-test1234', APP_KEY='base64:' + base64.b64encode(b'x' * 32).decode(),
                       DB_PASSWORD='synthetic-testing-password-84', APP_ENV='homologacao',
                       DB_HOST='postgres', DB_DATABASE='portal84_lms', DB_USERNAME='portal84_teste',
                       LEGADO_DB_HOST='postgres', LEGADO_DB_DATABASE='portal84_legado',
                       LEGADO_DB_USERNAME='portal84_teste', LEGADO_DB_PASSWORD='synthetic-testing-password-84',
                       APP_DEBUG='false', APP_CONFIG_CACHE='/tmp/portal84-config.php',
                       QUEUE_CONNECTION='database', DB_QUEUE='portal84-homologacao',
                       MAIL_MAILER='array', TELEGRAM_BOT_TOKEN='',
                       APP_ROOT=root.as_posix(), TRACE=(root / 'trace').as_posix(), MIGRATE_EXIT=failure)
            env.update(overrides or {})
            result = subprocess.run([BASH, '-c', 'export PATH="$PWD/bin:$PATH"; exec bash "$1" "$2"',
                                     '--', (ROOT / 'entrypoint.sh').as_posix(), mode],
                                    cwd=root, env=env, capture_output=True, text=True, encoding="utf-8", errors="replace", timeout=10)
            second = None
            if followup:
                second = subprocess.run([BASH, '-c', 'export PATH="$PWD/bin:$PATH"; exec bash "$1" "$2"',
                                         '--', (ROOT / 'entrypoint.sh').as_posix(), followup],
                                        cwd=root, env=env, capture_output=True, text=True,
                                        encoding='utf-8', errors='replace', timeout=10)
            trace = (root / 'trace').read_text() if (root / 'trace').exists() else ''
            return (result, trace, second) if followup else (result, trace)

    def test_migration_failure_aborts_and_never_starts_web(self):
        result, trace = self.run_entrypoint('migrate', '31')
        self.assertEqual(result.returncode, 31, result.stdout + result.stderr)
        self.assertNotIn('apache', trace)

    def test_startup_requires_successful_migration_for_this_release(self):
        for mode in ('web', 'worker', 'scheduler'):
            with self.subTest(mode=mode):
                result, trace = self.run_entrypoint(mode, prepared=False)
                self.assertNotEqual(result.returncode, 0)
                self.assertEqual(trace, '')

    def test_failed_migration_invalidates_previous_success_marker(self):
        result, trace, web = self.run_entrypoint('migrate', '31', followup='web')
        self.assertEqual(result.returncode, 31)
        self.assertNotEqual(web.returncode, 0)
        self.assertNotIn('apache', trace)

    def test_successful_migration_unlocks_web(self):
        result, trace, web = self.run_entrypoint('migrate', prepared=False, followup='web')
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(web.returncode, 0, web.stderr)
        self.assertIn('apache', trace)


    def test_only_explicit_mode_migrates(self):
        for mode in ('web', 'worker', 'scheduler', 'scheduler-list'):
            with self.subTest(mode=mode):
                result, trace = self.run_entrypoint(mode)
                self.assertNotIn('artisan migrate ', trace)
                if mode == 'worker':
                    self.assertIn('queue:work database --queue=portal84-homologacao', trace)
                if mode == 'scheduler':
                    self.assertNotIn('artisan', trace)
                    self.assertEqual(result.returncode, 73)
                if mode == 'scheduler-list':
                    self.assertIn('schedule:list', trace)

    def test_successful_migration_is_one_shot(self):
        result, trace = self.run_entrypoint('migrate')
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn('artisan migrate --force --no-interaction', trace)
        self.assertNotIn('apache', trace)

    def test_missing_secret_refuses_before_database_access(self):
        for key in ('APP_KEY', 'DB_PASSWORD'):
            result, trace = self.run_entrypoint('web', overrides={key: ''})
            self.assertNotEqual(result.returncode, 0)
            self.assertEqual(trace, '')

    def test_homologation_rejects_unsafe_runtime_config(self):
        for key, value in [('DB_HOST', 'production'), ('DB_DATABASE', 'cetsadp_lms'),
                           ('MAIL_MAILER', 'smtp'), ('APP_DEBUG', 'true'),
                           ('LEGADO_DB_DATABASE', 'production'), ('TELEGRAM_BOT_TOKEN', 'external')]:
            with self.subTest(key=key):
                result, trace = self.run_entrypoint('web', overrides={key: value})
                self.assertNotEqual(result.returncode, 0)
                self.assertEqual(trace, '')


class ReleaseTest(unittest.TestCase):
    def test_package_preserves_public_vendor_assets_and_blocks_nested_dependencies(self):
        module = infra()
        assets = {
            'public/vendor/fontawesome/webfonts/fa-solid-900.woff2': b'wOF2\x00\xff\r\n\x80\x00',
            'public/vendor/alpine/alpine.js': b'window.Alpine = {};\r\n',
        }
        blocked = [
            'vendor/autoload.php', 'app/vendor/autoload.php',
            'public/other/vendor/autoload.php', 'public/vendor/pdfjs/vendor/autoload.php',
            'public/vendor/node_modules/dependency/index.js', 'public/vendor/.env.local',
            'public/vendor/database.sql.gz', 'public/vendor/private.key',
            'public/vendor/storage/private.pdf', 'public/vendor/backups/data.tar',
        ]
        with tempfile.TemporaryDirectory() as directory:
            source = Path(directory) / 'source.tar'
            output = Path(directory) / 'portal84-assets.tar.gz'
            with tarfile.open(source, 'w') as tar:
                for name, content in {**assets, **dict.fromkeys(blocked, b'DO_NOT_COPY')}.items():
                    member = tarfile.TarInfo(name)
                    member.size = len(content)
                    tar.addfile(member, io.BytesIO(content))
            result = module.package_archive(source, output, '0123456789abcdef')
            with tarfile.open(output, 'r:gz') as tar:
                self.assertEqual(set(tar.getnames()), {*assets, '.portal84-release.json'})
                for name, content in assets.items():
                    self.assertEqual(tar.extractfile(name).read(), content)
                self.assertEqual(json.load(tar.extractfile('.portal84-release.json'))['files'], len(assets))
            self.assertEqual(result['files'], len(assets))
            extracted = Path(directory) / 'extracted'
            self.assertEqual(module.extract_release(source, extracted), len(assets))
            for name, content in assets.items():
                self.assertEqual((extracted / name).read_bytes(), content)
            for name in blocked:
                self.assertFalse((extracted / name).exists(), name)

    def test_docker_context_keeps_public_vendor_without_php_vendor_or_sensitive_data(self):
        # Avalia as regras em ordem, incluindo diretórios pais e glob **.
        def matches(pattern, parts):
            if not pattern:
                return not parts
            if pattern[0] == '**':
                return matches(pattern[1:], parts) or bool(parts) and matches(pattern, parts[1:])
            return bool(parts) and fnmatch.fnmatchcase(parts[0], pattern[0]) and matches(pattern[1:], parts[1:])

        rules = [line.strip() for line in (ROOT / '.dockerignore').read_text(encoding='utf-8').splitlines()
                 if line.strip() and not line.lstrip().startswith('#')]

        def included(name):
            parts = name.split('/')
            keep = True
            for rule in rules:
                allow = rule.startswith('!')
                pattern = rule.lstrip('!').strip('/').split('/')
                if any(matches(pattern, parts[:length]) for length in range(1, len(parts) + 1)):
                    keep = allow
            return keep

        for name in ['public/vendor/fontawesome/webfonts/fa-solid-900.woff2',
                     'public/vendor/alpine/alpine.js', 'public/vendor/pdfjs/pdf.js']:
            with self.subTest(asset=name):
                self.assertTrue(included(name), name)
        for name in ['vendor/autoload.php', 'app/vendor/autoload.php',
                     'public/other/vendor/autoload.php', 'public/vendor/pdfjs/vendor/autoload.php',
                     'public/vendor/node_modules/dependency/index.js', 'public/vendor/.env.local',
                     'public/vendor/database.sql.gz', 'public/vendor/private.key',
                     'public/vendor/storage/app/private.pdf', 'public/vendor/backups/data.tar',
                     'node_modules/dependency/index.js', 'public/storage/private.pdf']:
            with self.subTest(blocked=name):
                self.assertFalse(included(name), name)

    def test_package_preserves_git_case_and_excludes_data_without_filesystem_roundtrip(self):
        module = infra()
        with tempfile.TemporaryDirectory() as directory:
            source = Path(directory) / 'source.tar'
            output = Path(directory) / 'portal84-code.tar.gz'
            entries = {
                'app/Http/Controllers/API/ApplicationController.php': b'legacy API',
                'app/Http/Controllers/Api/V1/ApplicationDocumentDownloadController.php': b'private download',
                '.env': b'DO_NOT_COPY',
                'database/user.sqlite': b'DO_NOT_COPY',
                'entrypoint.sh': b'#!/bin/bash\r\nexit 0\r\n',
            }
            with tarfile.open(source, 'w') as tar:
                for name, content in entries.items():
                    info = tarfile.TarInfo(name)
                    info.size = len(content)
                    tar.addfile(info, io.BytesIO(content))
            module.package_archive(source, output, '0123456789abcdef')
            with tarfile.open(output, 'r:gz') as tar:
                self.assertIn('app/Http/Controllers/API/ApplicationController.php', tar.getnames())
                correct = 'app/Http/Controllers/Api/V1/ApplicationDocumentDownloadController.php'
                self.assertIn(correct, tar.getnames())
                self.assertEqual(tar.extractfile(correct).read(), b'private download')
                self.assertNotIn('.env', tar.getnames())
                self.assertNotIn('database/user.sqlite', tar.getnames())
                self.assertEqual(tar.extractfile('entrypoint.sh').read(), b'#!/bin/bash\nexit 0\n')

    def test_path_guard_rejects_production_existing_overlap_and_traversal(self):
        module = infra()
        with tempfile.TemporaryDirectory() as directory:
            base = Path(directory)
            repo = base / 'repo'
            repo.mkdir()
            root = base / 'portal84'
            root.mkdir()
            existing = root / 'portal84-existing'
            existing.mkdir()
            for destination in [base / 'production', root / 'portal84-prod', existing,
                                root / '..' / 'portal84-escape', repo / 'portal84-copy', root]:
                with self.subTest(destination=destination):
                    with self.assertRaises(ValueError):
                        module.validate_destination(repo, root, destination)
            module.validate_destination(repo, root, root / 'portal84-release1')

    def test_symlink_destination_parent_is_refused(self):
        module = infra()
        with tempfile.TemporaryDirectory() as directory:
            base = Path(directory)
            (base / 'real').mkdir()
            try:
                (base / 'portal84').symlink_to(base / 'real', target_is_directory=True)
            except OSError:
                self.skipTest('SO sem permissÃ£o de symlink')
            with self.assertRaises(ValueError):
                module.validate_destination(base / 'repo', base / 'portal84', base / 'portal84/portal84-r1')

    def test_archive_contains_only_head_code_not_local_data(self):
        module = infra()
        with tempfile.TemporaryDirectory() as directory:
            base = Path(directory)
            repo = base / 'repo'
            repo.mkdir()
            # commit-tree cria fixture sem git add/commit, sem tocar o Ã­ndice compartilhado.
            def git(*args, data=None):
                return subprocess.run(['git', '-C', str(repo), *args], input=data,
                                      capture_output=True, check=True).stdout.strip()
            git('init', '-q')
            blob = git('hash-object', '-w', '--stdin', data=b'committed code\n')
            tree = git('mktree', data=b'100644 blob ' + blob + b'\tDockerfile\n')
            env = dict(os.environ, GIT_AUTHOR_NAME='Fixture', GIT_AUTHOR_EMAIL='fixture@invalid.test',
                       GIT_COMMITTER_NAME='Fixture', GIT_COMMITTER_EMAIL='fixture@invalid.test')
            commit = subprocess.run(['git', '-C', str(repo), 'commit-tree', tree.decode(), '-m', 'fixture'],
                                    env=env, capture_output=True, check=True).stdout.strip()
            git('update-ref', 'HEAD', commit.decode())
            (repo / 'Dockerfile').write_text('dirty code')
            (repo / '.env').write_text('LOCAL_SECRET=do-not-copy')
            (repo / 'data.sql').write_text('local data')
            root = base / 'portal84'
            root.mkdir()
            target = root / 'portal84-release1'
            module.prepare_release(repo, root, target)
            self.assertEqual((target / 'Dockerfile').read_text(), 'committed code\n')
            self.assertFalse((target / '.env').exists())
            self.assertFalse((target / 'data.sql').exists())

    def test_archive_filter_removes_even_tracked_secrets_and_dumps(self):
        module = infra()
        for name in ['.env', '.env.production', 'app/.env.test', 'storage/app/private/a.pdf',
                     'storage/logs/a.log', 'public/a.sql.gz', 'database/a.sqlite3',
                     'cetsadp_lms', 'backups/a.tar', 'vendor/a.php', 'node_modules/a.js',
                     'bootstrap/cache/config.php', 'public/build/a.js', 'public/storage/a.pdf',
                     'config/key.pem', 'public/a.dump', 'app/a.bak']:
            with self.subTest(name=name):
                self.assertFalse(module.allowed_release_file(name))
        for name in ['Dockerfile', '.dockerignore', 'composer.lock', 'package-lock.json',
                     'app/Models/User.php', 'resources/views/a.blade.php', 'public/slides/a.pptx',
                     'postcss.config.js', 'tailwind.config.js', 'lang/pt_BR/auth.php']:
            self.assertTrue(module.allowed_release_file(name), name)

    def test_unsafe_tar_member_aborts_before_destination_creation(self):
        module = infra()
        for name, kind in [('../escape', tarfile.REGTYPE), ('/absolute', tarfile.REGTYPE),
                           ('app/link', tarfile.SYMTYPE), ('app/hard', tarfile.LNKTYPE)]:
            with tempfile.TemporaryDirectory() as directory:
                base = Path(directory)
                archive = base / 'archive.tar'
                with tarfile.open(archive, 'w') as tar:
                    member = tarfile.TarInfo(name)
                    member.type = kind
                    member.linkname = '/etc/passwd' if kind != tarfile.REGTYPE else ''
                    tar.addfile(member, io.BytesIO(b''))
                target = base / 'portal84-release'
                with self.assertRaises(ValueError):
                    module.extract_release(archive, target)
                self.assertFalse(target.exists())


class EnvAndComposeTest(unittest.TestCase):
    def private_env(self):
        return {'APP_KEY': 'base64:' + base64.b64encode(b'z' * 32).decode(),
                'DB_PASSWORD': 'synthetic-password-with-entropy-84', 'PORTAL84_TAG': 'portal84-abc1234'}

    def test_private_env_requires_secrets_and_rejects_production_or_overrides(self):
        module = infra()
        with tempfile.TemporaryDirectory() as directory:
            file = Path(directory) / 'private.env'
            good = self.private_env()
            for values in [good, {**good, 'APP_ENV': 'production'}, {**good, 'DB_HOST': 'prod'},
                           {**good, 'DB_DATABASE': 'production'}, {**good, 'DB_PASSWORD': ''},
                           {**good, 'APP_KEY': ''}, {**good, 'DB_PASSWORD': 'password'},
                           {**good, 'PORTAL84_TAG': 'latest'}]:
                file.write_text('\n'.join(f'{k}={v}' for k, v in values.items()))
                file.chmod(0o600)
                if values == good:
                    self.assertEqual(module.read_private_env(file), good)
                else:
                    with self.assertRaises(ValueError):
                        module.read_private_env(file)

    def test_private_env_rejects_duplicate_interpolation_and_open_permissions(self):
        module = infra()
        with tempfile.TemporaryDirectory() as directory:
            file = Path(directory) / 'private.env'
            content = '\n'.join(f'{k}={v}' for k, v in self.private_env().items())
            for suffix in ['\nAPP_KEY=duplicate', '\nDB_PASSWORD=${PRODUCTION_PASSWORD}']:
                file.write_text(content + suffix)
                file.chmod(0o600)
                with self.assertRaises(ValueError):
                    module.read_private_env(file)
            if os.name != 'nt':
                file.write_text(content)
                file.chmod(0o644)
                with self.assertRaises(ValueError):
                    module.read_private_env(file)

    def resolved_compose(self):
        # Compose Ã© JSON (subconjunto YAML), para checagem sem dependÃªncias locais.
        path = ROOT / 'docker/portal-homologacao.compose.yml'
        self.assertTrue(path.exists(), 'Compose privado ausente')
        config = json.loads(path.read_text())
        values = self.private_env()
        for service in config['services'].values():
            service.pop('build', None)
            service['networks'] = {'isolada': None}
            service['volumes'] = [dict(type='volume', source=s.split(':')[0], target=s.split(':')[1])
                                  for s in service.get('volumes', [])]
            if service.get('ports'):
                service['ports'] = [dict(host_ip='127.0.0.1', published='8874', target=80)]
            for key, value in service.get('environment', {}).items():
                for variable, replacement in values.items():
                    if isinstance(value, str) and value.startswith('${' + variable + ':?'):
                        service['environment'][key] = replacement
            if service.get('image', '').startswith('portal84-lms:'):
                service['image'] = 'portal84-lms:portal84-abc1234'
        return config

    def test_validate_release_suppresses_secrets_and_scrubs_shell_overrides(self):
        module = infra()
        with tempfile.TemporaryDirectory() as directory:
            base = Path(directory) / 'portal84'
            release = base / 'portal84-release1'
            release.mkdir(parents=True)
            (release / '.portal84-release.json').write_text('{"revision":"abc1234","files":0}')
            private = base / 'private.env'
            private.write_text('\n'.join(f'{k}={v}' for k, v in self.private_env().items()))
            private.chmod(0o600)
            config = self.resolved_compose()
            config['services']['web']['build'] = {'context': str(release), 'dockerfile': 'Dockerfile'}
            output = io.StringIO()
            completed = subprocess.CompletedProcess([], 0, json.dumps(config), '')
            with patch.dict(os.environ, {'APP_ENV': 'production', 'DB_HOST': 'prod', 'APP_KEY': 'external'}), \
                 patch.object(module.subprocess, 'run', return_value=completed) as run, \
                 patch('sys.stdout', output):
                module.validate_release(release, private)
            self.assertEqual(run.call_args.kwargs['env']['APP_KEY'], self.private_env()['APP_KEY'])
            self.assertNotIn('DB_HOST', run.call_args.kwargs['env'])
            self.assertNotIn(self.private_env()['DB_PASSWORD'], output.getvalue())
            self.assertNotIn(self.private_env()['APP_KEY'], output.getvalue())
            self.assertIn('validado', output.getvalue())

    def test_contaminated_release_refuses_before_docker(self):
        module = infra()
        for contaminant in ('.env', 'storage/app/private/a.pdf', 'database/a.sql'):
            with self.subTest(contaminant=contaminant), tempfile.TemporaryDirectory() as directory:
                base = Path(directory) / 'portal84'
                release = base / 'portal84-release1'
                release.mkdir(parents=True)
                (release / '.portal84-release.json').write_text('{"revision":"abc1234","files":0}')
                data = release / contaminant
                data.parent.mkdir(parents=True, exist_ok=True)
                data.write_text('PRIVATE_DATA')
                private = base / 'private.env'
                private.write_text('\n'.join(f'{k}={v}' for k, v in self.private_env().items()))
                private.chmod(0o600)
                with patch.object(module.subprocess, 'run') as run:
                    with self.assertRaises(ValueError):
                        module.validate_release(release, private)
                    run.assert_not_called()


    def test_safe_resolved_compose_and_dangerous_mutations(self):
        module = infra()
        good = self.resolved_compose()
        module.validate_compose(good)
        mutations = [lambda c: c.update(name='production'),
                     lambda c: c['networks']['isolada'].update(internal=False),
                     lambda c: c['services']['web']['ports'][0].update(host_ip='0.0.0.0'),
                     lambda c: c['services']['web']['environment'].update(DB_HOST='production'),
                     lambda c: c['services']['worker'].update(image='production:latest'),
                     lambda c: c['services']['scheduler'].update(command=['scheduler-work']),
                     lambda c: c['services']['postgres'].update(ports=[{'published': '5432'}]),
                     lambda c: c['services']['web']['volumes'][0].update(type='bind', source='/srv/prod'),
                     lambda c: c['volumes']['storage'].update(external=True),
                     lambda c: c['services']['web']['environment'].update(MAIL_MAILER='smtp')]
        for mutate in mutations:
            bad = copy.deepcopy(good)
            mutate(bad)
            with self.assertRaises(ValueError):
                module.validate_compose(bad)


if __name__ == '__main__':
    unittest.main()
