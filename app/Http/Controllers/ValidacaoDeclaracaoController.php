<?php

namespace App\Http\Controllers;

use App\Models\Declaracao;
use Illuminate\Http\Request;

class ValidacaoDeclaracaoController extends Controller
{
    /**
     * Página de validação de declaração
     */
    public function validar($codigo)
    {
        $codigo = strtoupper(trim($codigo));
        
        $declaracao = Declaracao::where('codigo_validacao', $codigo)
            ->with(['matricula.user', 'matricula.curso', 'matricula.turma'])
            ->first();

        if (!$declaracao) {
            return view('validacao.nao-encontrado', [
                'codigo' => $codigo
            ]);
        }

        // Registra a visualização
        $declaracao->registrarVisualizacao();

        return view('validacao.sucesso', [
            'declaracao' => $declaracao,
            'matricula' => $declaracao->matricula,
        ]);
    }

    /**
     * Formulário de busca de declaração
     */
    public function formulario()
    {
        return view('validacao.formulario');
    }

    /**
     * Busca declaração por código
     */
    public function buscar(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|min:10'
        ]);

        return redirect()->route('validacao.validar', [
            'codigo' => strtoupper(trim($request->codigo))
        ]);
    }
}