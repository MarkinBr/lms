<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Models\EventoPublico;
use App\Models\PaginaInicial;
use App\Services\NotificarContatoRecebidoService;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function __construct(
        private readonly NotificarContatoRecebidoService $notificadorContato,
    ) {}

    public function index()
    {
        // Pega a página ativa (ou a última criada se nenhuma estiver ativa)
        $dados = PaginaInicial::where('ativa', true)->first()
                 ?? PaginaInicial::latest()->first();

        // Se não houver nenhuma página cadastrada, cria uma padrão
        if (! $dados) {
            $dados = $this->criarPaginaPadrao();
        }

        // Se estiver em modo manutenção, mostra página especial
        if ($dados->modo_manutencao ?? false) {
            return view('site.manutencao', compact('dados'));
        }

        $eventosPublicos = EventoPublico::visiveis()->get();
        $categoriasPublicas = $eventosPublicos->pluck('categoria')->unique()->mapWithKeys(fn ($categoria) => [$categoria => EventoPublico::CATEGORIAS[$categoria] ?? 'Outros']);
        return view('site.index', compact('dados', 'eventosPublicos', 'categoriasPublicas'));

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
            'email' => 'required|email|max:255',
            'telefone' => 'nullable|string|max:30',
            'assunto' => 'required|string|max:120',
            'mensagem' => 'required|string|max:5000',
            'consentimento' => 'accepted',
        ]);

        $enviado = $this->notificadorContato->enviar([
            ...$validated,
            'telefone' => $validated['telefone'] ?? null,
        ]);
        $mensagem = $enviado
            ? 'Mensagem enviada com sucesso! Retornaremos em até 24 horas úteis.'
            : 'Não foi possível enviar sua mensagem agora. Tente novamente em instantes.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $enviado,
                'message' => $mensagem,
            ]);
        }

        return back()->with($enviado ? 'success' : 'error', $mensagem);
    }

    public function newsletter(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $assinante = NewsletterSubscriber::query()->firstOrCreate(
            ['email' => $email],
            ['ativo' => true],
        );

        if (! $assinante->ativo) {
            $assinante->update(['ativo' => true]);
        }

        $mensagem = 'Inscrição na newsletter confirmada! Que Deus abençoe.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $mensagem,
            ]);
        }

        return back()->with('success', $mensagem);
    }
}
