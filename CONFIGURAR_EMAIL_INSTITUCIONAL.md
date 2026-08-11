# E-mail institucional de confirmação

As inscrições enviam os protocolos ao e-mail informado pelo candidato. O remetente é definido somente no arquivo local `.env`; senhas nunca devem ser adicionadas ao Git.

## Gmail ou Google Workspace

Use esta opção somente se `contato@cetsadp.com.br` for uma caixa de e-mail do Google Workspace ou um alias verificado nessa conta. Ter apenas o domínio não permite que o Gmail envie como esse endereço.

1. Ative a verificação em duas etapas na conta que fará os envios.
2. Abra a conta Google, acesse **Segurança** e crie uma **Senha de app** para “CETSADP LMS”.
3. Copie a senha exibida e preencha o `.env` local:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=contato@cetsadp.com.br
MAIL_PASSWORD="cole-a-senha-de-aplicativo-aqui"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="contato@cetsadp.com.br"
MAIL_FROM_NAME="CETSADP"
```

4. Reinicie o servidor local depois de salvar o arquivo.

## Domínio fora do Google Workspace

Use as credenciais SMTP fornecidas pela hospedagem do domínio. Mantenha `MAIL_FROM_ADDRESS` como o endereço institucional e substitua apenas host, porta, usuário, senha e criptografia pelos dados do provedor.

## Entregabilidade

Antes de publicar, configure SPF e DKIM no DNS do domínio pelo painel do provedor de e-mail. Isso reduz o risco de as confirmações caírem em spam.
