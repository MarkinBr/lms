<?php

namespace App\Http\Controllers;

use App\Models\PaginaInicial;
use App\Mail\ContatoRecebidoMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SiteController extends Controller
{
    public function index()
    {
        // Pega a página ativa (ou a última criada se nenhuma estiver ativa)
        $dados = PaginaInicial::where('ativa', true)->first() 
                 ?? PaginaInicial::latest()->first();

        // Se não houver nenhuma página cadastrada, cria uma padrão
        if (!$dados) {
            $dados = $this->criarPaginaPadrao();
        }

        // Se estiver em modo manutenção, mostra página especial
        if ($dados->modo_manutencao ?? false) {
            return view('site.manutencao', compact('dados'));
        }

        return view('site.index', compact('dados'));
    }

    private function criarPaginaPadrao()
    {
        return PaginaInicial::create([
            'titulo_identificacao' => 'Página Padrão',
            'ativa' => true,
            'hero_titulo_principal' => 'Transforme Seu',
            'hero_destaque_1' => 'Chamado',
            'hero_destaque_2' => 'Ministério',
            'hero_subtitulo' => 'Formação teológica de excelência com professores qualificados e metodologia inovadora.',
            'hero_botao_texto' => 'Inscreva-se Agora',
            'hero_botao_link' => '/admin/register',
            'sobre_titulo' => 'Quem Somos',
            'sobre_texto' => 'O CETSADP é um centro de formação teológica comprometido com a excelência no ensino.',
            'cursos_titulo' => 'Nossos Cursos',
            'cursos_descricao' => 'Escolha a formação ideal para seu chamado ministerial.',
            'contato_titulo' => 'Entre em Contato',
            'telefone' => '(21) 9999-9999',
            'email' => 'contato@cetsadp.com.br',
        ]);
    }

    public function contato(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email',
            'telefone' => 'nullable|string|max:30',
            'assunto' => 'required|string|max:120',
            'mensagem' => 'required|string|max:5000',
            'consentimento' => 'accepted',
        ]);

        Mail::to(config('mail.contact.destination'))->send(new ContatoRecebidoMail($validated));

        return response()->json(['success' => true, 'message' => 'Mensagem enviada com sucesso!']);
    }

    public function newsletter(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:newsletter,email',
        ]);

        // Salvar no banco de newsletter
        // Newsletter::create($validated);

        return back()->with('success', 'Inscrito com sucesso!');
    }
}
