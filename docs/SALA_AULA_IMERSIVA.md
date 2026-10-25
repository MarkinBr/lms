# Sala de aula imersiva — Issue #76

A rota real `/portal/aula/{id}` agora abre uma sala dedicada ao estudo, sem carregar a navegação geral nem `premium-classroom.js`. A identidade usa a marca existente do CETSADP, marinho, dourado, Inter e Libre Baskerville locais.

## Experiência

- Computador: percurso do curso à esquerda, vídeo/PDF/leitura no centro e caderno pessoal à direita. O caderno pode ser recolhido; modo foco amplia o conteúdo e oculta as duas colunas.
- Até 1180 px: Aulas / Estudar / Caderno alternam áreas completas. Não há três colunas comprimidas. Controles por toque, foco visível, abas com setas/Home/End e movimento reduzido.
- Vídeo direto com controles nativos, YouTube/Vimeo com os controles oficiais. Retomada neste navegador, referências de tempo e picture-in-picture quando suportado. Sem reprodução automática.
- PDF.js carregado sob demanda: ajuste à largura, zoom, paginação, camada de texto selecionável e grifos. PDFs externos precisam permitir CORS; existe abertura do original. PDFs escaneados sem texto permitem referência de página, mas não seleção/OCR.
- Leitura com fonte ajustável, conteúdo sanitizado e grifos. Materiais, áudio, blocos, exercícios e comentários usam os dados existentes.

## Caderno e compatibilidade

A migração `2026_10_25_000001_create_cadernos_aula_table.php` adiciona apenas a tabela `cadernos_aula`, com chave única aluno/aula, conteúdo JSON e revisão. Não altera o conteúdo de tabelas existentes.

O texto continua espelhado em `progressos.notas` como texto simples. Os grifos e referências ficam separados no JSON; as anotações anteriores não são convertidas ou apagadas. O leitor também reconhece o envelope de caderno da referência fornecida, caso já tenha sido usado.

O salvamento exige matrícula válida, aula liberada, perfil de aluno e CSRF. O servidor deriva o aluno da sessão. Revisão antiga ou texto alterado por um editor legado retorna HTTP 409 e preserva a versão mais recente. A interface mantém o rascunho, informa o conflito e permite exportar; não promete sincronização em tempo real. Para comparar após conflito, exportar o rascunho e recarregar. A recuperação oferece escolha entre o rascunho e a conta.

Limites: 10.000 caracteres de anotação, 150 grifos, 150 referências e 500 KB por caderno no servidor. O rascunho local é separado por aluno/aula e removido após confirmação do salvamento. Em navegador compartilhado, um rascunho pendente permanece no armazenamento local até recuperação ou limpeza.

Os exercícios agora enviam respostas para correção no servidor; o score informado pelo cliente é ignorado. Gabaritos não são enviados na configuração da tela. A política de comentários é verificada no servidor, inclusive o vínculo das respostas à aula.

## Dependências e fontes

Os recursos de leitura foram adaptados do pacote `cetsadp-nova-sala-de-aula.zip` fornecido pelo usuário, com nova identidade, controles responsivos, integração de caderno e validação no Laravel completo. Os outros HTMLs foram tratados como referências; seus scripts externos, rastreadores e conteúdo demonstrativo não foram instalados no portal.

- [PDF.js 4.10.38 — Mozilla](https://github.com/mozilla/pdf.js/tree/v4.10.38), Apache-2.0. Distribuição local com worker, CMaps e fontes. Avaliação dinâmica desativada (`isEvalSupported: false`).
- [DOMPurify 3.4.17 — Cure53](https://github.com/cure53/DOMPurify/releases/tag/3.4.17), Apache-2.0 ou MPL-2.0. Atualizado a partir da versão antiga fornecida na referência. Licenças preservadas em `public/vendor/estudio`.
- Tipografia, ícones e marca reutilizam `public/vendor/portal` e `public/assets/portal`, da Issue #74.

Não há dependência de CDN para estilos, leitor PDF ou sanitização. Os SDKs de YouTube/Vimeo são requisitados somente para aulas desses provedores. A entrega não exige compilação Vite para os novos arquivos estáticos.

## Verificações executadas

- Regressão de Aluno/Auth/Autorizacao/Unit: 104 testes aprovados (545 asserções); teste adicional de isolamento entre dois alunos da mesma aula: 1 aprovado (7 asserções). Total distinto: 105 testes, 552 asserções.
- Pint nos arquivos PHP alterados; Biome 2.6.3 em JS/CSS sem erros; `node --check`; `git diff --check`; `view:clear` e `view:cache`.
- Navegador na aplicação Laravel com SQLite de homologação separado, aluno e conteúdo sintéticos: vídeo, salvamento e recuperação das notas, PDF com camada de texto, grifo amarelo, paginação, referência de página, navegação móvel, modo foco e retorno ao caderno. Exercício incorreto retornou 0% pelo servidor.
- Sem rolagem horizontal em 320, 390, 768, 1024 e 1440 px nos estados examinados. Controles próprios visíveis com rótulos; sem erros ou avisos de console no estado auditado. Isso não equivale a certificação completa de acessibilidade ou testes em todos os dispositivos físicos.
- A demonstração autenticada usa aluno sintético, banco separado e cookie de sessão exclusivo na porta 8776. O portal local continua exigindo perfil de aluno; nenhum perfil ou matrícula real foi convertido para a revisão visual.

## Instalação e rollback

Seguir Issue → branch → PR. Esta branch depende do redesign geral da Issue #74 / PR #75. Aplicar a migração antes da disponibilização do caderno e recompilar as views no ambiente de destino. Sem migração, a tela pode abrir e as notas antigas são lidas, mas o salvamento do novo caderno retorna 503 com orientação para exportar.

No ambiente local verificado (APP_ENV=local, SQLite dentro do projeto), foi criado backup consistente via `VACUUM INTO` na pasta privada `storage/app/portal-redesign-backups` e aplicada somente essa migração. Banco, backup e arquivos de homologação são ignorados pelo Git e não entram no pacote.

Para rollback visual, reverter os commits deste PR e recompilar views. Manter a tabela dos cadernos para preservar os grifos; não executar `migrate:rollback` automaticamente, pois o `down` remove essa tabela. O texto simples continua disponível para a sala anterior. Produção não foi publicada nesta tarefa.
