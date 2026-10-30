<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Acessos\CredenciaisAluno;
use App\Services\Auth\IdentificadorAluno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AcessoAssistidoController extends Controller
{
    public function formulario()
    {
        return $this->pagina('aluno.auth.assistido');
    }

    public function entrar(Request $request, IdentificadorAluno $identificadores, CredenciaisAluno $credenciais)
    {
        abort_if(Auth::check(), 403);
        $identificador = (string) $request->input('identificador');
        $codigo = (string) $request->input('codigo');
        $limite = 'assistido:'.hash('sha256', $identificadores->chaveLimite($identificador).'|'.$request->ip());
        if (strlen($identificador) > 255 || strlen($codigo) > 100 || RateLimiter::tooManyAttempts($limite, 5)) {
            return back()->withErrors(['codigo' => 'Não foi possível validar. Aguarde ou solicite outra credencial à secretaria.']);
        }
        RateLimiter::hit($limite, 1800);
        $aluno = $identificadores->resolver($identificador);
        $sessao = $aluno ? $credenciais->consumirTemporaria($aluno, $codigo) : null;
        if (! $sessao) {
            return back()->withErrors(['codigo' => 'Não foi possível validar. Confira os dados ou procure a secretaria.']);
        }

        return $this->iniciarSessao($request, $sessao);
    }

    public function link(string $id)
    {
        // GET é inerte: prévias de mensageiros não consomem a credencial.
        return $this->pagina('aluno.auth.assistido-link', ['id' => $id]);
    }

    public function comprovar(Request $request, string $id, CredenciaisAluno $credenciais)
    {
        abort_if(Auth::check(), 403);
        $segredo = $request->input('segredo');
        abort_unless(is_string($segredo) && preg_match('/^[a-f0-9]{64}$/D', $segredo), 403);
        $sessao = $credenciais->consumirLink($id, $segredo);
        abort_unless($sessao, 403, 'Link inválido ou expirado. Solicite outro à secretaria.');

        return $this->iniciarSessao($request, $sessao);
    }

    public function senha(Request $request, CredenciaisAluno $credenciais)
    {
        abort_unless(! Auth::check() && $credenciais->validarSessao((array) $request->session()->get('acesso_assistido', [])), 403, 'Atendimento expirado. Solicite uma nova credencial.');

        return $this->pagina('aluno.auth.assistido-senha');
    }

    public function definir(Request $request, CredenciaisAluno $credenciais)
    {
        abort_if(Auth::check(), 403);
        $sessao = (array) $request->session()->get('acesso_assistido', []);
        abort_unless($credenciais->validarSessao($sessao), 403, 'Atendimento expirado. Solicite uma nova credencial.');
        $request->validate(['password' => ['required', 'string', 'max:1024'], 'password_confirmation' => ['required', 'string', 'max:1024']]);
        $credenciais->definirSenha($sessao, $request->string('password')->toString(), $request->string('password_confirmation')->toString());
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('aluno.login')->with('status', 'Senha definida. Entre com sua matrícula e a nova senha.');
    }

    public static function iniciarSessao(Request $request, array $sessao)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('acesso_assistido', $sessao);

        return redirect()->route('aluno.assistido.senha')->withHeaders(['Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer']);
    }

    private function pagina(string $view, array $dados = [])
    {
        return response()->view($view, $dados)->withHeaders([
            'Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Content-Security-Policy' => "default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'",
        ]);
    }
}
