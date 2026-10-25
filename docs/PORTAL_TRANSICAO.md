# Novo portal em paralelo — Issue #84

Esta entrega prepara uma candidata e uma homologação privada. Não substitui o legado, não muda DNS e não autoriza desligar os portais dos cursos. O bot permanece sem credenciais/webhook neste ambiente.

## Acesso dos alunos

- Matrícula permanente e senha compatível com a origem; CPF pode ser alternativa quando não houver ambiguidade.
- E-mail pessoal é opcional para estudar. Login e recuperação por e-mail exigem confirmação.
- Cadastro/troca de e-mail exige senha atual; o endereço anterior só é substituído depois do link válido, que expira em 60 minutos e é de uso único.
- Contas sem credencial utilizável exigem atendimento da secretaria, sem senha padrão ou senha derivada do CPF.
- Novos alunos aprovados devem concluir a ativação individual antes de usar o e-mail. A matrícula continua válida após confirmação ou troca do endereço.

## Importação

`php artisan portal:importar-legado` executa uma prévia em transação desfeita. `--executar` mantém os registros no destino. A prévia não mantém linhas, mas sequências PostgreSQL podem reservar IDs; IDs contíguos não são um critério de integridade.

O comando é recusado em produção. Em `homologacao`, exige os bancos separados `portal84_lms` e `portal84_legado`. A conexão `legado` lê a origem em transação somente leitura. Não usar os importadores antigos `cets:importar` e `importar:alunos` para este banco.

`legado_mapeamentos` mantém IDs textuais da origem, destino e impressões dos campos gerenciados. Reexecução preserva edições locais e alterações da origem são relatadas para reconciliação. Código de saída 0 indica execução/prévia sem conflitos; 2 indica conflitos; 1 indica falha. Conflito em um grupo de vínculos interrompe esse grupo, impedindo criar outra matrícula para contornar uma matrícula cancelada.

Vínculos das duas tabelas antigas são reconciliados por aluno/curso. Nunca são simplesmente somados. CPF e e-mail coincidentes não bastam para vincular automaticamente identidades existentes. Cursos existentes exigem correspondência explícita por origem; nenhum vínculo é inferido apenas pelo nome.

Aulas conservam disciplina não classificada até a secretaria conferir a estrutura acadêmica. Organização técnica em módulo de transição não define carga horária nem cria avaliação. Materiais ligados a aulas bloqueadas não aparecem na listagem do curso. Histórico de presença/sessões fica em `legado_registros`, sem rota pública e sem converter eventos brutos em notas ou frequência oficial. Campos de credenciais são removidos também em JSON aninhado.

## Release e homologação

Use `scripts/portal-homologacao.py package --repo <checkout> --output <diretorio>/portal84-<versao>.tar.gz` para transportar código a partir de `git archive`. Não monte o TAR percorrendo uma extração no Windows: as pastas `API` e `Api` precisam manter as letras originais para funcionar no Linux. O pacote exclui ambientes, bancos, dumps, uploads, dependências e caches locais.

Na VPS, extrair somente em uma pasta NOVA sob a raiz privada `portal84`. O arquivo privado fora da release contém somente APP_KEY exclusiva da homologação, DB_PASSWORD aleatória forte e PORTAL84_TAG; não guardar credenciais no Git ou imprimir o Compose resolvido. Executar o validador antes de construir/iniciar serviços.

O Compose usa projeto, volumes e rede próprios; somente a porta web em `127.0.0.1:8874` é publicada. Web e worker usam a mesma imagem e compartilham `storage/app`. O worker consome exclusivamente a fila de homologação, nunca jobs padrão restaurados. O scheduler é apenas heartbeat: não executa cobranças, agenda ou notificações reais. A rede interna bloqueia saída do aplicativo. O navegador ainda pode carregar vídeo/material externo conforme seu endereço original.

Executar explicitamente o modo `migrate` antes de web/worker. Falha remove o marcador de sucesso dessa versão e impede a inicialização. Restaurar somente cópias dos bancos no PostgreSQL próprio, nunca sobrescrever o banco real. Copiar arquivos apenas dentro da VPS, para armazenamento próprio, sem montagem de volumes de produção com escrita.

O acesso inicial é privado, por túnel SSH. `MAIL_MAILER=array` permite testes sem entrega externa e não oferece uma caixa postal persistente. A confirmação pública depende de validar o serviço de envio já existente antes de abrir aos alunos; nenhum serviço pago foi contratado.

## Validação e limites

As evidências de execução ficam fora do Git. O baseline completo identifica falhas preexistentes em `tests/Feature/Deploy/PatchValidationTest.php`, que pressupõe patches financeiros/schema PostgreSQL antigos; elas devem ser relatadas separadamente de regressões desta candidata. Nenhuma regra financeira foi alterada para ocultar essas falhas.

O ensaio privado não substitui a validação de operação em produção. Antes de abrir ao público: conferir conflitos, atribuições acadêmicas, envio de e-mail, arquivos e permissões; repetir backups dos dois bancos, uploads e configuração; reconciliar alterações ocorridas no legado desde o snapshot. Não restaurar backup antigo sobre um sistema que continuou recebendo dados.

Se houver falha no piloto, interromper apenas seu acesso e preservar o legado e seus volumes. Não usar `docker compose down -v`, prune ou rollback de migrações como substituto do plano de retorno.

## Piloto público autorizado em 25/10/2026

O piloto usa `https://novo.cetsadp.com.br`, certificado próprio e o override `docker/portal-piloto.compose.yml`. O vhost em `docker/portal-piloto.nginx.conf` mantém o site e portais antigos intactos. Somente o web participa da rede interna de borda compartilhada com o Nginx; o banco continua exclusivo da rede isolada. O Nginx resolve o container pelo DNS Docker, evitando IP fixo após recriação.

Web e worker usam ambiente `production`, debug desativado e cookie seguro exclusivo. O scheduler continua apenas como heartbeat isolado. O piloto conserva banco próprio: alterações nele não são sincronizadas com o legado. A conta sintética de demonstração é desativada antes da exposição. Contas, senhas e registros reais não são publicados em logs ou documentos.

Sem serviço de envio validado, recuperação e confirmação de e-mail respondem com mensagem de indisponibilidade, em vez de simular entrega. Cobranças, administração, APIs e exclusão de conta não são expostas pelo vhost piloto. O financeiro continua no sistema atual. Os sete conflitos da origem permanecem preservados para conferência; o piloto não representa uma substituição definitiva.

O certificado HTTP-01 usa `scripts/portal-acme-hook.py` na VPS; o deploy-hook de renovação valida e recarrega somente o Nginx. Backups de bancos, arquivos e configuração permanecem privados na VPS. O retorno consiste em retirar apenas o novo vhost e recarregar o Nginx, sem restaurar bancos nem parar o legado.

A rede adicional do Nginx também foi declarada no Compose existente da VPS, preservando as demais configurações de serviços, para sobreviver à recriação do container. O piloto aceita o cabeçalho de IP real somente das redes oficiais Cloudflare em `docker/portal-cloudflare-ips.inc`; isso evita agrupar todos os alunos no limite de login do mesmo proxy. As listas têm suas fontes oficiais registradas no arquivo.

Publicação verificada por HTTPS: login, dashboard, sala de aula e cookie seguro. A conta sintética de aceitação foi desativada após o teste; a credencial permaneceu exclusivamente na VPS. O timer de renovação do Certbot está habilitado. E-mail, financeiro, sincronização com o legado e os sete conflitos continuam sujeitos às limitações acima.
