<?php

namespace Database\Seeders;

use App\Models\AcervoDidatico;
use App\Models\Aula;
use App\Models\AulaAoVivo;
use App\Models\Curso;
use App\Models\Disciplina;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Importação conservadora do acervo exportado do painel do CETSADP.
 *
 * - Os 17 links de vídeo são cadastrados como ENCONTROS do curso em
 *   aulas_ao_vivo, sem inventar disciplina ou turma.
 * - Só o vídeo sobre a História do Povo Hebreu é vinculado à aula digital
 *   de História e Cultura Judaica (2º período), que já tem leitura própria.
 * - Materiais são agrupados por curso/assunto; URLs sem disciplina/aula
 *   comprovada ficam no acervo para classificação pela secretaria.
 * - Nenhum material do acervo é publicado automaticamente.
 *
 * Execute depois de migrar e testar em homologação:
 * php artisan db:seed --class=Database\\Seeders\\AcervoCetsadp2026Seeder
 */
class AcervoCetsadp2026Seeder extends Seeder
{
    private const ORIGEM = 'CETSADP_VIDEOS_E_MATERIAIS.json';

    public function run(): void
    {
        $arquivo = __DIR__.'/dados/cetsadp-videos-materiais-20261111.json';
        if (! is_file($arquivo)) {
            throw new RuntimeException('Catálogo não encontrado: '.$arquivo);
        }
        $dados = json_decode((string) file_get_contents($arquivo), true, 512, JSON_THROW_ON_ERROR);

        if (count($dados['videos'] ?? []) !== 17 || count($dados['materiais'] ?? []) !== 12) {
            throw new RuntimeException('O catálogo esperado tem 17 vídeos e 12 entradas de material.');
        }

        $this->call(CourseCatalogSeeder::class);
        $this->call(ConteudosTeologiaVerificadosSeeder::class);

        DB::transaction(function () use ($dados): void {
            $cursos = Curso::query()
                ->whereIn('titulo', [
                    'Curso Médio em Teologia',
                    'Habilitação ao Diaconato',
                    'Liderança Cristã Ministerial',
                ])
                ->get()
                ->keyBy('titulo');

            foreach ($dados['videos'] as $video) {
                $cursoTitulo = $this->normalizarCurso((string) $video['curso']);
                $curso = $cursos->get($cursoTitulo);
                if (! $curso) {
                    throw new RuntimeException('Curso do vídeo não encontrado: '.$cursoTitulo);
                }

                $url = (string) ($video['url'] ?? '');
                if (! $this->urlYoutubeValida($url)) {
                    throw new RuntimeException('Vídeo com URL inválida no registro '.$video['id']);
                }

                $dataHora = Carbon::createFromFormat(
                    '!d/m/y, H:i',
                    (string) $video['data'],
                    'America/Sao_Paulo',
                )->format('Y-m-d H:i:s');

                $realizada = $video['status_origem'] === 'encerrada';

                // Não sobrescrever encontros já geridos pela secretaria.
                // O ID original é do OUTRO painel, não é chave primária do LMS.
                $existe = AulaAoVivo::query()
                    ->where('curso_id', $curso->id)
                    ->where('link_transmissao', $url)
                    ->exists();

                if ($existe) {
                    continue;
                }

                AulaAoVivo::query()->firstOrCreate(
                    [
                        'curso_id' => $curso->id,
                        'titulo' => (string) $video['titulo'],
                        'data_hora' => $dataHora,
                    ],
                    [
                        'turma_id' => null,
                        'descricao' => 'Importado do painel CETSADP; disciplina e turma não identificadas na exportação.',
                        'link_transmissao' => $url,
                        'modalidade' => AulaAoVivo::MODALIDADE_ONLINE,
                        'duracao' => (int) $video['duracao_min'],
                        'gravacao_disponivel' => $realizada,
                        'gravacao_url' => $realizada ? $url : null,
                        'status' => $realizada ? AulaAoVivo::STATUS_REALIZADA : AulaAoVivo::STATUS_AGENDADA,
                        'observacoes' => 'Status original: '.$video['status_origem']
                            .'. Data/horário extraídos do painel antigo; verificar contexto e disponibilidade do YouTube.',
                    ]
                );
            }

            foreach ($dados['materiais'] as $material) {
                if ($material['classificacao'] === 'invalido') {
                    $this->command?->warn('Material com URL inválida ignorado: '.$material['titulo']);

                    continue;
                }

                $url = (string) ($material['url'] ?? '');
                if (! $this->urlHttpsValida($url)) {
                    throw new RuntimeException('URL de material inválida: '.$material['titulo']);
                }

                $curso = $material['curso'] ? $cursos->get($material['curso']) : null;
                if ($material['curso'] && ! $curso) {
                    throw new RuntimeException('Curso do material não encontrado: '.$material['titulo']);
                }

                $disciplina = null;
                if ($material['disciplina_codigo']) {
                    if (! $curso) {
                        throw new RuntimeException('Material com disciplina sem curso: '.$material['titulo']);
                    }
                    $disciplina = Disciplina::query()
                        ->where('curso_id', $curso->id)
                        ->where('codigo', $material['disciplina_codigo'])
                        ->firstOrFail();
                }

                $chave = hash('sha256', self::ORIGEM.'|'.$material['titulo'].'|'.$url);
                AcervoDidatico::query()->firstOrCreate(
                    ['chave_importacao' => $chave],
                    [
                        'curso_id' => $curso?->id,
                        'disciplina_id' => $disciplina?->id,
                        'aula_id' => null,
                        'titulo' => (string) $material['titulo'],
                        'tipo' => (string) $material['tipo'],
                        'url' => $url,
                        'categoria' => (string) $material['categoria'],
                        'classificacao' => (string) $material['classificacao'],
                        'publicado' => false,
                    ]
                );
            }

            $this->vincularJudaica($dados, $cursos->get('Curso Médio em Teologia'));
        });

        // Cada vídeo passa a constar também na disciplina comprovada por
        // transcrição, sem substituir o vínculo histórico do encontro geral.
        $this->call(VincularTranscricoesTeologia2026Seeder::class);
    }

    private function vincularJudaica(array $dados, ?Curso $curso): void
    {
        if (! $curso) {
            throw new RuntimeException('Curso Médio em Teologia não encontrado.');
        }

        $disciplina = Disciplina::query()
            ->where('curso_id', $curso->id)
            ->where('codigo', 'TEO-M02-12')
            ->where('nome', 'História e Cultura Judaica')
            ->firstOrFail();

        $aula = Aula::query()
            ->where('disciplina_id', $disciplina->id)
            ->where('titulo', 'Aula 1 — Origens do povo hebreu e formação de Israel')
            ->first();

        if (! $aula) {
            $this->command?->warn('Aula de Judaica não encontrada. Vídeo permanece no acervo de encontros.');
            return;
        }

        $video = collect($dados['videos'])->firstWhere('id', '20');
        $urlVideo = (string) ($video['url'] ?? '');

        // Um vídeo diverso já vinculado requer revisão humana, nunca sobrescrita.
        if ($this->urlYoutubeValida($urlVideo) && blank($aula->video_url)) {
            $aula->video_url = $urlVideo;
            $aula->video_provider = 'youtube';
            $aula->tipo_conteudo = filled($aula->conteudo_texto) ? 'misto' : 'video';
            if (blank($aula->duracao)) {
                $aula->duracao = '03:00:00';
            }
        }

        $materiais = $aula->materiais ?? [];
        foreach ($dados['materiais'] as $material) {
            if ($material['disciplina_codigo'] !== 'TEO-M02-12') {
                continue;
            }

            $url = (string) $material['url'];
            if (! $this->urlHttpsValida($url)) {
                continue;
            }

            $jaExiste = collect($materiais)->contains(
                fn ($item): bool => is_array($item) && ($item['url'] ?? '') === $url
            );
            if (! $jaExiste) {
                $materiais[] = [
                    'titulo' => (string) $material['titulo'],
                    'tipo' => (string) $material['tipo'],
                    'url' => $url,
                    'contexto' => 'Material da disciplina; a exportação não confirma aula específica.',
                ];
            }
        }
        $aula->materiais = $materiais;
        // Mantém a aula bloqueada. Não altera notas, matrículas ou frequência.
        $aula->save();
    }

    private function normalizarCurso(string $original): string
    {
        return match ($original) {
            'Médio em Teologia' => 'Curso Médio em Teologia',
            'Curso de Habilitação ao Diaconato' => 'Habilitação ao Diaconato',
            'Liderança Cristã Ministerial' => 'Liderança Cristã Ministerial',
            default => throw new RuntimeException('Curso desconhecido no acervo: '.$original),
        };
    }

    private function urlYoutubeValida(string $url): bool
    {
        return (bool) preg_match(
            '~\\Ahttps://www\\.youtube\\.com/watch\\?v=[a-zA-Z0-9_-]{11}\\z~D',
            $url,
        );
    }

    private function urlHttpsValida(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL)
            && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https';
    }
}
