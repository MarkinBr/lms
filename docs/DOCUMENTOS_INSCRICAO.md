# Documentos de inscrição — armazenamento privado (Issue #50)

## Disco privado

- Documentos de inscrição (RG, CPF, comprovante, histórico, certificado, foto
  3x4, outro) são gravados no disco lógico de `config('filesystems.applications_disk')`.
- O padrão é `applications`, mapeado em `config/filesystems.php` para
  `storage/app/private/applications` (visibilidade `private`, sem symlink em `public/`).
- Se `APPLICATIONS_DISK` estiver vazio ou for `public`, o upload falha com 500
  registrado — nunca degrada silenciosamente para disco público.

## S3 / Cloudflare R2

Ajuste apenas o `.env`:

    APPLICATIONS_DRIVER=s3
    APPLICATIONS_S3_KEY=...
    APPLICATIONS_S3_SECRET=...
    APPLICATIONS_S3_REGION=...
    APPLICATIONS_S3_BUCKET=...
    APPLICATIONS_S3_ENDPOINT=...   # opcional, para R2

## Download autorizado (somente API)

- `GET /api/v1/applications/{application}/documents/{document}` (Sanctum).
- **Não há rota web de download.** O download é exclusivamente API; o contrato
  atual do portal do aluno não consome esses arquivos por rotas web.
- Regras (`App\Policies\ApplicationDocumentPolicy@download`, autodiscovery —
  o projeto não tem `AuthServiceProvider`):
  - usuário inativo: sempre negado;
  - `secretario` / `admin` ativos: qualquer documento de inscrição;
  - candidato: só documentos da inscrição cujo `email` é o seu.
- Terceiros, documento de outra inscrição na URL, inexistente ou caminho
  adulterado (path traversal) recebem 403/404 sem metadados, caminho ou PII.
- Nenhum JSON (upload, `show`, `index`, acompanhamento público) expõe
  `caminho_arquivo` ou `url_publica`: ambos estão em `$hidden` do model e a
  coluna `url_publica` é removida pela migration
  `2026_08_14_000001_drop_url_publica_from_application_documents_table`.
