# 🧪 Teste do Módulo de Inscrições - CETSADP

## ✅ Status Atual

- [x] Migration criada: `2026_08_10_000001_create_applications_table`
- [x] Migration criada: `2026_08_10_000002_create_application_documents_table`
- [x] Migration criada: `2026_08_10_000003_add_protocol_to_applications_table`
- [x] Model `Application` criado em `app/Models/Application.php`
- [x] Model `ApplicationDocument` criado em `app/Models/ApplicationDocument.php`
- [x] Controller `ApplicationController` criado em `app/Http/Controllers/Api/V1/ApplicationController.php`
- [x] Rotas registradas em `routes/api.php`
- [x] Resources Filament criados para painel administrativo

---

## 🔧 Pré-requisitos

### 1. Executar Migrations

No seu terminal (Windows PowerShell):

```powershell
php artisan migrate
```

**Saída esperada:**
```
INFO  Running migrations.

  2026_08_10_000001_create_applications_table ........ XX.XXms DONE
  2026_08_10_000002_create_application_documents_table  XX.XXms DONE
  2026_08_10_000003_add_protocol_to_applications_table  XX.XXms DONE
```

### 2. Verificar Cursos Existentes

A inscrição requer um curso válido. Verifique se existem cursos cadastrados:

```powershell
php artisan tinker
```

No Tinker:

```php
\App\Models\Curso::select('id', 'nome')->get();
```

**Se não houver cursos**, crie um curso de teste:

```php
\App\Models\Curso::create([
    'nome' => 'Médio em Teologia',
    'descricao' => 'Curso de Teologia com duração de 3 anos',
    'ativo' => true,
]);
```

Anote o **ID do curso** criado (ex: `a1b2c3d4-e5f6-7890-abcd-ef1234567890`).

---

## 🚀 Testes da API

### Teste 1: Criar Inscrição (Sucesso)

```powershell
$body = @{
    nome_completo = "João da Silva"
    email = "joao.silva@teste.com"
    telefone = "24999999999"
    whatsapp = "24999999999"
    cpf = "07906589123"
    rg = "123456789"
    data_nascimento = "1990-05-15"
    sexo = "M"
    estado_civil = "Solteiro"
    nacionalidade = "Brasileira"
    naturalidade_cidade = "Paracambi"
    naturalidade_estado = "RJ"
    escolaridade = "Ensino Médio Completo"
    cep = "26600000"
    logradouro = "Rua das Flores"
    numero = "123"
    complemento = "Apto 101"
    bairro = "Centro"
    cidade = "Paracambi"
    estado = "RJ"
    curso_id = "COLE_O_ID_DO_CURSO_AQUI"
    course_modality_id = $null
} | ConvertTo-Json -Depth 10

$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/api/v1/applications" `
        -Method POST `
        -Headers $headers `
        -Body $body `
        -UseBasicParsing
    
    $json = $response.Content | ConvertFrom-Json
    Write-Host "✅ SUCESSO!" -ForegroundColor Green
    Write-Host "Protocolo: $($json.data.protocol)" -ForegroundColor Cyan
    Write-Host "Status: $($json.data.status)" -ForegroundColor Yellow
    Write-Host "ID: $($json.data.id)" -ForegroundColor Gray
} catch {
    Write-Host "❌ ERRO!" -ForegroundColor Red
    Write-Host $_.Exception.Message
    if ($_.ErrorDetails.Message) {
        $error = $_.ErrorDetails.Message | ConvertFrom-Json
        Write-Host ($error | ConvertTo-Json -Depth 5)
    }
}
```

**Resposta esperada (201 Created):**

```json
{
  "success": true,
  "data": {
    "protocol": "INSC-XXXXXXXX-20260810",
    "nome_completo": "João da Silva",
    "email": "joao.silva@teste.com",
    "status": "em_analise",
    "created_at": "2026-08-10T..."
  },
  "message": "Inscrição realizada com sucesso!"
}
```

---

### Teste 2: Consultar Inscrição por Protocolo (Público)

```powershell
$protocolo = "INSC-XXXXXXXX-20260810"  # Substitua pelo protocolo retornado

try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/api/v1/applications/by-protocol/$protocolo" `
        -Method GET `
        -Headers @{ "Accept" = "application/json" } `
        -UseBasicParsing
    
    $json = $response.Content | ConvertFrom-Json
    Write-Host "✅ Inscricao encontrada!" -ForegroundColor Green
    Write-Host ($json.data | ConvertTo-Json -Depth 5)
} catch {
    Write-Host "❌ Erro ao buscar inscricao" -ForegroundColor Red
    Write-Host $_.Exception.Message
}
```

---

### Teste 3: Listar Todas as Inscrições (Secretaria/Admin - Requer Auth)

```powershell
# Primeiro faça login para obter o token
$loginBody = @{
    email = "admin@cets.com"
    password = "12345678"
} | ConvertTo-Json

$loginResponse = Invoke-WebRequest -Uri "http://localhost:8000/api/v1/auth/login" `
    -Method POST `
    -Headers @{ "Content-Type" = "application/json"; "Accept" = "application/json" } `
    -Body $loginBody `
    -UseBasicParsing

$token = ($loginResponse.Content | ConvertFrom-Json).access_token

# Agora liste as inscrições
$headers = @{
    "Authorization" = "Bearer $token"
    "Accept" = "application/json"
}

try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/api/v1/applications" `
        -Method GET `
        -Headers $headers `
        -UseBasicParsing
    
    $json = $response.Content | ConvertFrom-Json
    Write-Host "✅ Total de inscrições: $($json.pagination.total)" -ForegroundColor Green
    Write-Host ($json.data | ConvertTo-Json -Depth 3)
} catch {
    Write-Host "❌ Erro" -ForegroundColor Red
    Write-Host $_.Exception.Message
}
```

---

### Teste 4: Aprovar Inscrição (Secretaria/Admin)

```powershell
$applicationId = "ID_DA_INSCRICAO"  # Substitua pelo ID retornado no Teste 1
$token = "SEU_TOKEN_DE_ADMIN"  # Obtido no Teste 3

$body = @{
    status = "aprovado"
    observacoes_secretaria = "Documentação ok. Aprovado para matrícula."
} | ConvertTo-Json

$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/api/v1/applications/$applicationId/status" `
        -Method PATCH `
        -Headers $headers `
        -Body $body `
        -UseBasicParsing
    
    $json = $response.Content | ConvertFrom-Json
    Write-Host "✅ Inscrição aprovada!" -ForegroundColor Green
    Write-Host "Novo status: $($json.data.status)" -ForegroundColor Cyan
} catch {
    Write-Host "❌ Erro ao aprovar" -ForegroundColor Red
    $error = $_.ErrorDetails.Message | ConvertFrom-Json
    Write-Host ($error | ConvertTo-Json -Depth 5)
}
```

---

### Teste 5: Rejeitar Inscrição (Secretaria/Admin)

```powershell
$applicationId = "ID_DA_INSCRICAO"
$token = "SEU_TOKEN_DE_ADMIN"

$body = @{
    status = "rejeitado"
    observacoes_secretaria = "Documentação incompleta. Faltou histórico escolar."
} | ConvertTo-Json

$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/api/v1/applications/$applicationId/status" `
        -Method PATCH `
        -Headers $headers `
        -Body $body `
        -UseBasicParsing
    
    $json = $response.Content | ConvertFrom-Json
    Write-Host "✅ Inscrição rejeitada!" -ForegroundColor Yellow
    Write-Host "Motivo: $($json.data.observacoes_secretaria)" -ForegroundColor Gray
} catch {
    Write-Host "❌ Erro ao rejeitar" -ForegroundColor Red
}
```

---

## 🎨 Teste do Painel Filament (Secretaria)

1. Acesse: `http://localhost:8000/admin`
2. Login com:
   - Email: `admin@cets.com`
   - Senha: `12345678`
3. Navegue até **"Inscrições"** no menu lateral
4. Você deverá ver:
   - Lista de todas as inscrições
   - Filtros por status, curso, data
   - Botões para visualizar, editar, aprovar/rejeitar

---

## ❌ Tratamento de Erros Comuns

### Erro 422 - Unprocessable Content (Validação)

**Causa:** Dados inválidos ou campos obrigatórios faltando.

**Solução:** Verifique a resposta JSON que detalha os erros:

```json
{
  "success": false,
  "errors": {
    "cpf": ["Já existe uma inscrição com este CPF."],
    "curso_id": ["O curso selecionado não existe."]
  }
}
```

### Erro 404 - Not Found

**Causa:** Curso não encontrado ou rota incorreta.

**Solução:** 
1. Verifique se o curso existe: `php artisan tinker` → `Curso::all()`
2. Use o ID correto no campo `curso_id`

### Erro 500 - Class Application not found

**Causa:** Model não encontrado ou namespace incorreto.

**Solução:**
```powershell
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

---

## 📊 Verificação no Banco de Dados

```powershell
php artisan tinker
```

```php
// Listar todas as inscrições
\App\Models\Application::with(['curso'])->orderBy('created_at', 'desc')->get();

// Contar por status
\App\Models\Application::selectRaw('status, count(*) as total')
    ->groupBy('status')
    ->get();

// Buscar por protocolo
\App\Models\Application::where('protocol', 'INSC-XXXXXXXX-20260810')->first();
```

---

## ✅ Checklist Final

- [ ] Migrations executadas com sucesso
- [ ] Pelo menos 1 curso cadastrado
- [ ] Teste 1 (Criar) passou
- [ ] Teste 2 (Consultar por protocolo) passou
- [ ] Teste 3 (Listar - autenticado) passou
- [ ] Teste 4 (Aprovar) passou
- [ ] Painel Filament acessível e funcional
- [ ] Dados persistidos no banco corretamente

---

## 📝 Próximos Passos

Após validar este módulo:

1. **Implementar envio de e-mails** (confirmação de inscrição)
2. **Implementar upload de documentos** (anexos na inscrição)
3. **Integração com WhatsApp** (notificações)
4. **Frontend** (formulário HTML consumindo a API)
5. **Próximo módulo**: Matrícula de Alunos

---

**Dúvidas?** Consulte a documentação em `/docs/AUDITORIA_E_PLANO.md`
