# Central da Secretaria — Gestão de Acessos

Entrada: `/secretaria/central`. O painel da Secretaria possui o item **Central de atendimento**. É necessário entrar com uma conta ativa de administrador ou secretário. Alunos e professores não acessam esta central.

## Atendimento

1. Abra **Acessos dos alunos**, busque o aluno e confira a identidade pela ficha. As abas separam cadastro, segurança, inscrições/cursos e histórico.
2. Para corrigir dados, use **Editar cadastro**, informe motivo e sua senha, revise o antes/depois e confirme. Correção de e-mail aguarda confirmação do próprio aluno na nova caixa postal.
3. Para cadastrar novo acesso, escolha o canal, confira contato e dados, revise e confirme. Isso não cria matrícula em curso nem cobrança.
4. Em **Acesso e segurança**, escolha ativação, recuperação ou senha temporária. A tela seguinte exibe a mensagem uma única vez; confira novamente o destinatário antes de confirmar.
5. WhatsApp abre um rascunho oficial. A equipe envia no aplicativo. O histórico registra a abertura solicitada, sem afirmar envio, entrega ou leitura. Não há custo de API de WhatsApp.
6. E-mail é enfileirado e revalidado antes do envio. A aceitação pelo transporte aparece no histórico e não comprova chegada à caixa postal.

A senha temporária contém oito dígitos, dura 30 minutos, admite cinco erros e só permite escolher uma senha definitiva. Também pode ser entregue presencialmente ao próprio aluno. Emitir outra credencial invalida a anterior e a senha antiga. Não anote códigos, tokens ou senhas no motivo do atendimento.

Bloquear impede o login. Desbloquear libera a conta, preservando eventuais pendências de ativação. Encerrar sessões exige nova autenticação nos dispositivos do aluno. Essas ações não alteram sua situação acadêmica.

## Preparação da versão

As migrações `2026_10_30_000010`, `000011` e `000012` são aditivas: campos complementares, auditoria, entregas cifradas, operações idempotentes e credenciais. O retorno de código não apaga automaticamente esses dados. Faça backup e valide a migração em cópia isolada antes da implantação.

O worker precisa consumir a conexão **database** e a fila definida em `queue.connections.database.queue` (`DB_QUEUE`, normalmente `default`). No piloto Docker existente, a fila é `portal84-homologacao`. Um worker que consome somente Redis não processa estas entregas. O transporte de e-mail real deve estar configurado; `log`/`array` são recusados fora de testes. Preserve a `APP_KEY`, necessária para abrir conteúdo já cifrado.

O scheduler normal executa `acessos:limpar-entregas` a cada minuto para apagar o conteúdo reservado vencido. O histórico é preservado. Em homologação, execute somente esse comando diretamente: a agenda completa também contém rotinas financeiras reais.

As fotos enviadas pela central ficam no disco privado `local`, em `acessos/fotos`. O Portal do Aluno usa endpoint autenticado para a própria foto; a Secretaria possui endpoint separado autorizado. Preserve esse storage ao trocar releases.

## Validação

Os testes de `tests/Feature/Secretaria/*Acesso*Test.php` e `AtendimentoSeguroHttpTest.php`, junto com `tests/Feature/Site/InscricaoCamposComplementaresTest.php`, usam o ambiente de teste isolado. O teste `tests/infra/secretaria_acessos_postgres.php` só aceita PostgreSQL descartável `cetsadp_telegram_ci` em `APP_ENV=testing`; verifica disputas entre processos sem enviar mensagens reais.

Nenhum endpoint desta função administra credenciais de administradores, secretários ou professores. Inscrição original e dados acadêmicos são consultados pelo vínculo explícito da matrícula.
