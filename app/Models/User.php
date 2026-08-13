<?php

namespace App\Models;

// Importações necessárias para o Filament funcionar
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Os atributos que podem ser preenchidos em massa.
     *
     * CORREÇÃO v9.3.2: Adicionados campos SSO (username, last_sso_at, sso_provider)
     */
    protected $fillable = [
        'name',
        'email',
        'password',

        // Identificação e Status
        'cpf',
        'telefone',
        'foto',
        'nivel', // admin, secretario, professor, aluno
        'ativo',
        'documentos',

        // Dados Bancários
        'tipo_chave',
        'chave_pix',

        // Endereço
        'cep',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'data_nasc',

        // Campos Específicos (Gestão de Pessoas)
        'formacao',          // Professor
        'area_atuacao',      // Professor
        'biografia',         // Professor
        'responsavel_nome',  // Aluno
        'responsavel_telefone', // Aluno
        'observacoes',       // Aluno
        'setor',             // Secretário
        'ramal',             // Secretário
        'super_admin',       // Admin

        // === NOVOS CAMPOS DO PERFIL DO ALUNO ===
        'genero',
        'profissao',
        'escolaridade',
        'bio', // Biografia do aluno
        'receber_newsletter',
        'receber_notificacoes_email',
        'perfil_publico',
        'senha_alterada_em',
        'exclusao_solicitada_em',
        'motivo_exclusao',
        'status',
        'perfil_atualizado_em',

        // === CAMPOS SSO (v9.3.2) ===
        'username',
        'last_sso_at',
        'sso_provider',
    ];

    /**
     * Os atributos que devem ficar ocultos.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversão de tipos.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'ativo' => 'boolean',
        'super_admin' => 'boolean',
        'data_nasc' => 'date',
        'documentos' => 'array',
        // Casts dos novos campos
        'senha_alterada_em' => 'datetime',
        'exclusao_solicitada_em' => 'datetime',
        'perfil_atualizado_em' => 'datetime',
        'receber_newsletter' => 'boolean',
        'receber_notificacoes_email' => 'boolean',
        'perfil_publico' => 'boolean',
        // Casts SSO (v9.3.2)
        'last_sso_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | SEGURANÇA: Controle de Acesso aos Painéis
    |--------------------------------------------------------------------------
    */
    public function canAccessPanel(Panel $panel): bool
    {
        // 1. Usuário inativo nunca entra no sistema
        if (! $this->ativo) {
            return false;
        }

        // 2. Regras para o PORTAL DO ALUNO (/aluno)
        if ($panel->getId() === 'aluno') {
            return $this->nivel === 'aluno' || $this->nivel === 'admin';
        }

        // 3. Regras para o PORTAL ADMIN (/admin)
        if ($panel->getId() === 'admin') {
            return $this->nivel === 'admin';
        }

        // 4. Regras para o PORTAL SECRETARIA (/secretaria)
        if ($panel->getId() === 'secretario') {
            return in_array($this->nivel, ['secretario', 'admin']);
        }

        // 5. Regras para o PORTAL PROFESSOR (/professor)
        if ($panel->getId() === 'professor') {
            return in_array($this->nivel, ['professor', 'admin']);
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Funções Personalizadas & Relações
    |--------------------------------------------------------------------------
    */

    public function scopeProfessores($query)
    {
        return $query->where('nivel', 'professor');
    }

    // Documentos do Professor (tabela professor_documentos)
    public function documentos()
    {
        return $this->hasMany(ProfessorDocumento::class);
    }

    // Matrículas do Aluno (Relacionamento direto)
    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'user_id');
    }

    // Cursos do Aluno (Relacionamento através das matrículas)
    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'matriculas', 'user_id', 'curso_id')
            ->withPivot('status', 'data_matricula', 'data_validade')
            ->withTimestamps();
    }

    // Certificados do Aluno
    public function certificados()
    {
        return $this->hasMany(Certificado::class, 'aluno_id');
    }

    // Aulas concluídas pelo aluno (Relacionamento com Progresso)
    public function aulasConcluidas()
    {
        // Mantive 'aluno_id' aqui pois a tabela 'aula_progresso' parece usar este nome
        return $this->belongsToMany(Aula::class, 'aula_progresso', 'aluno_id', 'aula_id')
            ->withPivot('concluida', 'updated_at')
            ->wherePivot('concluida', true);
    }

    // === RELACIONAMENTOS ADICIONADOS PARA CORRIGIR O ERRO ===

    // Avaliações/Notas do Aluno
    public function avaliacoes()
    {
        // CORREÇÃO CRÍTICA: Mudado de 'aluno_id' para 'user_id' para bater com a tabela 'notas'
        return $this->hasMany(Nota::class, 'user_id');
    }

    // Progresso detalhado (tabela aula_progresso)
    public function progresso()
    {
        // Mantive 'aluno_id' aqui para bater com o relacionamento aulasConcluidas acima
        return $this->hasMany(AulaProgresso::class, 'aluno_id');
    }
}
