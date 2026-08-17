# Acesso administrativo por token

O painel `/admin` aceita a autenticação normal do Filament e um fluxo alternativo com uma frase temporária de 24 palavras.

## Como funciona

1. O administrador abre `/admin/login` e escolhe **Entrar com token de 24 palavras**.
2. Informa o e-mail administrativo. A resposta é sempre genérica, exista ou não uma conta elegível.
3. Para uma conta `admin` ativa, o sistema gera 24 palavras com `random_int`, envia a frase ao e-mail e armazena somente um HMAC-SHA-256 dela.
4. A frase vale por 10 minutos, é invalidada quando uma nova é solicitada e só pode ser usada uma vez.
5. Cinco tentativas inválidas consomem a frase. As rotas também têm limitação de requisições e proteção CSRF.

## Regras de segurança

- A frase nunca fica no HTML, no JavaScript, na URL, no banco ou nos logs.
- O login só é permitido para usuário com `nivel=admin` e `ativo=true`.
- Após o sucesso, a sessão é regenerada para evitar fixation.
- O token não substitui a autorização do painel: `User::canAccessPanel()` continua sendo a última barreira.
- Não reutilize a mesma frase nem a encaminhe por canais públicos.

## Operação

Depois de aplicar a migration, confirme o remetente SMTP usado pelo projeto e faça um teste com `Mail::fake()` ou com uma conta administrativa de homologação. A frase é enviada de forma síncrona para que o usuário só prossiga depois da solicitação ter sido processada.
