<?php

namespace Database\Seeders;

use App\Models\Aula;
use App\Models\Curso;
use App\Models\Disciplina;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Importa conteúdo DIDÁTICO cuja disciplina de origem foi identificada.
 *
 * Pré-requisito: matriz confirmada pelo usuário, contendo a disciplina
 * História e Cultura Judaica no 2º período (TEO-M02-12, 16 horas).
 *
 * Não inventa aulas para as demais 39 disciplinas e não associa vídeo,
 * professor, apostila ou PDF não verificados. A aula entra NÃO LIBERADA
 * até a validação da secretaria/coordenação.
 *
 * Pode ser executado mais de uma vez sem sobrescrever aulas existentes.
 */
class ConteudosTeologiaVerificadosSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MatrizCurricularTeologiaSeeder::class);

        $path = __DIR__ . '/conteudos/teologia/historia-cultura-judaica-aula-01.html';
        $conteudo = @file_get_contents($path);

        if (! is_string($conteudo) || trim($conteudo) === '') {
            throw new RuntimeException('Não foi possível ler a leitura de História e Cultura Judaica: ' . $path);
        }

        DB::transaction(function () use ($conteudo): void {
            $curso = Curso::query()
                ->where('titulo', MatrizCurricularTeologiaSeeder::CURSO_TITULO)
                ->firstOrFail();

            $disciplina = Disciplina::query()
                ->where('curso_id', $curso->id)
                ->where('codigo', 'TEO-M02-12')
                ->where('nome', 'História e Cultura Judaica')
                ->firstOrFail();

            // Se a secretaria já cadastrou uma aula nesta disciplina,
            // preservá-la integralmente para evitar duplicação/substituição.
            if (Aula::query()->where('disciplina_id', $disciplina->id)->exists()) {
                $this->command?->warn('História e Cultura Judaica já possui aula(s); importação de conteúdo ignorada.');

                return;
            }

            Aula::query()->create([
                'modulo_id' => $disciplina->modulo_id,
                'disciplina_id' => $disciplina->id,
                'titulo' => 'Aula 1 — Origens do povo hebreu e formação de Israel',
                'descricao' => 'Leitura de estudo reorganizada a partir de transcrição já disponível nos arquivos do CETSADP; validação acadêmica pendente.',
                'conteudo_texto' => $conteudo,
                'tipo_conteudo' => 'texto',
                'video_url' => null,
                'video_provider' => null,
                'pdf_url' => null,
                'materiais' => [],
                'anexos' => [],
                'ordem' => 1,
                'liberada' => false,
            ]);
        });
    }
}
