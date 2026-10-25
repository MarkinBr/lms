<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramAdministrativo;
use App\Services\Telegram\TelegramFalhaEnvio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramAdministrativo $bot): JsonResponse
    {
        $segredo = (string) config('services.telegram.webhook_secret');
        $token = (string) config('services.telegram.bot_token');

        if ($segredo === '' || $token === '') {
            return response()->json(['ok' => false], 503);
        }
        if (! hash_equals($segredo, (string) $request->header('X-Telegram-Bot-Api-Secret-Token', ''))) {
            return response()->json(['ok' => false], 403);
        }

        $dados = $request->validate([
            'update_id' => ['required', 'integer', 'min:0'],
            'message' => ['sometimes', 'array'],
            'callback_query' => ['sometimes', 'array'],
        ]);
        try {
            $bot->receber($dados);
        } catch (TelegramFalhaEnvio) {
            return response()->json(['ok' => false], 503);
        }

        return response()->json(['ok' => true]);
    }
}
