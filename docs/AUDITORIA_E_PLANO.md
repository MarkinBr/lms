# 📋 AUDITORIA E PLANO DE IMPLEMENTAÇÃO CETSADP — LARAVEL

## 1. RESUMO DA AUDITORIA TÉCNICA

### 1.1 Stack Tecnológica Atual (Confirmada ✅)

| Componente | Status | Versão/Configuração |
|------------|--------|---------------------|
| **Backend** | ✅ Confirmado | Laravel 12.x + PHP 8.2+ |
| **Banco de Dados** | ✅ Confirmado | PostgreSQL |
| **ORM** | ✅ Nativo | Eloquent (Laravel) |
| **Autenticação** | ✅ Confirmado | Laravel Sanctum (JWT-like tokens) |
| **Frontend Admin** | ✅ Implementado | Filament 3.x (Painéis: Admin, Secretaria, Professor, Aluno) |
| **Cache/Filas** | ✅ Configurado | Redis |
| **Storage** | ✅ Suportado | Local + S3/R2 (via `filesystems.php`) |
| **PDF** | ✅ Implementado | DomPDF + Simple QRCode |
| **Excel** | ✅ Implementado | Maatwebsite Excel |
| **Pagamentos** | ✅ Implementado | Mercado Pago (webhook configurado) |

---

### 1.2 Estrutura Atual do Projeto

```
/workspace
├── app/
│   ├── Console/              # Commands agendados
│   ├── Exports/              # Exportações Excel/PDF
│   ├── Filament/             # Painéis administrativos (Admin, Secretaria, Professor, Aluno)
│   ├── Helpers/              # Funções auxiliares
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Aluno/        # Controladores do Portal do Aluno
│   │   │   ├── Auth/         # Autenticação (Login, SSO)
│   │   │   └── ...           # Relatórios, Webhooks, etc.
│   │   └── Middleware/       # Middleware (IdentifyCurso, etc.)
│   ├── Jobs/                 # Filas/processamento assíncrono
│   ├── Mail/                 # Mails transacionais
│   ├── Models/               # 32 models Eloquent
│   ├── Observers/            # Observers de modelo
│   ├── Policies/             # Políticas de autorização
│   ├── Providers/            # Service providers
│   └── Services/             # Camada de serviço
├── config/
│   ├── auth.php              # Config autenticação
│   ├── database.php          # PostgreSQL configurado
│   ├── filesystems.php       # Storage (local, s3, r2)
│   ├── mail.php              # SMTP configurável
│   ├── queue.php             # Redis queue
│   ├── sanctum.php           # API tokens
│   ├── financeiro.php        # Configurações financeiras
│   └── instituicao.php       # Dados institucionais
├── database/
│   ├── migrations/           # 45 migrations existentes
│   ├── seeders/              # Seeders (DatabaseSeeder, etc.)
│   └── factories/            # Factories para testes
├── routes/
│   ├── web.php               # Rotas web
│   ├── api.php               # Rotas API (Sanctum + Webhooks)
│   └── console.php           # Commands agendados
├── resources/views/          # Blade templates
├── public/                   # Assets públicos
└── tests/                    # Testes PHPUnit
```

---

### 1.3 Models Existentes (32 modelos)

| Model | Status | Observações |
|-------|--------|-------------|
| `User` | ✅ Completo | Campos: CPF, telefone, foto, nível (admin/secretario/professor/aluno), endereço, dados bancários, SSO |
| `Curso` | ✅ Completo | Relacionamentos: turmas, módulos, aulas, matrículas |
| `Modulo` | ✅ Completo | Pertence a Curso, tem muitas Aulas |
| `Aula` | ✅ Completo | Vídeo, materiais, ordem, liberada |
| `AulaAoVivo` | ✅ Existe | Para aulas síncronas |
| `AulaProgresso` | ✅ Existe | Progresso do aluno por aula |
| `Turma` | ✅ Completo | Ano letivo, turno, valor, parcelas |
| `Matricula` | ✅ Completo | User → Curso/Turma, status, validade |
| `Mensalidade` | ✅ Avançado | BUG-C06 corrigido, PIX, multas, webhook MP |
| `MensalidadeCobranca` | ✅ Existe | Cobranças adicionais |
| `Nota` | ✅ Existe | AV1, AV2, AV3, média final |
| `Certificado` | ✅ Existe | Validação, QR Code |
| `Declaracao` | ✅ Existe | Declarações de matrícula |
| `ProfessorDocumento` | ✅ Existe | Documentos do professor |
| `CategoriaFinanceira` | ✅ Existe | Categorização financeira |
| `Conta` | ✅ Existe | Contas bancárias |
| `Despesa` | ✅ Existe | Gestão de despesas |
| `MovimentacaoFinanceira` | ✅ Existe | Fluxo de caixa |
| `MovimentacaoAuditoria` | ✅ Existe | Logs de auditoria financeira |
| `EventoCalendario` | ✅ Existe | Calendário acadêmico |
| `Aviso` | ⚠️ Parcial | Ver estrutura |
| `Biblioteca` | ⚠️ Básico | Verificar campos |
| `ComentarioAula` | ✅ Existe | Comentários nas aulas |
| `PaginaInicial` | ✅ Existe | Conteúdo do site público |
| `Progresso` | ✅ Existe | Progresso geral |
| `Requerimento` | ⚠️ Básico | Verificar campos |
| `TransacaoTeste` | ✅ Teste | Apenas para testes |

---

### 1.4 Funcionalidades Já Implementadas

#### ✅ Autenticação e Autorização
- Login por CPF + senha
- Multi-painéis Filament (Admin, Secretaria, Professor, Aluno)
- Middleware `canAccessPanel()` por nível
- SSO implementado (`SsoController.php`)
- Sanctum para API tokens

#### ✅ Gestão Acadêmica
- Cursos, módulos, aulas em sequência
- Matrículas por turma/curso
- Progresso individual por aula
- Comentários nas aulas
- Notas (AV1, AV2, AV3, média)

#### ✅ Financeiro
- Mensalidades automáticas por matrícula
- PIX com QR Code (Mercado Pago)
- Webhook de pagamento configurado
- Multas e juros (seeders de categorias)
- Contas bancárias múltiplas
- Despesas e movimentações
- Recibos públicos (validação por código)

#### ✅ Documentos
- Certificados com validação
- Declarações de matrícula
- Upload de documentos (professores)

#### ✅ Comunicação
- Eventos de calendário
- Avisos
- Comentários

---

## 2. GAP ANALYSIS — O QUE FALTA IMPLEMENTAR

### 2.1 Módulos Críticos Faltantes

| Módulo | Prioridade | Status | Ação Necessária |
|--------|------------|--------|-----------------|
| **Inscrição de Candidatos** | 🔴 Alta | ❌ Ausente | Criar fluxo: Visitante → Candidato → Análise → Matrícula |
| **RBAC Completo (Shield)** | 🔴 Alta | ⚠️ Parcial | Filament Shield instalado mas não configurado completamente |
| **Recuperação Acadêmica** | 🔴 Alta | ❌ Ausente | Implementar lógica de recuperação (nota < 7.0) |
| **TCC (Trabalho de Conclusão)** | 🔴 Alta | ❌ Ausente | Modelos: Thesis, ThesisSubmission, ThesisEvaluation |
| **Frequência por Aula** | 🔴 Alta | ⚠️ Parcial | Existe estrutura mas precisa de registro por aula com justificativa |
| **Atividades/Submissões** | 🔴 Alta | ❌ Ausente | Activities, Submissions (diferente de comentários) |
| **Avaliações/Provas** | 🔴 Alta | ⚠️ Parcial | Modelo `Nota` existe mas precisa de `Assessment` como entidade |
| **Plano Financeiro por Curso** | 🔴 Alta | ⚠️ Parcial | Turma tem valor, mas falta `CourseFinancialPlan` configurável |
| **Document Request Workflow** | 🟠 Média | ⚠️ Parcial | `Declaracao` existe, mas falta workflow de solicitação → aprovação |
| **Mensagens Internas** | 🟠 Média | ❌ Ausente | Sistema de mensagens entre usuários |
| **Fórum por Turma/Disciplina** | 🟠 Média | ❌ Ausente | ForumThread, ForumPost |
| **Suporte/Tickets** | 🟠 Média | ❌ Ausente | SupportTicket, TicketMessage |
| **Notificações (Email/WhatsApp)** | 🟠 Média | ⚠️ Parcial | Tabela `notifications` existe, mas falta integração WhatsApp |
| **Notícias Públicas** | 🟡 Baixa | ⚠️ Parcial | Verificar se integrado ao site público |
| **Biblioteca Completa** | 🟡 Baixa | ⚠️ Básico | Expandir modelo `Biblioteca` |

---

### 2.2 Entidades Faltantes (comparado ao schema Prisma)

```php
// === CANDIDATURA E MATRÍCULA ===
❌ Application (Inscrições de candidatos)
❌ ApplicationDocument (Documentos da inscrição)
❌ Student (Perfil específico de aluno - pode ser estendido do User)

// === ACADÊMICO AVANÇADO ===
❌ CourseModality (Modalidades: Presencial, Online, Híbrido)
❌ Subject (Disciplinas independentes de módulos)
❌ Semester (Períodos letivos formais)
❌ Class (Turmas específicas com horário/local - diferente de Turma atual)
❌ ClassEnrollment (Alunos em turmas específicas)
❌ Lesson (Aulas individuais com conteúdo estruturado)
❌ Material (Materiais de aula como entidade)
❌ Activity (Atividades/tarefas com prazo)
❌ Submission (Entregas de atividades)
❌ Assessment (Avaliações/provas formais)
❌ Grade (Notas vinculadas a assessments)
❌ Attendance (Frequência formal por aula)
❌ AcademicRecord (Histórico acadêmico consolidado)
❌ Thesis (TCC)
❌ ThesisSubmission
❌ ThesisEvaluation

// === FINANCEIRO AVANÇADO ===
⚠️ Invoice (Mensalidade existe, mas pode precisar de Invoice para cobranças avulsas)
✅ Payment (Mensalidade já cobre pagamentos)
❌ Discount (Descontos configuráveis: irmão, antecipação, múltiplos cursos)
❌ FineCategory (Categorias de multa/juro já existem via seeder)

// === DOCUMENTOS E CERTIFICADOS ===
❌ DocumentRequest (Solicitação formal de documentos)
✅ Certificate (Já existe)
❌ CertificateTemplate (Templates HTML para geração dinâmica)

// === COMUNICAÇÃO ===
❌ Message (Mensagens internas entre usuários)
❌ ForumThread
❌ ForumPost
❌ SupportTicket
❌ SupportTicketMessage

// === SISTEMA ===
❌ Role (Filament Shield já provê)
❌ Permission (Filament Shield já provê)
✅ AuditLog (MovimentacaoAuditoria existe para financeiro)
❌ SystemSetting (Configurações chave-valor)
❌ File (Gerenciamento centralizado de arquivos)
```

---

## 3. PLANO DE IMPLEMENTAÇÃO PRIORIZADO

### FASE 1: Fundação e Inscrição (Semanas 1-2)

#### 1.1 RBAC Completo com Filament Shield
```bash
php artisan shield:generate --all
php artisan shield:install
```

**Arquivos a criar:**
- [ ] Configurar roles: SuperAdmin, Admin, Secretary, Teacher, Student, Candidate
- [ ] Definir permissions por recurso
- [ ] Seed inicial de roles e permissions

#### 1.2 Módulo de Inscrição de Candidatos
**Models:**
- [ ] `Application.php` (inscrições)
- [ ] `ApplicationDocument.php` (documentos anexados)

**Migrations:**
- [ ] `create_applications_table.php`
- [ ] `create_application_documents_table.php`

**Controllers:**
- [ ] `ApplicationController.php` (público)
- [ ] `Admin/ApplicationCrud.php` (Filament - análise)

**Features:**
- [ ] Formulário público de inscrição
- [ ] Upload de documentos (RG, CPF, comprovante, foto)
- [ ] Status: pending, under_review, approved, rejected
- [ ] Candidato pode editar enquanto "pending"
- [ ] Notificação por email na aprovação/rejeição

---

### FASE 2: Estrutura Acadêmica Completa (Semanas 3-5)

#### 2.1 Disciplinas e Períodos Letivos
**Models:**
- [ ] `Subject.php` (disciplinas)
- [ ] `Semester.php` (períodos letivos)
- [ ] `CourseModality.php` (modalidades)

**Migrations:**
- [ ] `create_subjects_table.php`
- [ ] `create_semesters_table.php`
- [ ] `create_course_modalities_table.php`

#### 2.2 Turmas e Frequência
**Models:**
- [ ] `ClassSession.php` (aulas presenciais/online com data/hora)
- [ ] `Attendance.php` (frequência por aula)

**Features:**
- [ ] Registro de frequência: present, absent, late, justified
- [ ] Justificativa de ausência (upload de documento)
- [ ] Cálculo automático de percentual (mínimo 75%)

#### 2.3 Atividades e Avaliações
**Models:**
- [ ] `Activity.php` (tarefas com prazo)
- [ ] `Submission.php` (entregas de alunos)
- [ ] `Assessment.php` (provas/avaliações formais)
- [ ] `Grade.php` (notas vinculadas a assessments)

**Features:**
- [ ] Professor cria atividades com prazo
- [ ] Aluno envia arquivos/respostas
- [ ] Professor corrige e lança notas
- [ ] Pesos configuráveis por disciplina
- [ ] Cálculo automático de média (mínimo 7.0)

---

### FASE 3: Recuperação e TCC (Semana 6)

#### 3.1 Recuperação Acadêmica
**Models:**
- [ ] `RecoveryExam.php` (prova de recuperação)
- [ ] `RecoveryGrade.php` (nota de recuperação)

**Features:**
- [ ] Aluno abaixo de 7.0 automaticamente elegível
- [ ] Professor lança nota de recuperação
- [ ] Cálculo: (nota_original + recuperação) / 2 ou maior nota (configurável)
- [ ] Histórico mantém ambas as notas

#### 3.2 TCC (Trabalho de Conclusão de Curso)
**Models:**
- [ ] `Thesis.php` (registro do TCC)
- [ ] `ThesisSubmission.php` (envio do trabalho)
- [ ] `ThesisEvaluation.php` (avaliação da banca)

**Features:**
- [ ] Cadastro de orientador
- [ ] Aluno envia arquivo do TCC
- [ ] Banca avalia (nota + parecer)
- [ ] Aprovação necessária para certificado

---

### FASE 4: Financeiro Avançado (Semana 7)

#### 4.1 Planos Financeiros por Curso
**Models:**
- [ ] `CourseFinancialPlan.php`

**Features:**
- [ ] Valor total, número de parcelas
- [ ] Vencimento padrão (dia do mês)
- [ ] Descontos configuráveis:
  - [ ] Desconto por antecipação
  - [ ] Desconto para irmãos
  - [ ] Desconto múltiplos cursos
- [ ] Multas e juros (já existe seed, integrar)

#### 4.2 Pagamento via Comprovante (Manual)
**Features:**
- [ ] Aluno upload de comprovante (PIX, boleto, transferência)
- [ ] Secretaria analisa e confirma pagamento
- [ ] Status: pending_verification, verified, rejected
- [ ] Notificação ao aluno

---

### FASE 5: Comunicação (Semana 8)

#### 5.1 Mensagens Internas
**Models:**
- [ ] `Message.php`
- [ ] `MessageRecipient.php`

**Features:**
- [ ] Inbox, sent, archive
- [ ] Notificações por email

#### 5.2 Fórum
**Models:**
- [ ] `ForumThread.php`
- [ ] `ForumPost.php`

**Features:**
- [ ] Fóruns por turma/disciplina
- [ ] Moderação (professor/admin)

#### 5.3 Suporte/Tickets
**Models:**
- [ ] `SupportTicket.php`
- [ ] `SupportTicketMessage.php`

**Features:**
- [ ] Aluno abre ticket
- [ ] Secretaria/professor responde
- [ ] Status: open, in_progress, resolved, closed

---

### FASE 6: Notificações e Integrações (Semana 9)

#### 6.1 Notificações Email + WhatsApp
**Services:**
- [ ] `NotificationService.php`
- [ ] `WhatsAppService.php` (API Evolution, Z-API, ou similar)

**Events:**
- [ ] `ApplicationSubmitted` (confirmação inscrição)
- [ ] `ApplicationApproved` (aprovação + dados de acesso)
- [ ] `EnrollmentCreated` (matrícula realizada)
- [ ] `InvoiceDueSoon` (mensalidade próxima)
- [ ] `InvoiceOverdue` (mensalidade vencida)
- [ ] `PaymentConfirmed` (pagamento confirmado)
- [ ] `GradePublished` (nota lançada)
- [ ] `CertificateAvailable` (certificado disponível)

---

### FASE 7: Documentos e Certificados (Semana 10)

#### 7.1 Solicitação de Documentos
**Models:**
- [ ] `DocumentRequest.php`

**Features:**
- [ ] Aluno solicita: histórico, declaração, etc.
- [ ] Secretaria aprova/rejeita
- [ ] Geração automática de PDF
- [ ] Download pelo aluno

#### 7.2 Certificados Dinâmicos
**Models:**
- [ ] `CertificateTemplate.php`

**Features:**
- [ ] Templates HTML personalizáveis
- [ ] Geração dinâmica com DomPDF
- [ ] QR Code de validação
- [ ] Página pública de validação

---

### FASE 8: API REST Completa (Semana 11)

**Rotas API a implementar:**
```php
// Auth
POST /api/v1/auth/login
POST /api/v1/auth/logout
POST /api/v1/auth/refresh
POST /api/v1/auth/forgot-password
POST /api/v1/auth/reset-password

// Applications (Inscrição)
POST /api/v1/applications
GET  /api/v1/applications/{id}
PATCH /api/v1/applications/{id}

// Students
GET  /api/v1/students/me
GET  /api/v1/students/me/enrollments
GET  /api/v1/students/me/grades
GET  /api/v1/students/me/attendance
GET  /api/v1/students/me/invoices
GET  /api/v1/students/me/certificates

// Admin/Secretary
GET    /api/v1/admin/students
POST   /api/v1/admin/students/{id}/enroll
GET    /api/v1/admin/courses
POST   /api/v1/admin/courses
// ... etc
```

---

### FASE 9: Testes e Qualidade (Semana 12)

**Testes a implementar:**
- [ ] Testes unitários (models, services)
- [ ] Testes de feature (fluxos completos)
- [ ] Testes de integração (API, banco)
- [ ] Testes E2E (Playwright - opcional)

**Ferramentas:**
```bash
composer require --dev phpunit/phpunit
composer require --dev pestphp/pest  # Opcional, alternativa ao PHPUnit
```

---

## 4. ESTRUTURA DE DIRETÓRIOS PROPOSTA

```
app/
├── Models/
│   ├── Application.php              # NOVO
│   ├── ApplicationDocument.php      # NOVO
│   ├── Subject.php                  # NOVO
│   ├── Semester.php                 # NOVO
│   ├── CourseModality.php           # NOVO
│   ├── ClassSession.php             # NOVO (ou renomear Turma)
│   ├── Attendance.php               # NOVO
│   ├── Activity.php                 # NOVO
│   ├── Submission.php               # NOVO
│   ├── Assessment.php               # NOVO
│   ├── Grade.php                    # NOVO (ou estender Nota)
│   ├── RecoveryExam.php             # NOVO
│   ├── Thesis.php                   # NOVO
│   ├── ThesisSubmission.php         # NOVO
│   ├── ThesisEvaluation.php         # NOVO
│   ├── CourseFinancialPlan.php      # NOVO
│   ├── Message.php                  # NOVO
│   ├── ForumThread.php              # NOVO
│   ├── ForumPost.php                # NOVO
│   ├── SupportTicket.php            # NOVO
│   ├── SupportTicketMessage.php     # NOVO
│   ├── DocumentRequest.php          # NOVO
│   ├── CertificateTemplate.php      # NOVO
│   └── SystemSetting.php            # NOVO
│
├── Filament/
│   ├── Resources/
│   │   ├── ApplicationResource.php
│   │   ├── SubjectResource.php
│   │   ├── SemesterResource.php
│   │   ├── ActivityResource.php
│   │   ├── AssessmentResource.php
│   │   ├── ThesisResource.php
│   │   ├── MessageResource.php
│   │   ├── SupportTicketResource.php
│   │   └── DocumentRequestResource.php
│   └── ...
│
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── v1/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── ApplicationController.php
│   │   │   │   ├── StudentController.php
│   │   │   │   └── ...
│   │   └── Web/
│   │       └── PublicApplicationController.php
│   └── Requests/
│       ├── StoreApplicationRequest.php
│       ├── UpdateApplicationRequest.php
│       └── ...
│
├── Services/
│   ├── NotificationService.php
│   ├── WhatsAppService.php
│   ├── CertificateGeneratorService.php
│   └── EnrollmentService.php
│
├── Events/
│   ├── ApplicationSubmitted.php
│   ├── ApplicationApproved.php
│   ├── EnrollmentCreated.php
│   └── ...
│
└── Jobs/
    ├── SendApplicationConfirmation.php
    ├── GenerateCertificate.php
    └── ...
```

---

## 5. PRÓXIMOS PASSOS IMEDIATOS

### Passo 1: Criar Migration de Inscrição (HOJE)
```bash
php artisan make:migration create_applications_table
php artisan make:migration create_application_documents_table
```

### Passo 2: Criar Models de Inscrição
```bash
php artisan make:model Application
php artisan make:model ApplicationDocument
```

### Passo 3: Instalar e Configurar Filament Shield
```bash
composer require bezhansalleh/filament-shield
php artisan shield:install
php artisan shield:generate --all
```

### Passo 4: Seed Inicial de Roles
```bash
php artisan make:seeder RolePermissionSeeder
```

---

## 6. DECISÕES ARQUITETURAIS CONFIRMADAS

| Decisão | Implementação no Laravel |
|---------|--------------------------|
| **JWT + Refresh Token** | Sanctum com `personal_access_tokens` + refresh manual |
| **RBAC** | Filament Shield (roles, permissions) |
| **Storage Cloud** | `config/filesystems.php`: disk `s3` ou `r2` |
| **Email** | `config/mail.php`: SMTP configurável |
| **WhatsApp** | Service dedicado (`WhatsAppService.php`) |
| **Multas/Juros** | Seeders + cálculo no model `Mensalidade` |
| **Certificados** | DomPDF + QR Code + validação pública |
| **Backup** | Script externo + storage fora da VPS |

---

## 7. CHECKLIST DE VALIDAÇÃO

Antes de iniciar cada fase, validar:

- [ ] `.env` configurado corretamente
- [ ] Banco de dados migrado (`php artisan migrate`)
- [ ] Seeders executados (`php artisan db:seed`)
- [ ] Filament Shield configurado
- [ ] Storage link criado (`php artisan storage:link`)
- [ ] Queue worker rodando (`php artisan queue:work`)
- [ ] Scheduler configurado (`crontab -e`)

---

## 8. COMANDOS ÚTEIS

```bash
# Desenvolvimento
php artisan serve
php artisan queue:work
php artisan pail              # Logs em tempo real

# Banco de Dados
php artisan migrate
php artisan migrate:status
php artisan db:seed
php artisan migrate:fresh --seed

# Filament
php artisan make:filament-resource Student
php artisan make:filament-page Dashboard

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Testes
php artisan test
php artisan test --filter ApplicationTest
```

---

**Documento criado em:** {{date}}
**Versão:** 1.0
**Status:** Em revisão
