<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FotoAlunoController extends Controller
{
    public function __invoke(Request $request, User $aluno)
    {
        abort_unless($request->user()->is($aluno) && $aluno->nivel === 'aluno' && $aluno->ativo, 403);
        $foto = (string) $aluno->foto;
        abort_unless(preg_match('/\Aacessos\/fotos\/[a-zA-Z0-9_-]+\.(?:png|jpe?g|webp)\z/', $foto), 404);
        $disco = Storage::disk('local');
        abort_unless($disco->exists($foto) && in_array($disco->mimeType($foto), ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        return $disco->response($foto, null, ['Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer']);
    }
}
