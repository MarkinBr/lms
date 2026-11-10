# Testes E2E e CI

## Execução local

Com as dependências instaladas, instale o navegador Chromium uma vez:

```bash
npx playwright install chromium
```

Depois execute:

```bash
npm run test:e2e
```

Por padrão, o Playwright inicia `php artisan serve` em `http://127.0.0.1:8000`. Para testar um servidor já iniciado, informe:

```bash
E2E_BASE_URL=http://127.0.0.1:8000 npm run test:e2e
```

Os testes de contato, newsletter e acompanhamento interceptam as respostas de rede para não enviar e-mails nem gravar dados no banco de desenvolvimento. A persistência e o transporte são cobertos pelos testes de integração do Laravel.

## CI

O workflow `.github/workflows/ci.yml` roda em pull requests e em pushes na `main`. Ele executa:

- lint de sintaxe PHP e Laravel Pint apenas nos PHP alterados;
- testes Laravel direcionados aos fluxos públicos e de inscrição;
- `npm ci`, build Vite e checagem de sintaxe JavaScript;
- Playwright em Chromium com SQLite temporário.

Relatórios, vídeos e traces só são publicados quando o job E2E falha. Nenhum `.env`, banco local, credencial ou arquivo de `tests/e2e/.report` deve ser versionado.
