# Sala de aula imersiva — Issue #76

A rota real `/portal/aula/{id}` agora abre uma sala dedicada ao estudo, sem carregar a navegação geral nem `premium-classroom.js`. A identidade usa a marca existente do CETSADP, marinho, dourado, Inter e Libre Baskerville locais.

## Experiência

- Computador: percurso do curso à esquerda e vídeo/PDF/leitura no centro. O caderno começa recolhido para ampliar a aula. Aulas e Caderno abrem e fecham separadamente pelos botões do cabeçalho ou de cada painel; a escolha fica lembrada neste navegador. Modo foco continua disponível.
- Até 1180 px: Aulas / Estudar / Caderno alternam áreas completas. Não há três colunas comprimidas. Controles por toque, foco visível, abas com setas/Home/End e movimento reduzido.
- Vídeo direto com controles nativos, YouTube/Vimeo com os controles oficiais. Retomada neste navegador, referências de tempo e picture-in-picture quando suportado. Sem reprodução automática.
- PDF.js carregado sob demanda: ajuste à largura, zoom, paginação, camada de texto selecionável e grifos. PDFs externos precisam permitir CORS; existe abertura do original. PDFs escaneados sem texto permitem referência de página, mas não seleção/OCR.
- Leitura com fonte ajustável, conteúdo sanitizado e grifos. Materiais, áudio, blocos, exercícios e comentários usam os dados existentes.

## Correções de vídeo e estudo simultâneo — Issue #78

O vídeo ocupa a largura útil da sala e, no celular, chega às bordas da área de estudo. O título e os espaços no celular foram compactados. O contêiner intermediário do YouTube também ocupa a área inteira, sem limitar o iframe à altura de uma linha.

O botão **Vídeo + material** conserva o mesmo player e fixa o vídeo acima de Leitura, PDF ou Transcrição. Não recria o elemento, não reinicia a reprodução e não exige modo foco. **Desafixar vídeo** fica sobre o player para encerrar esse modo durante a rolagem. O leitor PDF calcula a largura descontando o padding real e reserva espaço para a barra de rolagem, evitando deslocamento horizontal no ajuste inicial.

**Vídeo flutuante** tenta Picture-in-Picture nativo no clique para vídeos diretos, quando o navegador permite. Se indisponível ou recusado, ou para embeds YouTube/Vimeo, abre um miniplayer dentro da própria página. O estado ativo, a mensagem e os controles Fechar flutuante / Voltar ao vídeo são explícitos; Escape também fecha o miniplayer. A alternativa dentro da página não acompanha a troca para outro aplicativo. A janela nativa depende do navegador e do provedor.

### Cadastro da transcrição

A migração aditiva `2026_10_25_000002_add_transcricao_video_to_aulas_table.php` adiciona `aulas.transcricao_video`, opcional. O campo **Transcrição e legendas do vídeo** está tanto na edição de aulas do Professor quanto nas aulas da Disciplina no administrativo. Aceita texto simples ou conteúdo de um arquivo WebVTT, até 500.000 caracteres e 5.000 trechos (limites corrigidos na Issue #80). Conteúdo acima desses limites gera aviso em vez de exibir uma versão cortada. O parser descarta tempos inválidos e blocos NOTE/STYLE/REGION.

Texto simples aparece sem horários. WebVTT habilita busca, salto por trecho, indicação do trecho atual e legendas CC no vídeo direto. Nos embeds, o texto cadastrado continua disponível na aba e o player de origem controla suas próprias legendas; o YouTube recebe a preferência de português e CC quando existentes. Não há extração de legendas de terceiros nem transcrição automática de áudio. Sem cadastro, a tela informa que a transcrição ainda não foi disponibilizada. A leitura da aula não é usada como se fosse transcrição.

Referências: [WebVTT — W3C](https://www.w3.org/TR/webvtt1/), [Picture-in-Picture — MDN](https://developer.mozilla.org/en-US/docs/Web/API/HTMLVideoElement/requestPictureInPicture), [parâmetros oficiais do YouTube](https://developers.google.com/youtube/player_parameters).

### Validação da Issue #78

- 123 testes aprovados, 595 asserções: Aluno, Auth, Autorizacao, cadastro/migração de AulaDisciplina e Unit, com SQLite em memória, mail/cache/sessão array e fila síncrona. Inclui transcrição persistida, parsing de WebVTT, tempos inválidos e ausência de transcrição.
- Pint nos sete arquivos PHP alterados/novos; Biome em JS/CSS sem erros; sintaxe JS; compilação Blade; diff sem erros.
- Navegador na porta 8776, banco e aluno sintéticos: reprodução contínua ao alternar para leitura e PDF, vídeo fixo a 64 px do topo no celular durante rolagem, fechamento manual das duas laterais, busca e salto para 10 segundos na transcrição, ativação de CC e miniplayer com retorno ao vídeo. O navegador de homologação não concedeu PiP nativo; a alternativa dentro da página foi exercitada.
- Larguras 320, 390, 768, 1024 e 1440 px sem overflow da página. A 1440 px, com percurso aberto e caderno recolhido, o player passou de aproximadamente 805 para 1151 px. No celular a 390 px, passou de aproximadamente 345 para 375 px, descontando a barra de rolagem do navegador.
- Migração aplicada no SQLite local após backup consistente privado; nenhuma transcrição de aula real foi preenchida. Homologação usa trechos fictícios identificados na própria interface. Não houve publicação em produção.

### Transcrição do vídeo original — Issue #80

A aula local 1 usa o vídeo `S7s27o1xXps` e estava sem `transcricao_video`. O player do YouTube disponibiliza CC, mas os parâmetros do iframe não importam o texto para a aba do LMS. A transcrição foi lida da interface **Mostrar transcrição** do próprio vídeo, cuja legenda é identificada como **Português (gerada automaticamente)**. Não há serviço externo de transcrição nem dependência de API interna do YouTube em produção.

Foram preservados 1.219 trechos, com inícios de 0:00 até 3:16:06, e gerado WebVTT de 161.697 caracteres. Os horários têm a precisão de segundos exibida pela interface. Como a interface consultada não fornece finais de cada fala, o arquivo usa o próximo início como limite de navegação e uma janela de oito segundos no último trecho. Isso não substitui as legendas oficiais do player nem representa alinhamento fonético exato. O texto automático foi mantido sem revisão editorial.

O limite antigo de 100.000 caracteres cortava essa aula. Agora o parser preserva a transcrição inteira dentro dos limites explícitos; formulários usam a mesma constante. Os horários após uma hora usam `h:mm:ss`. A indicação do trecho atual atualiza somente o botão anterior e o novo, evitando milhares de escritas no DOM a cada meio segundo.

A importação local foi feita após backup SQLite consistente e somente quando o campo estava vazio e a URL do vídeo correspondia exatamente. A comparação com o backup confirmou alteração apenas em `aulas.transcricao_video`, na aula 1. Na homologação isolada, a aula 2 passou a usar o vídeo e a transcrição reais; cadernos, grifos e progresso foram preservados. Arquivos de origem e WebVTT ficam privados em `storage/app/transcricoes`, ignorados pelo Git. Eles não são publicados no PR. Demais vídeos continuam exigindo transcrição cadastrada; não foi criado um importador automático genérico.

Validação: três regressões primeiro falharam (aula longa cortada; excesso de caracteres e de trechos sem aviso) e depois passaram. Os seis testes de transcrição passaram. Navegador confirmou 1.219 botões, busca no texto original, formato `3:16:06`, player YouTube e salto para o trecho em 1:03:55 com indicação subsequente do trecho atual. Pint, Biome e sintaxe JS verificados.

A execução completa com SQLite em memória terminou com **385 testes aprovados e 20 falhas, 1.963 asserções**: 19 em `tests/Feature/Deploy/PatchValidationTest.php` (auditoria de patches financeiros/deploy, consultas PostgreSQL e tabelas ausentes nesse teste sem migração) e uma em `tests/Feature/ExampleTest.php` (GET `/` sem preparar `pagina_inicials`). Esses arquivos e o código financeiro não foram alterados nesta correção. A suíte completa não está verde; detalhes ficam no log privado `storage/app/sala76-qa/issue80-testes-completos.log`.

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
