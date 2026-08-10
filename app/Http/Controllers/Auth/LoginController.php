<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * LoginController — Autenticação por curso com subdomínio
 * 
 * Cada subdomínio (diaconato, lideranca, teologia, capelania) 
 * exibe login com identidade visual própria e próxima aula ao vivo.
 */
class LoginController extends Controller
{
    /**
     * Exibe a tela de login personalizada para o curso
     */
    public function showLogin(Request $request, string $curso)
    {
        // Se já autenticado, redireciona para dashboard
        if (Auth::check()) {
            return redirect()->route('curso.dashboard', ['curso' => $curso]);
        }

        // Busca próxima aula ao vivo ou programada do curso
        $proximaAula = $this->getProximaAula($curso);

        return view('portal.login', compact('proximaAula'));
        // $curso já é injetado pelo middleware IdentifyCurso via view()->share()
    }

    /**
     * Processa o login
     */
    public function login(Request $request, string $curso)
    {
        $request->validate([
            'cpf'      => 'required|string',
            'password' => 'required|string',
        ], [
            'cpf.required'      => 'Informe seu CPF.',
            'password.required' => 'Informe sua senha.',
        ]);

        // Remove formatação do CPF
        $cpf = preg_replace('/\D/', '', $request->cpf);

        $credentials = [
            'cpf'      => $cpf,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Verifica se o aluno tem acesso a este curso
            $user = Auth::user();
            if (!$user->cursos()->where('curso_slug', $curso)->exists()) {
                Auth::logout();
                return back()
                    ->withInput(['cpf' => $request->cpf])
                    ->withErrors(['cpf' => 'Você não está matriculado neste curso.']);
            }

            // Registra o acesso
            $user->update(['ultimo_acesso' => now()]);

            return redirect()->intended(
                route('curso.dashboard', ['curso' => $curso])
            );
        }

        return back()
            ->withInput(['cpf' => $request->cpf])
            ->withErrors(['cpf' => 'CPF ou senha incorretos.']);
    }

    /**
     * Logout
     */
    public function logout(Request $request, string $curso)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('curso.login', ['curso' => $curso]);
    }

    /**
     * Tela de registro
     */
    public function showRegister(Request $request, string $curso)
    {
        return view('portal.register');
    }

    /**
     * Tela esqueci senha
     */
    public function showForgot(Request $request, string $curso)
    {
        return view('portal.forgot-password');
    }

    /**
     * Busca a próxima aula ao vivo do curso
     */
    private function getProximaAula(string $cursoSlug): ?array
    {
        // Primeiro: busca aula ao vivo AGORA
        $aulaLive = Aula::where('curso_slug', $cursoSlug)
            ->where('is_live', true)
            ->where('status', 'ao_vivo')
            ->first();

        if ($aulaLive) {
            return [
                'titulo'    => $aulaLive->titulo,
                'data'      => $aulaLive->data_inicio->format('d M Y'),
                'horario'   => $aulaLive->data_inicio->format('H:i'),
                'professor' => $aulaLive->professor->nome,
                'ao_vivo'   => true,
                'data_iso'  => $aulaLive->data_inicio->toIso8601String(),
            ];
        }

        // Segundo: busca próxima aula programada
        $proxima = Aula::where('curso_slug', $cursoSlug)
            ->where('data_inicio', '>', now())
            ->where('tipo', 'ao_vivo')
            ->orderBy('data_inicio', 'asc')
            ->first();

        if ($proxima) {
            return [
                'titulo'    => $proxima->titulo,
                'data'      => $proxima->data_inicio->format('d M Y'),
                'horario'   => $proxima->data_inicio->format('H:i'),
                'professor' => $proxima->professor->nome,
                'ao_vivo'   => false,
                'data_iso'  => $proxima->data_inicio->toIso8601String(),
            ];
        }

        return null;
    }
}
