# CETSADP LMS — instruções de contexto

- Stack: Laravel 12 + PHP 8.2+, Filament 3, Blade/Vite/Tailwind; PostgreSQL em produção e SQLite nos testes.
- Leia `AGENTS.md`, mas carregue apenas os arquivos relevantes para a tarefa. Não faça varredura de `vendor/`, `node_modules/`, `storage/`, `backups/` ou `public/build/`.
- Preserve mudanças existentes do usuário; não faça reset/revert amplo.
- A fonte atual de pessoas/alunos é `users` (`nivel = aluno`); a tabela legada `alunos` foi removida.
- `users.nivel` usa minúsculas: `admin`, `secretario`, `professor`, `aluno`.
- `matriculas.status` canônico: `ativo`, `trancado`, `concluido`, `cancelado`; ao ler dados antigos, aceite também as variantes femininas.
- Antes de adicionar pacote, confirme que o Laravel/Filament já não resolve a necessidade.
- Para diagnóstico, prefira buscas direcionadas e diffs pequenos em vez de despejar logs/arquivos inteiros.
- Validação mínima: `composer test`, `npm run check:js` e, quando frontend mudou, `npm run build`.
