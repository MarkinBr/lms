<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\SecretariaAssinatura;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SecretariaAssinaturasController extends Controller
{
    /**
     * Cadastro de imagem manuscrita, não certificado de assinatura digital.
     * O arquivo fica privado e seu uso é restrito ao próprio responsável.
     */
    public function salvar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'cargo' => ['required', 'string', 'min:3', 'max:150'],
            'imagem' => ['nullable', 'file', 'max:2048', 'mimes:png,jpg,jpeg,webp'],
            'desenho' => ['nullable', 'string', 'max:1000000'],
            'confirmar_titularidade' => ['accepted'],
        ]);

        if ($request->hasFile('imagem') === filled($dados['desenho'] ?? null)) {
            throw ValidationException::withMessages(['imagem' => 'Envie uma imagem ou desenhe a assinatura no celular.']);
        }

        $origem = 'upload';
        if ($request->hasFile('imagem')) {
            $arquivo = $request->file('imagem');
            $mime = (string) $arquivo->getMimeType();
            $permitidos = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
            if (! array_key_exists($mime, $permitidos) || $arquivo->getSize() > 2 * 1024 * 1024) {
                throw ValidationException::withMessages(['imagem' => 'Use PNG, JPG ou WebP de até 2 MB.']);
            }
            $binario = file_get_contents($arquivo->getRealPath());
            $extensao = $permitidos[$mime];
        } else {
            $origem = 'touch';
            $texto = (string) $dados['desenho'];
            if (! str_starts_with($texto, 'data:image/png;base64,')) {
                throw ValidationException::withMessages(['desenho' => 'O desenho deve ser uma imagem PNG do campo de assinatura.']);
            }
            $binario = base64_decode(substr($texto, strlen('data:image/png;base64,')), true);
            $mime = 'image/png';
            $extensao = 'png';
            if ($binario === false || strlen($binario) > 600000) {
                throw ValidationException::withMessages(['desenho' => 'Imagem de assinatura inválida ou muito grande.']);
            }
        }

        if (! is_string($binario) || $binario === '') {
            throw ValidationException::withMessages(['imagem' => 'Imagem de assinatura vazia.']);
        }
        $metadados = @getimagesizefromstring($binario);
        if (! is_array($metadados) || $metadados['mime'] !== $mime ||
            $metadados[0] < 120 || $metadados[1] < 50 ||
            $metadados[0] > 4000 || $metadados[1] > 4000) {
            throw ValidationException::withMessages(['imagem' => 'Imagem inválida. Tamanho permitido entre 120 × 50 e 4000 × 4000 pixels.']);
        }

        $caminho = 'secretaria/assinaturas/'.$request->user()->id.'/'.Str::uuid().'.'.$extensao;
        if (! Storage::disk('local')->put($caminho, $binario)) {
            throw ValidationException::withMessages(['imagem' => 'Não foi possível guardar a assinatura de forma privada.']);
        }

        try {
            SecretariaAssinatura::query()->create([
                'responsavel_id' => $request->user()->id,
                'cargo' => trim($dados['cargo']),
                'arquivo_privado' => $caminho,
                'mime' => $mime,
                'origem' => $origem,
                'ativa' => true,
            ]);
        } catch (\Throwable $erro) {
            Storage::disk('local')->delete($caminho);
            throw $erro;
        }

        return redirect()->route('secretaria.documentos.assinaturas')
            ->with('status', 'Assinatura gráfica cadastrada para uso interno. Não é certificado digital.');
    }
}
