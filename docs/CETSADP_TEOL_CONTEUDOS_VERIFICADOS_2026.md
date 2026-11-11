# CETSADP — organização de aulas, vídeos e materiais (importação 2026)

## Fontes de referência

1. **Matriz curricular confirmada de Teologia:** `database/seeders/MatrizCurricularTeologiaSeeder.php`: 6 períodos, 40 disciplinas, 960 horas cadastradas (a homologar), incluindo **História e Cultura Judaica**, código `TEO-M02-12`, **2º período / 16 horas**.
2. **Acervo exportado do painel CETSADP:** `database/seeders/dados/cetsadp-videos-materiais-20261111.json`, extraído em 11/11/2026, contendo **17 vídeos** e **12 materiais** (11 links HTTPS válidos; 1 entrada inválida).
3. **Leitura de Judaica:** `database/seeders/conteudos/teologia/historia-cultura-judaica-aula-01.html`, derivada de transcrição já presente nos arquivos disponibilizados. **Pendente de revisão acadêmica.**

Não substituir a matriz confirmada por versão alternativa com Grego Instrumental, Hebraico Instrumental e Língua Portuguesa independentes.

## Mapeamento conservador

### Gravações e encontros

| Curso do LMS | Vídeos identificados | Vínculo curricular disponível |
| --- | ---: | --- |
| Habilitação ao Diaconato | 7 | O vídeo da **Introdução à Bibliologia** identifica o tema; os demais permanecem no acervo de encontros do curso, pois não é possível identificar a disciplina de cada um apenas pelo título. |
| Liderança Cristã Ministerial | 6 | Títulos Aula 1–6 e datas, **sem disciplina curricular confirmada**. |
| Curso Médio em Teologia | 4 | A gravação “A História do Povo Hebreu” (ID original 20; YouTube `S7s27o1xXps`) foi vinculada tematicamente à **História e Cultura Judaica / 2º período / Aula 1**. As demais gravações permanecem gerais, pois seus títulos não identificam disciplina. |
| **Total** | **17** | Nenhuma turma, professor, frequência ou nota foi inventada. |

Os registros `encerrada` são criados como encontros `realizada`, com URL de gravação; os marcados `programada` são preservados como `agendada`, mesmo que a data extraída já tenha passado. **O status do painel não comprova que o YouTube ainda reproduz o vídeo.** A classificação por disciplina desses encontros deve ser conferida antes de vincular às aulas didáticas individuais.

### Materiais

| Quantidade | Curso | Classificação de conteúdo |
| ---: | --- | --- |
| 2 | Teologia | Ementa e material de **História e Cultura Judaica**; disciplina `TEO-M02-12`. Links associados à Aula 1 como **material da disciplina**, não como afirmação de correspondência exata com a gravação. |
| 2 | Diaconato (sugestão) | Slides + apostila de **Primeiros Socorros**, organizados tematicamente em **Atividades Práticas**. Confirmar curso antes de publicar. |
| 4 | Diaconato (sugestão) | **Três Eixos de Sustentação**, **Declaração de Fé**, **Avivamentos**, **Identidade/Missão da Igreja**, agrupados tematicamente no **Núcleo Comum**. Confirmar curso antes de publicar. |
| 1 | Diaconato | **Manual Diaconato** (documento de referência). |
| 1 | Diaconato | Vídeo geral “Curso de Habilitação ao Diaconato” (link separado das 7 aulas do calendário). |
| 1 | **Pendente** | PDF “Aula Inaugural”: título não informa a qual dos três cursos pertence. **Não atribuir por suposição.** |
| 1 | **Descartado** | Registro de teste `jkhj`, endereço `kjhkhj`, **URL inválida**. |
| **12** | | **11 catalogados, 1 ignorado por URL inválida**. |

Os títulos de 6 materiais não indicam curso: foram colocados provisoriamente no grupo Diaconato por **afinidade temática** com a matriz compartilhada, mas estão marcados `sugestao` e o sistema **impede sua publicação até a classificação ser confirmada**. As URLs do Drive foram extraídas do HTML administrativo, mas **permissões, existência e autorização de uso não foram verificadas**. Os demais materiais também ficam **não publicados** até aprovação.

## O que será modificado ao aplicar em ambiente de homologação

- Novo `AcervoDidatico` com associação opcional a curso/disciplina/aula, classificação e indicador de publicação; a administração pode revisar itens no menu **Acadêmico → Acervo didático**.
- O seeder `AcervoCetsadp2026Seeder` registra 17 eventos/links no `aulas_ao_vivo`, sem criar turmas nem vincular disciplinas não comprovadas.
- A **Aula 1 de História e Cultura Judaica** recebe a gravação sobre o povo hebreu e os dois links do material da disciplina, sem perder a leitura existente; a aula permanece **bloqueada até liberação acadêmica**.
- O portal do aluno exibe gravações de encontros **realizados** na página do curso para quem possui matrícula; exibe também materiais do curso **somente se `publicado=true`**. A tela da aula passa a mostrar vídeos de aulas do tipo `misto` e links de `aulas.materiais`.
- A importação é **idempotente e não destrutiva**: usa `firstOrCreate`, não sobrescreve vídeos que já tenham sido cadastrados nas aulas e não altera matrículas, históricos, notas, frequência nem conclusão dos alunos.

## Sequência de homologação

Faça backup antes de executar no servidor de testes:

```bash
php artisan migrate
php artisan db:seed --class=AcervoCetsadp2026Seeder
```

Confirme no banco **6 módulos, 40 disciplinas e 960 horas cadastradas em Teologia**; **17 encontros do acervo** (sem contar registros anteriores); **11 materiais catalogados** e **Aula 1 de Judaica com 1 vídeo e 2 links de material**, inicialmente bloqueada. Reexecute o seeder para verificar que os totais não aumentam. Verifique player, links e permissões do Google Drive antes de publicar o conteúdo.

Não execute `migrate:fresh` ou o `DatabaseSeeder` demonstrativo em banco de produção. O seeder de acervo é invocado isoladamente.

**Estado desta alteração:** código e protótipo preparados na branch de revisão; não houve execução de migrations, importação ou implantação no banco de dados do usuário.


## Atualização de 11/11/2026 — identificação por transcrições

A coordenação enviou as transcrições completas dos encontros #26, #32 e #36. A identificação curricular não depende mais dos títulos genéricos do painel:

| ID no painel | Data da aula | Vídeo | Disciplina comprovada pelo conteúdo | Período da matriz | Ordem dentro da disciplina |
|---|---|---|---|---|---|
| #20 | 17/05/2026 (painel) | `S7s27o1xXps` | História e Cultura Judaica (já vinculada por transcrição anterior) | 2º — `TEO-M02-12` | Aula 1 |
| **#26** | 20/06/2026 | `NOsvbWaaaus` | **História e Cultura Judaica**. Professor diz expressamente que é a segunda e última aula sobre a história judaica; retoma a divisão dos reinos. | **2º — `TEO-M02-12`** | **Aula 2** |
| **#32** | 15/08/2026 | `5Osxg5CvQ2w` | **Teologia Bíblica do Antigo Testamento**. A exposição trata da criação, da revelação divina e da relação entre a teologia do Antigo Testamento e a Teologia Sistemática. Embora examine palavras hebraicas, não é um curso separado de línguas. | **1º — `TEO-M01-01`** | **Aula 1** |
| **#36** | 17/10/2026 | `pQ8aokW5-Ls` | **Teologia Bíblica do Novo Testamento**. Professor menciona repetidamente essa disciplina, explica sua natureza, métodos, exegese e revelação progressiva. Não é apenas Novo Testamento I (Evangelhos). | **1º — `TEO-M01-02`** | **Aula 1** |

Trechos de evidência do material recebido:
- #26, início **00:39–04:22**: identificada a disciplina **História e Cultura Judaica** e anunciada a segunda aula; em **04:59–05:31**, retomada do tema anterior e divisão do reino.
- #32, em **26:17–30:13**: apresentação da Teologia do Antigo Testamento e sua relação com a Teologia Sistemática; os primeiros minutos tratam de verbos hebraicos associados à criação.
- #36, em **01:04:47–01:05:53**, **01:17:07–01:20:43** e **01:58:12–01:59:08**: Teologia do Novo Testamento enquanto disciplina e introdução teológica.

**Importante: o número “Aula 4”/“Aula 5” é o ordinal do calendário do antigo painel, não da disciplina.** No LMS cada um desses encontros é a primeira aula da sua própria matéria, pois não havia material anterior da mesma disciplina identificado.

A nova classe `VincularTranscricoesTeologia2026Seeder` é executada pelo importador `AcervoCetsadp2026Seeder`, depois de criar o catálogo. Ela cria **3 aulas curriculares adicionais**, todas bloqueadas para revisão, com vídeo original, resumo acadêmico e materiais da disciplina quando houver. Os dois PDFs de Judaica são colocados também na **Aula 2**, rotulados explicitamente como material compartilhado de disciplina, sem afirmar que correspondem somente à segunda gravação. Nenhum PDF de outra matéria foi associado por aproximação.

**Inconsistências a homologar, sem modificar automaticamente o currículo:** no encontro de Judaica #26 o expositor menciona **8 horas**, enquanto a matriz atual registra **16 horas** para a disciplina; no encontro #32 são mencionadas **930 horas** para o curso, e em #36 aparecem referências a outros totais, enquanto o seeder mantém **960 horas**. A coordenação acadêmica deve validar formalmente a carga total e as cargas disciplinares antes da emissão de documentos.

**Condição da alteração:** todos os vínculos acima estão preparados em **PR de revisão**, não executados no banco de produção; as URLs e as transcrições foram fornecidas pela coordenação e a disponibilidade pública dos vídeos e PDFs ainda não foi validada por acesso externo.
