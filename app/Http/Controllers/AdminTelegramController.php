<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTelegramController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        abort_unless($usuario->ativo && $usuario->nivel === 'admin', 403);

        $vinculado = \Illuminate\Support\Facades\DB::table('telegram_vinculos')
            ->where('user_id', $usuario->id)->whereNotNull('chat_id')->exists();

        return view('admin.telegram', compact('vinculado'));
    }

    public function gerar(Request $request, TelegramAdministrativo $bot): RedirectResponse
    {
        $request->session()->flash('telegram_codigo', $bot->criarCodigo($request->user()));

        return redirect()->route('admin.telegram.index');
    }

    public function revogar(Request $request, TelegramAdministrativo $bot): RedirectResponse
    {
        abort_unless($request->user()->ativo && $request->user()->nivel === 'admin', 403);
        $bot->revogar($request->user());

        return redirect()->route('admin.telegram.index')->with('telegram_aviso', 'Vinculação revogada.');
    }
}
