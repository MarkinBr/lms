# Issue #68 — Eventos públicos
A Secretaria já possuía a Agenda Acadêmica (EventoCalendario), usada para turmas. Foi criado recurso independente **Eventos do Site** para evitar publicar informações privadas e preservar agendas acadêmicas.
O site passa a consumir somente registros publicados e futuros. Nenhum exemplo antigo é automaticamente convertido em evento real. URLs de inscrição externas são opcionais e devem apontar a um formulário verdadeiro.
## Executar no repositório
- php artisan migrate --pretend
- php artisan migrate
- php artisan route:list --path=secretaria
- php artisan test --filter=EventosPublicosTest
- vendor/bin/pint --test app/Models/EventoPublico.php app/Filament/Secretario/Resources/EventoPublicoResource.php
- npm run build (se aplicável) e QA em telas 390/1024/1440 px
## Limitações desta entrega
Ferramentas PHP/Composer e navegador local não foram disponibilizadas durante esta alteração por GitHub. Testes e renderização não foram executados aqui; o PR precisa passar CI e verificação visual antes do merge.
A revisão de login identificou simulações na main; o PR #63 (Issue #56) contém a correção proposta para o aluno e permanece aberto.
