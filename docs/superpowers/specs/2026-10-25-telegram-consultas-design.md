# Telegram CETSADP — consultas e menu (Issue #86)

## Objetivo e contexto
Ampliar o bot privado existente com navegação inline e consultas acadêmicas limitadas. O pareamento e a autorização exclusiva do administrador configurado permanecem intactos. A base é exatamente a candidata PR #85, commit 680c462; não usar main.

## Abordagens
1. Nutgram: facilita estados conversacionais, mas adiciona dependências desnecessárias para a leitura.
2. Consultas por links para o painel: menor exposição, mas pouco útil para navegação rápida.
3. **Selecionada: botões e callbacks pequenos com HTTP Laravel atual**, listas de cinco itens e encaminhamento ao painel quando necessário.

## Alcance
Menu com áreas alunos, inscrições, matrículas, turmas e aulas; listas paginadas; busca por nome de aluno pelo comando /buscar, ficha somente com identificador, nome, status, matrícula/curso/turma; /notas e /frequencia por ID inequívoco. Usar modelos existentes, seleções explícitas e queries de apenas leitura. Nenhum CPF, telefone, endereço, senha, documento, dado financeiro ou envio para grupos. Links exibidos apenas como instrução para abrir o painel autenticado.

## Segurança e limites
Validar dono ativo e chat privado em todas as callbacks, inclusive após revogação. Callbacks aceitam apenas categoria listada, páginas de 1 a 20 e IDs numéricos limitados; nenhuma SQL dinâmica. Mensagens curtas, no máximo cinco resultados. A infraestrutura existente registra update_id e trata falha do Telegram; não alterá-la. Sem migrações. Testes SQLite com HTTP fake.
