<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Modulo;
use App\Models\PaginaInicial;
use Database\Seeders\MatrizCurricularTeologiaSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SiteController extends Controller
{
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

        // View-model somente-leitura da matriz oficial do Curso Médio em Teologia.
        // Fonte: MatrizCurricularTeologiaSeeder (Issue #21). Não cria nada.
        $matrizTeologia = $this->montarMatrizTeologia();

        return view('site.index', compact('dados', 'matrizTeologia'));
    }

    /**
     * Monta a matriz curricular do Curso Médio em Teologia para exibição pública.
     *
     * Retorna uma Collection de módulos ordenados por `ordem`, cada um com suas
     * disciplinas também ordenadas — SEM criar nenhum registro no banco. Se o
     * seeder oficial ainda não tiver sido executado (curso ausente), retorna
     * `null` para que a view degrade de forma segura.
     *
     * @return Collection<int, Modulo>|null
     */
    private function montarMatrizTeologia(): ?Collection
    {
        $curso = Curso::query()
            ->where('titulo', MatrizCurricularTeologiaSeeder::CURSO_TITULO)
            ->with(['modulos' => function ($query) {
                $query->orderBy('ordem')->with(['disciplinas' => function ($q) {
                    $q->orderBy('ordem');
                }]);
            }])
            ->first();

        if (! $curso) {
            return null;
        }

        return $curso->modulos;
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
            'mensagem' => 'required|string',
        ]);

        // Aqui você pode enviar email, salvar no banco, etc.
        // Mail::to('contato@cetsadp.com.br')->send(new ContatoMail($validated));

        return back()->with('success', 'Mensagem enviada com sucesso!');
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
