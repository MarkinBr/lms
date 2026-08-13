<?php

namespace Database\Seeders;

use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Cadastra a matriz curricular oficial do Curso Médio em Teologia:
 * 3 anos, 6 módulos curriculares, 40 disciplinas e 960 horas totais.
 *
 * Regras conceituais (Issue #21):
 *  - Os 6 períodos são módulos curriculares (tabela `modulos`),
 *    não `periodo_letivos` (que representa calendário acadêmico real).
 *  - Não cria turmas: a matriz não define modalidade, professor,
 *    código de turma, vagas, horários, local ou período letivo real.
 *  - Não marca disciplinas como "concluídas": esse é estado
 *    individual de aluno, não da matriz global.
 *  - `descricao` da disciplina fica nula: sem inventar ementas.
 *
 * Idempotência:
 *  - Curso: firstOrCreate por título.
 *  - Módulos: updateOrCreate por (curso_id, ordem).
 *  - Disciplinas: updateOrCreate por (curso_id, codigo).
 */
class MatrizCurricularTeologiaSeeder extends Seeder
{
    public const CURSO_TITULO = 'Curso Médio em Teologia';

    /**
     * Matriz curricular oficial.
     * Formato: [ordem_modulo => ['titulo' => ..., 'disciplinas' => [ [ordem, nome, carga], ... ]]]
     */
    private const MATRIZ = [
        1 => [
            'titulo' => '1º Período',
            'disciplinas' => [
                [1, 'Teologia Bíblica do Antigo Testamento', 10],
                [2, 'Teologia Bíblica do Novo Testamento', 10],
                [3, 'Livro do Apocalipse', 20],
                [4, 'Escatologia Bíblica', 30],
                [5, 'História Eclesiástica', 40],
                [6, 'Sistematização do Estudo', 8],
                [7, 'Antigo Testamento I – Pentateuco', 30],
                [8, 'Direito Eclesiástico', 12],
            ],
        ],
        2 => [
            'titulo' => '2º Período',
            'disciplinas' => [
                [9, 'Antigo Testamento II – Livros Históricos', 30],
                [10, 'Antigo Testamento III – Livros Poéticos', 30],
                [11, 'Antigo Testamento IV – Proféticos Maiores', 30],
                [12, 'História e Cultura Judaica', 16],
                [13, 'Novo Testamento I – Os Quatro Evangelhos', 20],
                [14, 'Novo Testamento II – Atos dos Apóstolos', 20],
                [15, 'Administração Eclesiástica', 14],
            ],
        ],
        3 => [
            'titulo' => '3º Período',
            'disciplinas' => [
                [16, 'Novo Testamento III – Epístolas Paulinas', 40],
                [17, 'Novo Testamento IV – Epístolas Gerais', 30],
                [18, 'Antigo Testamento V – Proféticos Menores', 40],
                [19, 'Psicologia Geral', 5],
                [20, 'Introdução às Línguas Bíblicas', 40],
                [21, 'Teologia Sistemática I – Bibliologia', 5],
            ],
        ],
        4 => [
            'titulo' => '4º Período',
            'disciplinas' => [
                [22, 'Teologia Sistemática II – Teologia e Cristologia', 40],
                [23, 'Teologia Sistemática III – Soteriologia', 50],
                [24, 'Teologia Sistemática V – Angelologia', 30],
                [25, 'Teologia Sistemática VI – Pneumatologia e Espiritualidade Cristã', 34],
                [26, 'Psicologia Pastoral', 6],
            ],
        ],
        5 => [
            'titulo' => '5º Período',
            'disciplinas' => [
                [27, 'Heresiologia – Religiões e Seitas', 10],
                [28, 'Missiologia', 20],
                [29, 'Eclesiologia', 6],
                [30, 'Exegese Bíblica', 40],
                [31, 'Relações Humanas', 4],
                [32, 'Apologética', 40],
                [33, 'Hermenêutica Bíblica', 40],
            ],
        ],
        6 => [
            'titulo' => '6º Período',
            'disciplinas' => [
                [34, 'Ética e Bioética', 5],
                [35, 'Geografia Bíblica', 15],
                [36, 'Homilética', 10],
                [37, 'Evangelismo e Oração', 40],
                [38, 'Teologia Pastoral', 20],
                [39, 'Pedagogia I e Didática', 30],
                [40, 'Pedagogia II – EBD e Educação Cristã', 40],
            ],
        ],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $curso = Curso::query()->firstOrCreate(
                ['titulo' => self::CURSO_TITULO],
                ['ativo' => true],
            );

            foreach (self::MATRIZ as $ordemModulo => $dadosModulo) {
                $modulo = Modulo::query()->updateOrCreate(
                    [
                        'curso_id' => $curso->id,
                        'ordem' => $ordemModulo,
                    ],
                    [
                        'titulo' => $dadosModulo['titulo'],
                    ],
                );

                foreach ($dadosModulo['disciplinas'] as [$ordemGlobal, $nome, $cargaHoraria]) {
                    $codigo = sprintf('TEO-M%02d-%02d', $ordemModulo, $ordemGlobal);

                    Disciplina::query()->updateOrCreate(
                        [
                            'curso_id' => $curso->id,
                            'codigo' => $codigo,
                        ],
                        [
                            'modulo_id' => $modulo->id,
                            'nome' => $nome,
                            'descricao' => null,
                            'carga_horaria' => $cargaHoraria,
                            'ordem' => $ordemGlobal,
                            'ativa' => true,
                        ],
                    );
                }
            }
        });
    }
}
