<?php

// ═════════════════════════════════════════════════════════════════════════
// ARQUIVO 1: lms/app/Http/Controllers/ReciboPublicoController.php
// ═════════════════════════════════════════════════════════════════════════

namespace App\Http\Controllers;

use App\Models\Mensalidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ReciboPublicoController extends Controller
{
    /**
     * Tela de conferência: pede últimos 3 dígitos do CPF antes de mostrar o recibo
     *
     * GET /recibo/v/{codigo_validacao}
     */
    public function form(string $codigo)
    {
        // Valida formato UUID antes de tocar no banco
        if (!Str::isUuid($codigo)) {
            abort(404);
        }

        $existe = Mensalidade::where('codigo_validacao', $codigo)
            ->where('status', 'Pago')
            ->exists();

        if (!$existe) {
            abort(404);
        }

        return view('recibo-publico.conferir-cpf', ['codigo' => $codigo]);
    }

    /**
     * Exibe o recibo após conferência do CPF.
     *
     * POST /recibo/v/{codigo_validacao}
     */
    public function show(Request $request, string $codigo)
    {
        $request->validate([
            'cpf3' => 'required|digits:3',
        ]);

        // Throttle: 5 tentativas / minuto / IP (anti-bruteforce)
        $rlKey = 'recibo-cpf:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rlKey, 5)) {
            Log::warning('Bruteforce em recibo público', [
                'ip'     => $request->ip(),
                'codigo' => $codigo,
            ]);
            return back()->withErrors([
                'cpf3' => 'Muitas tentativas. Aguarde 1 minuto e tente novamente.',
            ]);
        }

        if (!Str::isUuid($codigo)) {
            abort(404);
        }

        $mensalidade = Mensalidade::with(['matricula.user', 'matricula.curso'])
            ->where('codigo_validacao', $codigo)
            ->where('status', 'Pago')
            ->first();

        if (!$mensalidade) {
            abort(404);
        }

        $cpfReal = preg_replace('/[^0-9]/', '', $mensalidade->matricula->user->cpf ?? '');
        $cpf3    = preg_replace('/[^0-9]/', '', $request->input('cpf3'));

        if (!Str::endsWith($cpfReal, $cpf3)) {
            RateLimiter::hit($rlKey, 60); // 60s de janela

            Log::notice('Tentativa de acesso ao recibo com CPF errado', [
                'ip'                 => $request->ip(),
                'mensalidade_id'     => $mensalidade->id,
                'cpf3_fornecido'     => $cpf3,
                'tentativas_restantes' => RateLimiter::retriesLeft($rlKey, 5),
            ]);

            return back()->withErrors([
                'cpf3' => 'Os dígitos não conferem com o cadastro. Verifique e tente novamente.',
            ])->withInput();
        }

        // Sucesso — limpa contador e loga acesso (LGPD Art. 37)
        RateLimiter::clear($rlKey);

        Log::info('Recibo público acessado', [
            'mensalidade_id' => $mensalidade->id,
            'ip'             => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'timestamp'      => now()->toIso8601String(),
        ]);

        return view('recibo-publico.show', [
            'mensalidade'  => $mensalidade,
            'mascarado'    => $this->mascararDados($mensalidade),
        ]);
    }

    /**
     * Mascara dados pessoais para exibição segura.
     */
    private function mascararDados(Mensalidade $m): array
    {
        $user = $m->matricula->user;
        $cpf  = preg_replace('/[^0-9]/', '', $user->cpf ?? '');

        return [
            // CPF: ***.***.123-45 (mostra só últimos 5)
            'cpf_mascarado' => $cpf
                ? '***.***.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2)
                : '—',

            // Nome: "João S******" (1ª palavra + iniciais)
            'nome_mascarado' => $this->mascararNome($user->name ?? ''),

            // Cidade/UF apenas
            'localizacao' => trim(($user->cidade ?? '') . ' / ' . ($user->estado ?? ''), ' /'),
        ];
    }

    private function mascararNome(string $nome): string
    {
        $partes = explode(' ', trim($nome));
        if (count($partes) === 1) {
            return $partes[0];
        }
        $primeiro = $partes[0];
        $resto    = array_slice($partes, 1);
        $iniciais = array_map(fn($p) => ($p[0] ?? '') . str_repeat('*', max(0, strlen($p) - 1)), $resto);
        return $primeiro . ' ' . implode(' ', $iniciais);
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
 * ARQUIVO 2: SNIPPET PARA lms/routes/web.php
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * REMOVER esta linha:
 *   Route::get('/recibo/{id}', [ReciboController::class, 'show'])->name('recibo.show');
 *
 * ADICIONAR estas linhas (no lugar):
 *
 * use App\Http\Controllers\ReciboPublicoController;
 *
 * Route::prefix('recibo/v')->name('recibo.publico.')->group(function () {
 *     Route::get('/{codigo}', [ReciboPublicoController::class, 'form'])
 *         ->where('codigo', '[0-9a-f-]{36}')
 *         ->middleware('throttle:30,1')
 *         ->name('form');
 *
 *     Route::post('/{codigo}', [ReciboPublicoController::class, 'show'])
 *         ->where('codigo', '[0-9a-f-]{36}')
 *         ->middleware('throttle:10,1')
 *         ->name('show');
 * });
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */

/* ═══════════════════════════════════════════════════════════════════════════
 * ARQUIVO 3: lms/resources/views/recibo-publico/conferir-cpf.blade.php
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * <!DOCTYPE html>
 * <html lang="pt-BR">
 * <head>
 *   <meta charset="UTF-8">
 *   <title>Validar Recibo — CETSADP</title>
 *   <meta name="viewport" content="width=device-width, initial-scale=1">
 *   <style>
 *     body{font-family:system-ui,sans-serif;background:#f3f4f6;display:grid;place-items:center;min-height:100vh;margin:0;padding:20px}
 *     .card{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:420px;width:100%}
 *     h1{margin:0 0 8px;color:#003D7A;font-size:22px}
 *     p{color:#64748b;margin:0 0 24px;font-size:14px}
 *     label{display:block;margin-bottom:6px;font-weight:600;color:#334155;font-size:13px}
 *     input{width:100%;padding:14px;border:2px solid #e2e8f0;border-radius:8px;font-size:18px;text-align:center;letter-spacing:6px;font-weight:600}
 *     input:focus{outline:none;border-color:#003D7A}
 *     button{width:100%;background:#003D7A;color:#fff;padding:14px;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;margin-top:16px}
 *     button:hover{background:#002b56}
 *     .error{background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:6px;margin-bottom:14px;font-size:13px}
 *     .lgpd{font-size:11px;color:#94a3b8;margin-top:24px;line-height:1.5;text-align:center}
 *   </style>
 * </head>
 * <body>
 *   <div class="card">
 *     <h1>🔒 Validar Recibo</h1>
 *     <p>Para sua segurança, informe os <strong>3 últimos dígitos do CPF</strong> do titular para visualizar o recibo.</p>
 *
 *     @if($errors->any())
 *       <div class="error">{{ $errors->first() }}</div>
 *     @endif
 *
 *     <form method="POST" action="{{ route('recibo.publico.show', $codigo) }}">
 *       @csrf
 *       <label for="cpf3">Últimos 3 dígitos do CPF</label>
 *       <input type="text" id="cpf3" name="cpf3" maxlength="3" pattern="\d{3}" required
 *              inputmode="numeric" autocomplete="off" autofocus>
 *       <button type="submit">Conferir e Exibir Recibo</button>
 *     </form>
 *
 *     <p class="lgpd">
 *       Esta verificação garante que somente o titular ou pessoa autorizada
 *       acessa os dados pessoais. Conforme LGPD (Lei 13.709/2018).
 *       Tentativas inválidas são registradas.
 *     </p>
 *   </div>
 * </body>
 * </html>
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */

/* ═══════════════════════════════════════════════════════════════════════════
 * ARQUIVO 4: lms/resources/views/recibo-publico/show.blade.php
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Versão MASCARADA (segura para link público).
 * Para versão com CPF completo, use a rota /mensalidade/{id}/recibo (auth).
 *
 * <!DOCTYPE html>
 * <html lang="pt-BR">
 * <head>
 *   <meta charset="UTF-8">
 *   <title>Recibo {{ $mensalidade->codigo_validacao }} — CETSADP</title>
 *   <style>
 *     body{font-family:system-ui,sans-serif;background:#f8fafc;padding:24px;color:#1e293b;margin:0}
 *     .recibo{max-width:680px;margin:0 auto;background:#fff;padding:48px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.06)}
 *     .header{text-align:center;border-bottom:3px solid #003D7A;padding-bottom:24px;margin-bottom:32px}
 *     .header h1{color:#003D7A;margin:0 0 4px;font-size:22px}
 *     .header p{margin:2px 0;font-size:12px;color:#64748b}
 *     .selo{background:#059669;color:#fff;padding:8px 16px;border-radius:20px;display:inline-block;font-weight:700;letter-spacing:2px;font-size:13px;margin:8px 0}
 *     .info-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px dotted #e2e8f0}
 *     .info-row .label{color:#64748b;font-weight:500;font-size:13px}
 *     .info-row .value{font-weight:600;font-size:13px}
 *     .valor-box{background:#ecfdf5;border:2px solid #059669;padding:24px;text-align:center;margin:28px 0;border-radius:8px}
 *     .valor-box .valor{font-size:32px;font-weight:700;color:#059669}
 *     .valor-box .label{font-size:11px;color:#475569;text-transform:uppercase;letter-spacing:2px;margin-bottom:6px}
 *     .footer{text-align:center;margin-top:36px;padding-top:20px;border-top:1px solid #e2e8f0;font-size:11px;color:#94a3b8}
 *     .lgpd{background:#eff6ff;border-left:4px solid #2563eb;padding:14px;margin-top:24px;font-size:12px;color:#1e40af;border-radius:4px}
 *   </style>
 * </head>
 * <body>
 *   <div class="recibo">
 *     <div class="header">
 *       <h1>RECIBO DE PAGAMENTO</h1>
 *       <p>Centro Educacional Teológico e Social — CETSADP</p>
 *       <div class="selo">✓ PAGO</div>
 *       <p>Recibo Nº {{ str_pad($mensalidade->id, 6, '0', STR_PAD_LEFT) }} /
 *         {{ $mensalidade->data_pagamento->format('Y') }}</p>
 *     </div>
 *
 *     <div class="info-row">
 *       <span class="label">Pagador (parcial):</span>
 *       <span class="value">{{ $mascarado['nome_mascarado'] }}</span>
 *     </div>
 *     <div class="info-row">
 *       <span class="label">CPF (parcial):</span>
 *       <span class="value">{{ $mascarado['cpf_mascarado'] }}</span>
 *     </div>
 *     <div class="info-row">
 *       <span class="label">Localização:</span>
 *       <span class="value">{{ $mascarado['localizacao'] ?: '—' }}</span>
 *     </div>
 *     <div class="info-row">
 *       <span class="label">Matrícula:</span>
 *       <span class="value">{{ $mensalidade->matricula->numero_matricula }}</span>
 *     </div>
 *     <div class="info-row">
 *       <span class="label">Curso:</span>
 *       <span class="value">{{ $mensalidade->matricula->curso->titulo ?? '—' }}</span>
 *     </div>
 *     <div class="info-row">
 *       <span class="label">Vencimento:</span>
 *       <span class="value">{{ $mensalidade->data_vencimento->format('d/m/Y') }}</span>
 *     </div>
 *     <div class="info-row">
 *       <span class="label">Data do Pagamento:</span>
 *       <span class="value">{{ $mensalidade->data_pagamento->format('d/m/Y') }}</span>
 *     </div>
 *
 *     <div class="valor-box">
 *       <div class="label">Valor Pago</div>
 *       <div class="valor">R$ {{ number_format($mensalidade->valor_pago ?? $mensalidade->valor, 2, ',', '.') }}</div>
 *     </div>
 *
 *     <div class="lgpd">
 *       ℹ️ Dados pessoais foram mascarados nesta versão pública conforme a LGPD.
 *       Para visualizar o recibo completo, o aluno deve acessar o portal autenticado.
 *     </div>
 *
 *     <div class="footer">
 *       Código de validação: {{ $mensalidade->codigo_validacao }}<br>
 *       Documento emitido eletronicamente — {{ now()->format('d/m/Y H:i') }}
 *     </div>
 *   </div>
 * </body>
 * </html>
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
