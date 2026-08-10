<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaginaInicial; // Importante: Importar o Modelo

class HomeController extends Controller
{
    public function index()
    {
    // 1. Tenta buscar a página marcada como ATIVA
    $dados = PaginaInicial::where('ativa', true)->first();

    // 2. Se NENHUMA estiver ativa (erro humano), pega a última criada para o site não quebrar
    if (!$dados) {
        $dados = PaginaInicial::latest()->first() ?? new PaginaInicial();
    }

    return view('landing-page', compact('dados'));
    }
}