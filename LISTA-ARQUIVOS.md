# Issue #57 — Lista final de arquivos

Legenda: **C** criado, **A** alterado, **R** removido.

## Núcleo acadêmico

| Op | Arquivo |
|---|---|
| A | `app/Models/Aula.php` |
| A | `app/Models/Disciplina.php` |
| A | `app/Models/Modulo.php` |
| A | `app/Models/Turma.php` |
| A | `database/seeders/AulaTesteSeeder.php` |
| C | `database/migrations/2026_08_24_000001_add_disciplina_id_to_aulas_table.php` |

## Central administrativa e Resources

| Op | Arquivo |
|---|---|
| C | `app/Filament/Pages/GestaoAcademica.php` |
| C | `app/Services/GestaoAcademica/PainelAcademicoQuery.php` |
| C | `app/Filament/Resources/DisciplinaResource.php` |
| C | `app/Filament/Resources/DisciplinaResource/Pages/CreateDisciplina.php` |
| C | `app/Filament/Resources/DisciplinaResource/Pages/EditDisciplina.php` |
| C | `app/Filament/Resources/DisciplinaResource/Pages/ListDisciplinas.php` |
| C | `app/Filament/Resources/DisciplinaResource/RelationManagers/AulasRelationManager.php` |
| C | `app/Filament/Resources/PeriodoLetivoResource.php` |
| C | `app/Filament/Resources/PeriodoLetivoResource/Pages/ManagePeriodoLetivos.php` |
| C | `app/Filament/Resources/ModuloResource/RelationManagers/DisciplinasRelationManager.php` |
| C | `app/Filament/Resources/ModuloResource/RelationManagers/AulasLegadasRelationManager.php` |
| R | `app/Filament/Resources/ModuloResource/RelationManagers/AulasRelationManager.php` |
| A | `app/Filament/Resources/CursoResource.php` |
| A | `app/Filament/Resources/CursoResource/RelationManagers/ModulosRelationManager.php` |
| A | `app/Filament/Resources/ModuloResource.php` |
| A | `app/Filament/Resources/ModuloResource/Pages/EditModulo.php` |
| A | `app/Filament/Resources/TurmaResource.php` |
| A | `app/Filament/Resources/MatriculaResource.php` |
| A | `app/Filament/Resources/MatriculaResource/Pages/CreateMatricula.php` |
| A | `app/Filament/Resources/MatriculaResource/Pages/EditMatricula.php` |
| A | `app/Filament/Resources/AulaAoVivoResource.php` |
| A | `app/Providers/Filament/AdminPanelProvider.php` |

## Secretaria e professor

| Op | Arquivo |
|---|---|
| A | `app/Filament/Secretario/Resources/DisciplinaSecretariaResource.php` |
| A | `app/Filament/Secretario/Resources/TurmaSecretariaResource.php` |
| A | `app/Filament/Professor/Resources/AulaResource.php` |
| A | `app/Filament/Professor/Resources/AulaResource/Pages/CreateAula.php` |
| A | `app/Filament/Professor/Resources/AulaResource/Pages/EditAula.php` |
| A | `app/Filament/Professor/Resources/CursoResource.php` |
| A | `app/Filament/Professor/Resources/CursoResource/RelationManagers/ModulosRelationManager.php` |

## Interface e fontes

| Op | Arquivo |
|---|---|
| C | `resources/views/filament/pages/gestao-academica.blade.php` |
| C | `resources/css/filament/admin/gestao-academica.css` |
| C | `public/css/app/gestao-academica.css` |
| C | `public/fonts/manrope-latin.woff2` |
| C | `public/fonts/playfair-display-latin.woff2` |
| C | `public/fonts/LICENSES/Manrope-OFL.txt` |
| C | `public/fonts/LICENSES/PlayfairDisplay-OFL.txt` |
| C | `public/fonts/PROVENIENCIA.md` |

## Testes

| Op | Arquivo |
|---|---|
| C | `tests/Feature/Admin/AulaDisciplinaMigrationTest.php` |
| C | `tests/Feature/Admin/AulaDisciplinaTest.php` |
| C | `tests/Feature/Admin/GestaoAcademicaPageTest.php` |
| C | `tests/Feature/Admin/MatriculaResourceTest.php` |
| C | `tests/Feature/Professor/HierarquiaAcademicaProfessorTest.php` |
| A | `tests/Feature/Aluno/FrequenciaAlunoTest.php` |
| A | `tests/Feature/Autorizacao/AulaAlunoAuthTest.php` |
| A | `tests/Feature/Secretaria/AcademicoPolicyTest.php` |
| A | `tests/Feature/Secretaria/DisciplinaTest.php` |
| A | `tests/Feature/Secretaria/FrequenciaTest.php` |
| A | `tests/Feature/Secretaria/MatrizCurricularTeologiaSeederTest.php` |
| A | `tests/Feature/Secretaria/TurmaAcademicaTest.php` |

## Documentação

| Op | Arquivo |
|---|---|
| C | `DECISOES.md` |
| C | `LISTA-ARQUIVOS.md` |
| C | `PENDENCIAS-FUTURAS.md` |
| C | `VALIDACAO.md` |

Total: **59 arquivos** — 29 criados, 29 alterados e 1 removido.
