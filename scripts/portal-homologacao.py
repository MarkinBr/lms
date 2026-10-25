#!/usr/bin/env python3
"""Homologação privada da Issue #84 (somente biblioteca padrão).

Na VPS, depois de integrar e commitar a candidata:
 python3 scripts/portal-homologacao.py prepare --repo /checkout/candidata \
   --root /srv/portal84 --dest /srv/portal84/portal84-<sha>
 python3 /srv/portal84/portal84-<sha>/scripts/portal-homologacao.py validate \
   --release /srv/portal84/portal84-<sha> --env-file /srv/portal84/private.env

Criar private.env NA VPS, modo 0600, contendo SOMENTE APP_KEY (base64 de 32
bytes aleatórios), DB_PASSWORD (>=24 caracteres aleatórios), PORTAL84_TAG
(portal84-<sha>). Não copiar env produção. Não há default de credenciais.

O programa prepara/valida apenas: nunca inicia containers, restaura/exporta
bancos ou remove releases/volumes. validate captura compose config em memória;
não imprimir compose config no terminal nem em logs (contém segredos).

Coordenador: build web; up -d postgres; criar/restaurar SOMENTE portal84_lms e
portal84_legado neste postgres isolado. Depois run --rm --no-deps web migrate;
conferir sucesso antes de up -d web worker scheduler. Web/worker/heartbeat
recusam iniciar sem marcador desta tag em storage/app; migrate invalida o
marcador antes da tentativa e só o grava após sucesso. Conferir que a fila
portal84-homologacao está vazia antes de worker; nunca consumir jobs default
restaurados. Storage público/privado compartilhado em portal84_storage; copiar
somente arquivos selecionados, diretamente na VPS. Dados não saem da VPS.

Scheduler é SOMENTE heartbeat (60s) em /tmp/portal84-scheduler-heartbeat.
run --rm --no-deps web scheduler-list INSPECIONA a agenda; NUNCA usar
schedule:run/work: a agenda inclui cobranças reais. Mail array reside na memória
do processo (sem caixa postal persistente); Telegram/MP/WhatsApp sem tokens,
rede internal bloqueia saída para integrações.

Acesso: ssh -N -L 8874:127.0.0.1:8874 <VPS>; http://127.0.0.1:8874.
Limites somados: ~1696 MiB/1.8 CPU. Build usa recursos adicionais: coordenar
janela e monitorar a VPS. Só git archive HEAD entra na release (alterações não
commitadas não entram); nenhum arquivo de storage, env ou dependência local.
"""
import argparse
import base64
import binascii
import json
import io
import copy
import os
from pathlib import Path, PurePosixPath
import re
import stat
import subprocess
import sys
import tarfile
import tempfile

FORBIDDEN = re.compile(r'(^|[-_.])(prod(uction|ucao|ução)?|cetsadp|live)([-_.]|$)', re.I)
TAG = re.compile(r'portal84-[a-z0-9][a-z0-9._-]{0,63}\Z')
SOURCE_DIRS = {'app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'lang', 'docker', 'scripts', 'tests'}
SOURCE_FILES = {'Dockerfile', '.dockerignore', 'entrypoint.sh', 'artisan', 'composer.json',
                'composer.lock', 'package.json', 'package-lock.json', 'phpunit.xml', 'postcss.config.js', 'tailwind.config.js'}
SECRET_KEYS = {'APP_KEY', 'DB_PASSWORD', 'PORTAL84_TAG'}


def checked_path(path):
    path = Path(path)
    if not path.is_absolute() or '..' in path.parts:
        raise ValueError('Caminho deve ser absoluto e sem travessia.')
    for component in (path, *path.parents):
        if component.is_symlink() or (hasattr(component, 'is_junction') and component.is_junction()):
            raise ValueError('Links/junctions recusados em caminhos de release/env.')
    if any(FORBIDDEN.search(part) for part in path.parts):
        raise ValueError('Caminho de produção recusado.')
    return path.resolve()


def validate_destination(repo, root, destination):
    repo, root, destination = map(checked_path, (repo, root, destination))
    if root.name != 'portal84' or not root.is_dir():
        raise ValueError('Raiz deve existir e chamar-se portal84.')
    if destination.parent != root or not TAG.fullmatch(destination.name):
        raise ValueError('Destino deve ser filho direto portal84-<versão>.')
    if destination.exists() or destination == repo or repo in destination.parents or destination in repo.parents:
        raise ValueError('Destino deve ser NOVO e separado do checkout.')
    return repo, root, destination


def allowed_release_file(name):
    path = PurePosixPath(name)
    if path.is_absolute() or '..' in path.parts or '\\' in name:
        return False
    parts = tuple(part.lower() for part in path.parts)
    if not parts or any(p.startswith('.env') or p in {
            'storage', 'backups', 'backup', 'node_modules', '.git', '.aws', '.codex', '.agents'} for p in parts):
        return False
    # Somente o vendor público versionado; dependências PHP/aninhadas continuam bloqueadas.
    if any(part == 'vendor' and not (index == 1 and path.parts[:2] == ('public', 'vendor'))
           for index, part in enumerate(parts)):
        return False
    lower = name.lower()
    if lower.startswith(('bootstrap/cache/', 'public/build/', 'public/storage/')) or lower == 'public/hot':
        return False
    if re.search(r'\.(sql|sqlite\d*|db|dump|backup|bak|pem|key|p12|pfx|log|zip|tar|tgz)([.-]|$)', lower):
        return False
    return path.parts[0] in SOURCE_DIRS or name in SOURCE_FILES or bool(re.fullmatch(r'vite\.config\.[a-z]+', name))


def extract_release(archive, destination):
    destination = Path(destination)
    # Validar tudo ANTES de criar destino. Não usar extractall.
    with tarfile.open(archive, 'r:') as tar:
        members = tar.getmembers()
        for member in members:
            path = PurePosixPath(member.name)
            if path.is_absolute() or '..' in path.parts or '\\' in member.name or ':' in member.name:
                raise ValueError('Travessia no archive recusada.')
            if not (member.isfile() or member.isdir()):
                raise ValueError('Links e arquivos especiais no archive recusados.')
        destination.mkdir(mode=0o700, exist_ok=False)
        count = 0
        for member in members:
            if not member.isfile() or not allowed_release_file(member.name):
                continue
            target = destination.joinpath(*PurePosixPath(member.name).parts)
            target.parent.mkdir(parents=True, exist_ok=True)
            with tar.extractfile(member) as source, target.open('xb') as output:
                while chunk := source.read(1024 * 1024):
                    output.write(chunk)
            target.chmod(0o755 if member.mode & 0o111 else 0o644)
            count += 1
    return count


def prepare_release(repo, root, destination):
    repo, root, destination = validate_destination(repo, root, destination)
    def git(*args):
        result = subprocess.run(['git', '-C', str(repo), *args], capture_output=True)
        if result.returncode:
            raise ValueError('Git falhou; nenhum dado externo será copiado.')
        return result.stdout.decode().strip()
    revision = git('rev-parse', '--verify', 'HEAD')
    with tempfile.TemporaryDirectory(prefix='portal84-archive-') as directory:
        archive = Path(directory) / 'head.tar'
        with archive.open('wb') as output:
            result = subprocess.run(['git', '-c', 'core.autocrlf=false', '-C', str(repo), 'archive', '--format=tar', 'HEAD'],
                                    stdout=output, stderr=subprocess.PIPE)
        if result.returncode or revision != git('rev-parse', '--verify', 'HEAD'):
            raise ValueError('Archive falhou ou HEAD mudou durante preparação.')
        count = extract_release(archive, destination)
    (destination / '.portal84-release.json').write_text(
        json.dumps({'revision': revision, 'files': count}) + '\n', encoding='utf-8')
    return {'revision': revision, 'files': count}


def package_archive(archive, output, revision):
    """Preserva nomes do Git, inclusive API/Api, sem passar pelo NTFS."""
    output = Path(output)
    with tarfile.open(archive, 'r:') as source:
        members = source.getmembers()
        for member in members:
            path = PurePosixPath(member.name)
            if path.is_absolute() or '..' in path.parts or '\\' in member.name or ':' in member.name:
                raise ValueError('Travessia no archive recusada.')
            if not (member.isfile() or member.isdir()):
                raise ValueError('Links e arquivos especiais no archive recusados.')
        selected = [m for m in members if m.isfile() and allowed_release_file(m.name)]
        with output.open('xb') as raw, tarfile.open(fileobj=raw, mode='w:gz') as target:
            for member in selected:
                if member.name.endswith('.sh'):
                    content = source.extractfile(member).read().replace(b'\r\n', b'\n')
                    member = copy.copy(member)
                    member.size = len(content)
                    target.addfile(member, io.BytesIO(content))
                else:
                    target.addfile(member, source.extractfile(member))
            marker = json.dumps({'revision': revision, 'files': len(selected)}).encode() + b'\n'
            info = tarfile.TarInfo('.portal84-release.json')
            info.size = len(marker)
            target.addfile(info, io.BytesIO(marker))
    return {'revision': revision, 'files': len(selected)}


def package_release(repo, output):
    repo, output = map(checked_path, (repo, output))
    if not output.parent.is_dir() or not output.name.startswith('portal84-') or not output.name.endswith('.tar.gz'):
        raise ValueError('Pacote deve ser portal84-<versão>.tar.gz em diretório existente.')
    revision = subprocess.run(['git', '-C', str(repo), 'rev-parse', '--verify', 'HEAD'], capture_output=True, check=True).stdout.decode().strip()
    with tempfile.TemporaryDirectory(prefix='portal84-package-') as directory:
        archive = Path(directory) / 'head.tar'
        with archive.open('wb') as stream:
            result = subprocess.run(['git', '-c', 'core.autocrlf=false', '-C', str(repo), 'archive', '--format=tar', revision], stdout=stream, stderr=subprocess.PIPE)
        if result.returncode:
            raise ValueError('Git archive falhou; pacote não criado.')
        return package_archive(archive, output, revision)


def validate_secrets(values):
    if set(values) != SECRET_KEYS:
        raise ValueError('Env aceita somente APP_KEY, DB_PASSWORD e PORTAL84_TAG.')
    key = values['APP_KEY']
    try:
        valid = key.startswith('base64:') and len(base64.b64decode(key[7:], validate=True)) == 32
    except (ValueError, binascii.Error):
        valid = False
    if not valid:
        raise ValueError('APP_KEY deve ser base64 de 32 bytes.')
    password = values['DB_PASSWORD']
    if len(password) < 24 or not re.fullmatch(r'[A-Za-z0-9_+/.=-]+', password) or len(set(password)) < 8:
        raise ValueError('DB_PASSWORD deve ser forte e sem interpolação/caracteres de shell.')
    if not TAG.fullmatch(values['PORTAL84_TAG']) or FORBIDDEN.search(values['PORTAL84_TAG']):
        raise ValueError('PORTAL84_TAG deve identificar uma candidata isolada.')


def read_private_env(path):
    path = checked_path(path)
    if not path.is_file():
        raise ValueError('Arquivo env privado ausente.')
    if os.name != 'nt' and (stat.S_IMODE(path.stat().st_mode) != 0o600 or path.stat().st_uid != os.geteuid()):
        raise ValueError('Env deve pertencer ao executor e ter modo 0600.')
    values = {}
    for line in path.read_text(encoding='utf-8').splitlines():
        line = line.strip()
        if not line or line.startswith('#'):
            continue
        key, separator, value = line.partition('=')
        if not separator or key in values:
            raise ValueError('Env inválido/chave duplicada.')
        values[key] = value
    validate_secrets(values)
    return values


def validate_compose(config):
    """Valida JSON resolvido do Compose sem retornar/imprimir segredos."""
    def require(condition, message):
        if not condition:
            raise ValueError(message)
    require(config.get('name') == 'portal84', 'Projeto deve ser portal84.')
    services = config.get('services', {})
    require(set(services) == {'web', 'worker', 'scheduler', 'postgres'}, 'Serviços inesperados.')
    networks = config.get('networks', {})
    require(set(networks) == {'isolada'}, 'Rede inesperada.')
    network = networks['isolada']
    require(network.get('internal') is True and not network.get('external')
            and network.get('name') == 'portal84_isolada', 'Rede deve ser interna e própria.')
    volumes = config.get('volumes', {})
    require(set(volumes) == {'postgres', 'storage'}, 'Volumes inesperados.')
    for name, volume in volumes.items():
        require(volume.get('name') == 'portal84_' + name and not volume.get('external')
                and not volume.get('driver_opts'), 'Volume deve ser próprio e sem bind.')
    expected = {
        'APP_ENV': 'homologacao', 'APP_DEBUG': 'false', 'APP_CONFIG_CACHE': '/tmp/portal84-config.php',
        'APP_URL': 'http://127.0.0.1:8874', 'DB_CONNECTION': 'pgsql', 'DB_HOST': 'postgres',
        'DB_DATABASE': 'portal84_lms', 'DB_USERNAME': 'portal84_teste',
        'LEGADO_DB_CONNECTION': 'pgsql', 'LEGADO_DB_HOST': 'postgres',
        'LEGADO_DB_DATABASE': 'portal84_legado', 'LEGADO_DB_USERNAME': 'portal84_teste',
        'QUEUE_CONNECTION': 'database', 'DB_QUEUE': 'portal84-homologacao', 'MAIL_MAILER': 'array',
        'SESSION_COOKIE': 'portal84_session', 'SESSION_DOMAIN': '', 'CACHE_PREFIX': 'portal84',
        'TELEGRAM_ENABLED': 'false', 'TELEGRAM_BOT_TOKEN': '', 'TELEGRAM_WEBHOOK_SECRET': '',
        'MP_ACCESS_TOKEN': '', 'MP_PUBLIC_KEY': '', 'WHATSAPP_API_TOKEN': '',
        'DB_URL': '', 'DATABASE_URL': '', 'LEGADO_DB_URL': '',
    }
    reference = services['web'].get('environment', {})
    total_memory = total_cpu = 0
    for name, service in services.items():
        require(not any(service.get(k) for k in ('privileged', 'network_mode', 'pid', 'devices', 'cap_add', 'volumes_from', 'env_file', 'entrypoint')),
                'Configuração privilegiada/externa recusada.')
        require(set(service.get('networks', {})) == {'isolada'}, 'Serviço fora da rede isolada.')
        require(service.get('pids_limit', 0) in range(1, 201), 'Limite de processos obrigatório.')
        memory = re.fullmatch(r'(\d+)([mg]?)', str(service.get('mem_limit', '0')).lower())
        require(memory is not None, 'Limite de memória inválido.')
        amount = int(memory[1]) * {'': 1, 'm': 1024**2, 'g': 1024**3}[memory[2]]
        require(0 < amount <= 768 * 1024**2, 'Limite de memória ausente/excessivo.')
        cpu = float(service.get('cpus', 0))
        require(0 < cpu <= 1, 'Limite de CPU ausente/excessivo.')
        total_memory += amount
        total_cpu += cpu
        mounts = service.get('volumes', [])
        require(len(mounts) == 1, 'Somente um volume por serviço é permitido.')
        mount = mounts[0]
        source, target = ('postgres', '/var/lib/postgresql/data') if name == 'postgres' else ('storage', '/var/www/html/storage/app')
        require(mount.get('type') == 'volume' and mount.get('source') == source
                and mount.get('target') == target, 'Montagem externa/inesperada recusada.')
        ports = service.get('ports', [])
        if name == 'web':
            require(len(ports) == 1 and ports[0].get('host_ip') == '127.0.0.1'
                    and str(ports[0].get('published')) == '8874' and ports[0].get('target') == 80,
                    'Web deve publicar somente 127.0.0.1:8874.')
        else:
            require(not ports, 'Porta adicional recusada.')
        if name == 'postgres':
            require(service.get('image') == 'postgres:16-bookworm', 'PostgreSQL 16 obrigatório.')
            pg = service.get('environment', {})
            require(pg.get('POSTGRES_DB') == 'portal84_lms' and pg.get('POSTGRES_USER') == 'portal84_teste'
                    and pg.get('POSTGRES_PASSWORD') == reference.get('DB_PASSWORD'), 'Postgres deve usar credenciais isoladas.')
            continue
        image = service.get('image', '')
        require(image.startswith('portal84-lms:') and TAG.fullmatch(image.split(':', 1)[1])
                and image == services['web'].get('image'), 'Imagem/tag deve ser a mesma candidata.')
        require(service.get('command') == [name], 'Modo de serviço inseguro.')
        environment = service.get('environment', {})
        require(environment.get('PORTAL84_RELEASE') == image.split(':', 1)[1], 'Gate de migração deve corresponder à imagem.')
        require(environment == reference, 'Web/worker/scheduler devem ter configuração idêntica.')
        require(all(environment.get(k) == v for k, v in expected.items()), 'Configuração de produção/integração recusada.')
        validate_secrets({'APP_KEY': environment.get('APP_KEY', ''), 'DB_PASSWORD': environment.get('DB_PASSWORD', ''),
                          'PORTAL84_TAG': image.split(':', 1)[1]})
        require(environment.get('LEGADO_DB_PASSWORD') == environment.get('DB_PASSWORD'), 'Senha da cópia inconsistente.')
    require(total_memory <= 1792 * 1024**2 and total_cpu <= 2, 'Orçamento de recursos excedido.')


def validate_release(release, env_file):
    release = checked_path(release)
    if release.parent.name != 'portal84' or not TAG.fullmatch(release.name) or not release.is_dir():
        raise ValueError('Release deve estar sob raiz portal84.')
    marker = release / '.portal84-release.json'
    if not marker.is_file():
        raise ValueError('Release sem registro de preparação por git archive.')
    for file in release.rglob('*'):
        relative = file.relative_to(release).as_posix()
        if file.is_symlink() or (hasattr(file, 'is_junction') and file.is_junction()):
            raise ValueError('Link em contexto de build recusado.')
        if file.is_file() and relative != '.portal84-release.json' and not allowed_release_file(relative):
            raise ValueError('Contexto contaminado: preparar outra release limpa de HEAD.')
    env_path = checked_path(env_file)
    if release == env_path or release in env_path.parents:
        raise ValueError('Env deve ficar fora do contexto de build.')
    values = read_private_env(env_path)
    environment = {k: v for k, v in os.environ.items()
                   if k in {'PATH', 'HOME', 'USERPROFILE', 'SystemRoot', 'TEMP', 'TMP'}}
    environment.update(values)
    command = ['docker', '--context', 'default', 'compose', '--project-name', 'portal84',
               '--env-file', str(env_path), '-f', str(release / 'docker/portal-homologacao.compose.yml'),
               'config', '--format', 'json']
    result = subprocess.run(command, cwd=release, env=environment, capture_output=True, text=True)
    if result.returncode:
        raise ValueError('Compose não resolvido; detalhes suprimidos para proteger segredos.')
    try:
        config = json.loads(result.stdout)
        validate_compose(config)
    except (KeyError, TypeError, json.JSONDecodeError) as error:
        raise ValueError('Compose inválido (conteúdo privado suprimido).') from error
    build = config['services']['web'].get('build', {})
    if Path(build.get('context', '')).resolve() != release or build.get('dockerfile') != 'Dockerfile':
        raise ValueError('Contexto de build deve ser somente a release limpa.')
    print('portal84: Compose validado; rede privada, recursos limitados, segredos omitidos.')


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    commands = parser.add_subparsers(dest='command', required=True)
    prepare = commands.add_parser('prepare')
    for option in ('repo', 'root', 'dest'):
        prepare.add_argument('--' + option, required=True, type=Path)
    validate = commands.add_parser('validate')
    validate.add_argument('--release', required=True, type=Path)
    validate.add_argument('--env-file', required=True, type=Path)
    package = commands.add_parser('package')
    package.add_argument('--repo', required=True, type=Path)
    package.add_argument('--output', required=True, type=Path)
    args = parser.parse_args()
    try:
        if args.command == 'prepare':
            result = prepare_release(args.repo, args.root, args.dest)
            print(f"portal84: release limpa criada; HEAD={result['revision']}; arquivos={result['files']}")
        elif args.command == 'package':
            result = package_release(args.repo, args.output)
            print(f"portal84: pacote limpo criado; HEAD={result['revision']}; arquivos={result['files']}")
        else:
            validate_release(args.release, args.env_file)
    except (ValueError, OSError, tarfile.TarError) as error:
        print(f'ERRO: {error}', file=sys.stderr)
        return 1
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
