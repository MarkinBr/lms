<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use App\Services\Acessos\AtendimentoAluno;
use App\Services\Acessos\CadastroAluno;
use App\Services\Acessos\CredenciaisAluno;
use App\Services\Acessos\EntregasAcesso;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CentralAcessosController extends Controller
{
    public function home()
    {
        return view('secretaria.central.home');
    }

    public function index(Request $request)
    {
        $busca = mb_substr(trim((string) $request->query('busca', '')), 0, 255);
        $filtro = (string) $request->query('filtro', 'todos');
        $query = User::query()->where('nivel', 'aluno');
        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                foreach (['name', 'matricula_login', 'email', 'cpf'] as $campo) {
                    $q->orWhereRaw('LOWER('.$campo.') LIKE ?', ['%'.mb_strtolower($busca).'%']);
                }
            });
        }
        match ($filtro) {
            'ativos', 'ativo' => $query->where('ativo', true),
            'bloqueados', 'bloqueado' => $query->where('ativo', false),
            'primeiro_acesso', 'pendentes', 'pendente' => $query->where('primeiro_acesso_pendente', true),
            default => null,
        };
        $alunos = $query->with('matriculas.curso')->orderBy('name')->paginate(15)->withQueryString();
        $totais = ['todos' => User::where('nivel', 'aluno')->count(), 'ativos' => User::where('nivel', 'aluno')->where('ativo', true)->count(), 'bloqueados' => User::where('nivel', 'aluno')->where('ativo', false)->count(), 'primeiro_acesso' => User::where('nivel', 'aluno')->where('primeiro_acesso_pendente', true)->count()];

        return view('secretaria.central.index', compact('alunos', 'busca', 'filtro', 'totais'));
    }

    public function show(Request $request, User $aluno)
    {
        AtendimentoAluno::autorizar($request->user(), $aluno);
        $aluno->load('matriculas.curso', 'matriculas.turma');
        $inscricoes = Application::with('curso', 'courseModality', 'matricula.turma')->whereIn('id', $aluno->matriculas->pluck('application_id')->filter())->orderBy('created_at')->get();
        $historico = DB::table('acesso_auditorias')->leftJoin('users as autores', 'autores.id', '=', 'acesso_auditorias.ator_id')->where('aluno_id', $aluno->id)->select('acesso_auditorias.*', 'autores.name as autor_nome')->orderByDesc('acesso_auditorias.id')->limit(100)->get();
        $estado = AtendimentoAluno::estado($aluno);

        $entregas = DB::table('acesso_entregas')->where('aluno_id', $aluno->id)->select('id', 'canal', 'tipo', 'estado', 'created_at', 'updated_at')->orderByDesc('created_at')->limit(30)->get();

        return view('secretaria.central.show', compact('aluno', 'inscricoes', 'historico', 'estado', 'entregas'));
    }

    /**
     * Resumo exclusivamente de leitura, com acesso restrito à equipe autorizada.
     * Senha, hash, códigos e tokens nunca são apresentados.
     */
    public function formasDeAcesso(Request $request, User $aluno)
    {
        AtendimentoAluno::autorizar($request->user(), $aluno);
        $aluno->load('matriculas.curso');

        return view('secretaria.central.formas-acesso', compact('aluno'));
    }

    public function create()
    {
        return view('secretaria.central.form', ['aluno' => null, 'estado' => null]);
    }

    public function edit(Request $request, User $aluno)
    {
        AtendimentoAluno::autorizar($request->user(), $aluno);

        return view('secretaria.central.form', ['aluno' => $aluno, 'estado' => AtendimentoAluno::estado($aluno)]);
    }

    public function store(Request $request, CadastroAluno $cadastros, CredenciaisAluno $credenciais, EntregasAcesso $entregas)
    {
        $this->confirmarSenha($request);
        $dados = $cadastros->validar($request->only(CadastroAluno::CAMPOS), true);
        $meta = $request->validate(['canal' => 'required|in:email,whatsapp', 'motivo' => 'required|string|max:500', 'destino_conferido' => 'accepted']);
        if (empty($dados[$meta['canal'] === 'email' ? 'email' : 'whatsapp'])) {
            throw ValidationException::withMessages(['canal' => 'Informe o contato do canal escolhido.']);
        }
        $hash = hash('sha256', json_encode([$dados, $meta], JSON_THROW_ON_ERROR));
        if (! $request->boolean('confirmar')) {
            $operacao = (string) Str::uuid();
            $request->session()->put('acessos.cadastros.'.$operacao, ['hash' => $hash, 'expira' => now()->addMinutes(10)->timestamp]);
            DB::table('acesso_operacoes')->insert(['id' => $operacao, 'ator_id' => $request->user()->id, 'proposta_hash' => $hash, 'expira_em' => now()->addMinutes(10), 'created_at' => now(), 'updated_at' => now()]);

            return view('secretaria.central.review', ['aluno' => null, 'dados' => $dados, 'estado' => null, 'motivo' => $meta['motivo'], 'canal' => $meta['canal'], 'novo' => true, 'operacao' => $operacao, 'alteracoes' => []]);
        }
        $operacao = (string) $request->input('operacao');
        $proposta = $request->session()->get('acessos.cadastros.'.$operacao);
        abort_unless($proposta && $proposta['expira'] >= now()->timestamp && hash_equals($proposta['hash'], $hash), 409, 'Reabra o cadastro para revisar as informações.');
        if (isset($proposta['aluno_id'])) {
            return redirect()->route('secretaria.acessos.show', $proposta['aluno_id'])->with('status', 'Este cadastro já foi concluído. Consulte o histórico de entrega.');
        }
        [$aluno, $id] = DB::transaction(function () use ($request, $cadastros, $credenciais, $entregas, $dados, $meta, $operacao, $hash) {
            $registro = DB::table('acesso_operacoes')->where('id', $operacao)->lockForUpdate()->first();
            abort_unless($registro && (int) $registro->ator_id === $request->user()->id && Carbon::parse($registro->expira_em)->isFuture() && hash_equals($registro->proposta_hash, $hash), 409);
            if ($registro->aluno_id !== null) {
                return [User::findOrFail($registro->aluno_id), null];
            }
            $aluno = $cadastros->criar($request->user(), $dados, $meta['motivo']);
            $emissao = $credenciais->emitir($request->user(), $aluno, 'ativacao', $meta['canal'], $meta['motivo']);
            $aluno->refresh();
            $id = $entregas->preparar($request->user(), $aluno, $meta['canal'], 'ativacao', $emissao['mensagem'], $emissao['expira_em'], ['credencial_id' => $emissao['id']]);
            DB::table('acesso_operacoes')->where('id', $operacao)->update(['aluno_id' => $aluno->id, 'updated_at' => now()]);

            return [$aluno, $id];
        });
        $proposta['aluno_id'] = $aluno->id;
        $request->session()->put('acessos.cadastros.'.$operacao, $proposta);

        if ($id === null) {
            return redirect()->route('secretaria.acessos.show', $aluno)->with('status', 'Este cadastro já foi concluído.');
        }

        return redirect()->route('secretaria.acessos.entrega', [$aluno, $id]);
    }

    public function update(Request $request, User $aluno, CadastroAluno $cadastros)
    {
        AtendimentoAluno::autorizar($request->user(), $aluno);
        $this->confirmarSenha($request);
        $dados = $cadastros->validar($request->only(CadastroAluno::CAMPOS));
        $request->validate(['motivo' => 'required|string|max:500', 'destino_conferido' => 'accepted']);
        $estado = (string) $request->input('_estado', '');
        AtendimentoAluno::conferir($aluno, $estado);
        $hash = hash('sha256', json_encode([$dados, $estado, $request->motivo], JSON_THROW_ON_ERROR));
        if (! $request->boolean('confirmar')) {
            $operacao = (string) Str::uuid();
            $request->session()->put('acessos.edicoes.'.$operacao, ['hash' => $hash, 'aluno_id' => $aluno->id, 'expira' => now()->addMinutes(10)->timestamp]);
            $alteracoes = [];
            foreach ($dados as $campo => $valor) {
                if ((string) $valor !== (string) $aluno->getRawOriginal($campo)) {
                    $alteracoes[$campo] = ['antes' => $aluno->getRawOriginal($campo), 'depois' => $valor];
                }
            }

            return view('secretaria.central.review', ['aluno' => $aluno, 'dados' => $dados, 'estado' => $estado, 'motivo' => $request->motivo, 'novo' => false, 'alteracoes' => $alteracoes, 'operacao' => $operacao]);
        }
        $chave = 'acessos.edicoes.'.(string) $request->input('operacao');
        $proposta = $request->session()->get($chave);
        abort_unless($proposta && $proposta['aluno_id'] === $aluno->id && $proposta['expira'] >= now()->timestamp && hash_equals($proposta['hash'], $hash), 409, 'Reabra a edição e revise as alterações antes de confirmar.');
        $cadastros->atualizar($request->user(), $aluno, $dados, $estado, $request->motivo);
        $request->session()->forget($chave);

        return redirect()->route('secretaria.acessos.show', $aluno)->with('status', 'Cadastro atualizado. Se houve correção de e-mail, envie a confirmação pela aba de acesso.');
    }

    public function acao(Request $request, User $aluno, CredenciaisAluno $credenciais, EntregasAcesso $entregas)
    {
        AtendimentoAluno::autorizar($request->user(), $aluno);
        $this->confirmarSenha($request);
        $dados = $request->validate(['acao' => 'required|in:ativacao,recuperacao,temporaria,bloquear,desbloquear,sessoes', 'motivo' => 'required|string|max:500', 'estado' => 'required|string|size:64', 'canal' => 'nullable|in:email,whatsapp,presencial', 'destino_conferido' => 'accepted']);

        return DB::transaction(function () use ($request, $aluno, $dados, $credenciais, $entregas) {
            User::query()->lockForUpdate()->findOrFail($request->user()->id);
            $aluno = User::query()->lockForUpdate()->findOrFail($aluno->id);
            AtendimentoAluno::autorizar($request->user(), $aluno);
            AtendimentoAluno::conferir($aluno, $dados['estado']);
            if (in_array($dados['acao'], ['ativacao', 'recuperacao', 'temporaria'], true)) {
                $emissao = $credenciais->emitir($request->user(), $aluno, $dados['acao'], $dados['canal'] ?? '', $dados['motivo']);
                $aluno->refresh();
                $id = $entregas->preparar($request->user(), $aluno, $dados['canal'], $dados['acao'], $emissao['mensagem'], $emissao['expira_em'], ['credencial_id' => $emissao['id']]);

                return redirect()->route('secretaria.acessos.entrega', [$aluno, $id]);
            }
            AtendimentoAluno::revogar($aluno);
            if ($dados['acao'] !== 'sessoes') {
                $aluno->forceFill(['ativo' => $dados['acao'] === 'desbloquear'])->save();
            }
            AtendimentoAluno::registrar($request->user(), $aluno, $dados['acao'], $dados['motivo']);

            return redirect()->route('secretaria.acessos.show', $aluno)->with('status', 'Atendimento concluído e registrado no histórico.');
        });
    }

    public function mensagem(Request $request, User $aluno, EntregasAcesso $entregas)
    {
        AtendimentoAluno::autorizar($request->user(), $aluno);
        $this->confirmarSenha($request);
        $dados = $request->validate(['canal' => 'required|in:email,whatsapp', 'mensagem' => 'required|string|max:2000', 'motivo' => 'required|string|max:500', 'estado' => 'required|string|size:64', 'destino_conferido' => 'accepted']);
        AtendimentoAluno::conferir($aluno, $dados['estado']);
        $id = $entregas->preparar($request->user(), $aluno, $dados['canal'], 'mensagem', $dados['mensagem'], now()->addHours(24));

        return redirect()->route('secretaria.acessos.entrega', [$aluno, $id]);
    }

    public function entrega(Request $request, User $aluno, string $entrega, EntregasAcesso $entregas)
    {
        $dados = $entregas->revelar($request->user(), $aluno, $entrega);

        return view('secretaria.central.entrega', ['aluno' => $aluno, 'entrega' => $dados, 'entregaId' => $entrega]);
    }

    public function enviar(Request $request, User $aluno, string $entrega, EntregasAcesso $entregas)
    {
        $request->validate(['confirmar' => 'accepted', 'destino_conferido' => 'accepted']);
        $url = $entregas->confirmar($request->user(), $aluno, $entrega);
        if ($url !== null) {
            return redirect()->away($url)->header('Referrer-Policy', 'no-referrer');
        }

        return redirect()->route('secretaria.acessos.show', $aluno)->with('status', 'Entrega confirmada. E-mail enfileirado quando este foi o canal escolhido; consulte o histórico.');
    }

    public function foto(Request $request, User $aluno)
    {
        AtendimentoAluno::autorizar($request->user(), $aluno);
        $this->confirmarSenha($request);
        $dados = $request->validate(['foto' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:5120', 'motivo' => 'required|string|max:500']);
        $path = $dados['foto']->store('acessos/fotos', 'local');
        abort_unless($path, 503, 'Não foi possível armazenar a foto.');
        try {
            DB::transaction(function () use ($request, $aluno, $path, $dados) {
                User::query()->lockForUpdate()->findOrFail($request->user()->id);
                $aluno = User::query()->lockForUpdate()->findOrFail($aluno->id);
                AtendimentoAluno::autorizar($request->user(), $aluno);
                AtendimentoAluno::conferir($aluno, (string) $request->input('estado'));
                $aluno->forceFill(['foto' => $path])->save();
                AtendimentoAluno::registrar($request->user(), $aluno, 'foto_atualizada', $dados['motivo'], ['campos' => ['foto']]);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return redirect()->route('secretaria.acessos.show', $aluno)->with('status', 'Foto atualizada.');
    }

    public function verFoto(Request $request, User $aluno)
    {
        AtendimentoAluno::autorizar($request->user(), $aluno);
        $foto = (string) $aluno->foto;
        abort_if($foto === '' || str_contains($foto, '..') || str_contains($foto, ':') || str_starts_with($foto, '/'), 404);
        $disk = str_starts_with($foto, 'acessos/fotos/') ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($foto) && in_array(Storage::disk($disk)->mimeType($foto), ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        return Storage::disk($disk)->response($foto, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function email(Request $request, User $aluno, EntregasAcesso $entregas)
    {
        AtendimentoAluno::autorizar($request->user(), $aluno);
        $this->confirmarSenha($request);
        $request->validate(['estado' => 'required|string|size:64', 'motivo' => 'required|string|max:500', 'destino_conferido' => 'accepted']);

        return DB::transaction(function () use ($request, $aluno, $entregas) {
            User::query()->lockForUpdate()->findOrFail($request->user()->id);
            $aluno = User::query()->lockForUpdate()->findOrFail($aluno->id);
            AtendimentoAluno::autorizar($request->user(), $aluno);
            AtendimentoAluno::conferir($aluno, $request->estado);
            abort_unless($aluno->ativo && $aluno->email_confirmacao_pendente, 422, 'Informe o novo e-mail na edição do cadastro.');
            $token = bin2hex(random_bytes(32));
            $expira = now()->addMinutes(60);
            $aluno->forceFill(['email_confirmacao_token' => hash('sha256', $token), 'email_confirmacao_expira_em' => $expira])->save();
            $url = URL::temporarySignedRoute('secretaria.acessos.email.confirmar', $expira, ['aluno' => $aluno->id]).'#token='.$token;
            $id = $entregas->preparar($request->user(), $aluno, 'email', 'confirmar_email', "Confirme seu endereço de e-mail no CETSADP pelo link individual:\n".$url, $expira, ['email_token_hash' => hash('sha256', $token)]);

            return redirect()->route('secretaria.acessos.entrega', [$aluno, $id]);
        });
    }

    public function confirmarEmail(Request $request, User $aluno)
    {
        if ($request->isMethod('GET')) {
            return response()->view('aluno.auth.confirmar-email-atendimento')
                ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer')
                ->header('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; script-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
        }
        $token = (string) $request->input('token', '');
        abort_unless(preg_match('/^[a-f0-9]{64}$/D', $token), 403);
        DB::transaction(function () use ($aluno, $token) {
            $aluno = User::query()->lockForUpdate()->findOrFail($aluno->id);
            abort_unless($aluno->nivel === 'aluno' && $aluno->ativo && $aluno->email_confirmacao_pendente && $aluno->email_confirmacao_expira_em?->isFuture() && $aluno->email_confirmacao_token && hash_equals($aluno->email_confirmacao_token, hash('sha256', $token)), 403);
            $email = $aluno->email_confirmacao_pendente;
            AtendimentoAluno::reservarEmail($email);
            abort_if(User::whereKeyNot($aluno->id)->whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists(), 409);
            AtendimentoAluno::revogar($aluno);
            $aluno->forceFill(['email' => $email, 'email_verified_at' => now(), 'email_confirmacao_pendente' => null])->save();
        });

        return redirect()->route('aluno.login')->with('status', 'E-mail confirmado. Entre com sua matrícula e senha.');
    }

    private function confirmarSenha(Request $request): void
    {
        $senha = (string) $request->input('senha_confirmacao', '');
        $request->request->remove('senha_confirmacao');
        $request->query->remove('senha_confirmacao');
        $ator = $request->user()->fresh();
        $chave = 'atendimento-senha:'.$ator->id;
        if (RateLimiter::tooManyAttempts($chave, 5)) {
            throw ValidationException::withMessages(['senha_confirmacao' => 'Aguarde alguns minutos para confirmar sua identidade novamente.']);
        }
        $recente = $request->session()->get('acessos.confirmacao');
        $fingerprint = hash('sha256', (string) $ator->password);
        if ($senha === '' && $recente && $recente['ator'] === $ator->id && $recente['expira'] >= now()->timestamp && hash_equals($recente['hash'], $fingerprint)) {
            return;
        }
        if ($senha === '' || ! Hash::check($senha, $ator->password)) {
            RateLimiter::hit($chave, 600);
            throw ValidationException::withMessages(['senha_confirmacao' => 'Confirme sua senha de Secretaria para executar este atendimento.']);
        }
        RateLimiter::clear($chave);
        $request->session()->put('acessos.confirmacao', ['ator' => $ator->id, 'hash' => $fingerprint, 'expira' => now()->addMinutes(10)->timestamp]);
    }
}
