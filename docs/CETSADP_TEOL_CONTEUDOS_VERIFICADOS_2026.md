# CETSADP — Matrizes, aulas e materiais do Seminário Médio em Teologia

## Referência curricular

O projeto utiliza `MatrizCurricularTeologiaSeeder.php` como fonte da matriz confirmada:
- 6 períodos curriculares, 40 disciplinas;
- 960 horas cadastradas no LMS (validar a carga oficial antes de emitir documentos);
- a disciplina **História e Cultura Judaica** fica no **2º período**, sob código `TEO-M02-12`, com **16 horas** cadastradas.

Não substituir esta matriz por versões alternativas com Grego Instrumental e Hebraico Instrumental independentes, Língua Portuguesa etc., sem decisão formal da coordenação.

## Conteúdo vinculado nesta alteração

| Disciplina | Período | Código | Conteúdo | Vídeo | PDF/apostila | Publicação |
|---|---|---|---|---|---|---|
| História e Cultura Judaica | 2º | TEO-M02-12 | Aula 1 — leitura de estudo derivada de transcrição existente | Não informado/validado | Não informado/validado no LMS | **Bloqueada**, para revisão |

A leitura foi recuperada de arquivo do CETSADP que já existia. **Não é a videoaula nem a apostila institucional mencionada na transcrição.** O material não deve ser rotulado como aprovado sem revisão.

As outras **39 disciplinas** permanecem na matriz, mas sem novas aulas artificiais: a matriz curricular não especifica roteiros de aula nem links de mídia.

## Importar para um banco homologado

Depois de verificar migrations/backup, executar uma única vez na instalação do Laravel:

```bash
php artisan db:seed --class=Database\\Seeders\\ConteudosTeologiaVerificadosSeeder
```

O seeder primeiro reaplica a matriz de forma idempotente e depois procura a disciplina pelo código, curso e título. Se ela já tiver aulas, preserva tudo e informa que pulou a importação. A aula criada é **não liberada** (`liberada = false`) e não altera notas, matrículas, frequência, turmas ou progresso.

## Vinculação de materiais e vídeos

Para cada arquivo ou link fornecido pela coordenação, validar **curso → período → disciplina → aula**, conteúdo, autoria, autorização de uso, URL reproduzível e acesso pelos alunos. Só então preencher `aulas.video_url`, `aulas.video_provider`, `aulas.pdf_url`, `aulas.materiais` ou `aulas.anexos`.

**Não usar vídeo demonstrativo, apostila genérica ou links externos sem confirmação.** Para a aula de História e Cultura Judaica, o vídeo ainda não foi encontrado entre os arquivos atualmente acessíveis e por isso o campo fica nulo.

## Checklist de homologação

1. Confirmar 6 módulos, 40 disciplinas e cargas horárias cadastradas; comparar com documento acadêmico assinado.
2. Confirmar vínculo da Aula 1 somente a `TEO-M02-12`.
3. Verificar que `liberada = false` e `video_url/pdf_url = null`.
4. Revisar a leitura, cadastrar a mídia correta e a apostila autorizada, e somente depois liberar.
5. Confirmar que nenhuma turma, matrícula, presença ou nota foi modificada.

**Escopo:** a alteração está proposta em uma branch do GitHub. Nada foi executado no banco de dados ou implantado no servidor.
