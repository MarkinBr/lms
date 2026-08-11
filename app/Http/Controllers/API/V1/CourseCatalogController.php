<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Http\JsonResponse;

class CourseCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        $cursos = Curso::query()
            ->select(['id', 'titulo'])
            ->where('ativo', true)
            ->orderBy('titulo')
            ->with(['modalities' => function ($query) {
                $query->select(['id', 'curso_id', 'nome'])
                    ->where('ativo', true)
                    ->orderBy('nome');
            }])
            ->get()
            ->map(fn (Curso $curso) => [
                'id' => $curso->id,
                'titulo' => $curso->titulo,
                'modalidades' => $curso->modalities
                    ->map(fn ($modalidade) => [
                        'id' => $modalidade->id,
                        'nome' => $modalidade->nome,
                    ])
                    ->values(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $cursos,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
