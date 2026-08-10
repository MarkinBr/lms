# 📋 Guia de Teste - Módulo de Inscrições CETSADP

## ✅ O que foi implementado

### Backend (Laravel 12.x)

1. **Models**
   - `Application` - Inscrição de candidatos
   - `ApplicationDocument` - Documentos da inscrição

2. **Migrations**
   - `2026_08_10_000001_create_applications_table` - Tabela principal
   - `2026_08_10_000002_create_application_documents_table` - Documentos
   - `2026_08_10_000003_add_protocol_to_applications_table` - Protocolo único

3. **Controller API**
   - `ApplicationController` (Api/V1)
     - `POST /api/v1/applications` - Criar inscrição (público)
     - `GET /api/v1/applications/by-protocol/{protocol}` - Consultar por protocolo (público)
     - `GET /api/v1/applications` - Listar todas (Secretaria/Admin)
     - `GET /api/v1/applications/{id}` - Detalhes (Secretaria/Admin)
     - `PATCH /api/v1/applications/{id}/status` - Atualizar status (Secretaria/Admin)
     - `POST /api/v1/applications/{id}/documents` - Upload documento (autenticado)
     - `DELETE /api/v1/applications/{id}` - Remover (Secretaria/Admin)

4. **Rotas**
   - Registradas em `/routes/api.php`

---

## 🧪 COMO TESTAR

### Pré-requisitos

1. Banco de dados PostgreSQL configurado no `.env`
2. migrations executadas

### Passo 1: Executar Migrations

```bash
php artisan migrate
```

**Saída esperada:**
```
INFO  Running migrations.
  2026_08_10_000001_create_applications_table ........ XX.XXms DONE
  2026_08_10_000002_create_application_documents_table  XX.XXms DONE
  2026_08_10_000003_add_protocol_to_applications_table  XX.XXms DONE
```

---

### Passo 2: Verificar Rotas

```bash
php artisan route:list --path=api/v1/applications
```

**Saída esperada:**
```
POST        api/v1/applications
GET|HEAD    api/v1/applications
GET|HEAD    api/v1/applications/{application}
PATCH       api/v1/applications/{application}/status
POST        api/v1/applications/{application}/documents
DELETE      api/v1/applications/{application}
GET|HEAD    api/v1/applications/by-protocol/{protocol}
```

---

### Passo 3: Testar Criação de Inscrição (cURL)

```bash
curl -X POST http://localhost:8000/api/v1/applications \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "nome_completo": "João da Silva",
    "email": "joao.silva@email.com",
    "telefone": "24999999999",
    "whatsapp": "24999999999",
    "cpf": "12345678901",
    "rg": "123456789",
    "data_nascimento": "1990-05-15",
    "sexo": "M",
    "estado_civil": "Solteiro",
    "nacionalidade": "Brasileira",
    "naturalidade_cidade": "Paracambi",
    "naturalidade_estado": "RJ",
    "escolaridade": "Ensino Médio Completo",
    "cep": "26600000",
    "logradouro": "Rua das Flores",
    "numero": "123",
    "complemento": "Apto 101",
    "bairro": "Centro",
    "cidade": "Paracambi",
    "estado": "RJ",
    "curso_id": "<ID_DO_CURSO>",
    "course_modality_id": "<ID_DA_MODALIDADE>"
  }'
```

**Resposta esperada (201 Created):**
```json
{
  "success": true,
  "message": "Inscrição realizada com sucesso! Você receberá um e-mail de confirmação.",
  "data": {
    "application": {
      "id": "uuid...",
      "protocol": "INSC-XXXXXXXX-20260810",
      "nome_completo": "João da Silva",
      "email": "joao.silva@email.com",
      "status": "em_analise",
      ...
    },
    "protocol": "INSC-XXXXXXXX-20260810"
  }
}
```

---

### Passo 4: Consultar Inscrição por Protocolo

```bash
curl -X GET http://localhost:8000/api/v1/applications/by-protocol/INSC-XXXXXXXX-20260810
```

**Resposta esperada (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": "uuid...",
    "protocol": "INSC-XXXXXXXX-20260810",
    "nome_completo": "João da Silva",
    "status": "em_analise",
    "curso": {...},
    "documentos": []
  }
}
```

---

### Passo 5: Secretaria - Listar Inscrições

```bash
curl -X GET "http://localhost:8000/api/v1/applications?status=em_analise&search=Joao" \
  -H "Authorization: Bearer <TOKEN_ADMIN>" \
  -H "Accept: application/json"
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "page": 1,
    "limit": 15,
    "total": 1,
    "total_pages": 1
  }
}
```

---

### Passo 6: Secretaria - Aprovar Inscrição

```bash
curl -X PATCH http://localhost:8000/api/v1/applications/<ID_INSCRICAO>/status \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN_ADMIN>" \
  -d '{
    "status": "aprovado",
    "observacoes_secretaria": "Documentação completa. Aguardando matrícula."
  }'
```

**Resposta esperada:**
```json
{
  "success": true,
  "message": "Inscrição aprovado com sucesso.",
  "data": {
    "id": "...",
    "status": "aprovado",
    "observacoes_secretaria": "Documentação completa. Aguardando matrícula.",
    "analisado_em": "2026-08-10T15:00:00.000000Z",
    "analisado_por_id": "uuid-admin..."
  }
}
```

---

## 🔐 Tokens de Teste

Para testar rotas protegidas, você precisa de um token JWT.

### Obter Token (Login):

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@cetsadp.com.br",
    "password": "senha_admin"
  }'
```

Use o `token` retornado no header `Authorization: Bearer <TOKEN>`.

---

## 📁 Upload de Documentos

### Enviar Documento Complementar:

```bash
curl -X POST http://localhost:8000/api/v1/applications/<ID_INSCRICAO>/documents \
  -H "Authorization: Bearer <TOKEN_CANDIDATO>" \
  -F "type=photo" \
  -F "file=@/caminho/para/foto.jpg"
```

**Tipos válidos:** `photo`, `id_document`, `cpf`, `residence_certificate`, `school_history`, `other`

---

## 🎯 Status Válidos

| Status | Descrição | Pode Editar? |
|--------|-----------|--------------|
| `em_analise` | Aguardando análise da secretaria | ✅ Sim |
| `aprovado` | Aprovado, aguardando matrícula | ❌ Não |
| `rejeitado` | Rejeitado pela secretaria | ❌ Não |
| `matriculado` | Já foi matriculado | ❌ Não |
| `cancelado` | Candidato cancelou | ❌ Não |

---

## 🐛 Problemas Comuns

### Erro: "Table 'applications' doesn't exist"
**Solução:** Execute `php artisan migrate`

### Erro: "404 Not Found" nas rotas
**Solução:** Verifique se as rotas estão registradas: `php artisan route:list --path=api/v1/applications`

### Erro: "401 Unauthorized"
**Solução:** Rotas de listagem/atualização requerem autenticação. Use rotas públicas para criar/consultar por protocolo.

### Erro: "Validation failed: curso_id"
**Solução:** Certifique-se de que existem cursos cadastrados na tabela `courses`.

---

## 📊 Próximos Passos

1. ✅ Migrations criadas
2. ✅ Models implementados
3. ✅ Controller API completo
4. ✅ Rotas registradas
5. ⏳ **Testar endpoints** (você está aqui)
6. ⏳ Integrar com frontend (formulário de inscrição)
7. ⏳ Implementar envio de e-mails/WhatsApp
8. ⏳ Criar painel Filament para Secretaria

---

## 📞 Suporte

Em caso de dúvidas, consulte:
- `/docs/AUDITORIA_E_PLANO.md` - Plano completo do projeto
- `/routes/api.php` - Definição das rotas
- `/app/Http/Controllers/Api/V1/ApplicationController.php` - Lógica da API
- `/app/Models/Application.php` - Model com regras de negócio

