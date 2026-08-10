<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Application - Inscrição de Candidato
 * 
 * Representa uma inscrição de candidato ao CETSADP.
 * Fluxo: Candidato se inscreve → Secretaria analisa → Aprova/Rejeita → Matrícula
 * 
 * @property string $id
 * @property string $nome_completo
 * @property string $cpf
 * @property string|null $rg
 * @property \Carbon\Carbon $data_nascimento
 * @property string $sexo
 * @property string|null $estado_civil
 * @property string $nacionalidade
 * @property string|null $naturalidade_cidade
 * @property string|null $naturalidade_estado
 * @property string $email
 * @property string $telefone
 * @property string|null $whatsapp
 * @property string $cep
 * @property string $logradouro
 * @property string $numero
 * @property string|null $complemento
 * @property string $bairro
 * @property string $cidade
 * @property string $estado
 * @property string $escolaridade
 * @property string $curso_id
 * @property string $course_modality_id
 * @property string $status (em_analise, aprovado, rejeitado, matriculado, cancelado)
 * @property array|null $documentos_urls
 * @property string|null $observacoes_secretaria
 * @property string|null $analisado_por_id
 * @property \Carbon\Carbon|null $analisado_em
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Application extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Status possíveis da inscrição
     */
    const STATUS_EM_ANALISE = 'em_analise';
    const STATUS_APROVADO = 'aprovado';
    const STATUS_REJEITADO = 'rejeitado';
    const STATUS_MATRICULADO = 'matriculado';
    const STATUS_CANCELADO = 'cancelado';

    /**
     * Tipos de documento aceitos
     */
    const DOCUMENT_TYPES = [
        'foto_3x4',
        'rg',
        'cpf',
        'comprovante_residencia',
        'historico_escolar',
        'certificado_conclusao',
        'outro'
    ];

    protected $table = 'applications';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Atributos que podem ser preenchidos em massa
     */
    protected $fillable = [
        'nome_completo',
        'cpf',
        'rg',
        'data_nascimento',
        'sexo',
        'estado_civil',
        'nacionalidade',
        'naturalidade_cidade',
        'naturalidade_estado',
        'email',
        'telefone',
        'whatsapp',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'escolaridade',
        'curso_id',
        'course_modality_id',
        'status',
        'documentos_urls',
        'observacoes_secretaria',
        'analisado_por_id',
        'analisado_em',
    ];

    /**
     * Atributos que devem ser convertidos
     */
    protected $casts = [
        'data_nascimento' => 'date',
        'analisado_em' => 'datetime',
        'documentos_urls' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot do model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            
            // Formatar CPF (remove caracteres especiais)
            if (!empty($model->cpf)) {
                $model->cpf = preg_replace('/\D/', '', $model->cpf);
            }
        });

        static::updating(function ($model) {
            // Se o status mudou para aprovado e não estava aprovado antes
            if ($model->isDirty('status') && 
                $model->status === self::STATUS_APROVADO && 
                $model->getOriginal('status') !== self::STATUS_APROVADO) {
                $model->analisado_em = now();
            }
        });
    }

    /**
     * Relacionamento com Curso
     */
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    /**
     * Relacionamento com Modalidade do Curso
     */
    public function courseModality(): BelongsTo
    {
        return $this->belongsTo(CourseModality::class, 'course_modality_id');
    }

    /**
     * Relacionamento com Usuário que analisou
     */
    public function analisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analisado_por_id');
    }

    /**
     * Relacionamento com Documentos da Inscrição
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'application_id');
    }

    /**
     * Verifica se a inscrição está em análise
     */
    public function isEmAnalise(): bool
    {
        return $this->status === self::STATUS_EM_ANALISE;
    }

    /**
     * Verifica se a inscrição foi aprovada
     */
    public function isAprovado(): bool
    {
        return $this->status === self::STATUS_APROVADO;
    }

    /**
     * Verifica se a inscrição foi rejeitada
     */
    public function isRejeitado(): bool
    {
        return $this->status === self::STATUS_REJEITADO;
    }

    /**
     * Verifica se o candidato já foi matriculado
     */
    public function isMatriculado(): bool
    {
        return $this->status === self::STATUS_MATRICULADO;
    }

    /**
     * Verifica se a inscrição foi cancelada
     */
    public function isCancelado(): bool
    {
        return $this->status === self::STATUS_CANCELADO;
    }

    /**
     * Verifica se a inscrição pode ser editada pelo candidato
     * (somente quando está em análise)
     */
    public function canBeEditedByCandidate(): bool
    {
        return $this->status === self::STATUS_EM_ANALISE;
    }

    /**
     * Aprovar inscrição
     */
    public function approve(string $userId, ?string $observacoes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APROVADO,
            'analisado_por_id' => $userId,
            'analisado_em' => now(),
            'observacoes_secretaria' => $observacoes ?? $this->observacoes_secretaria,
        ]);
    }

    /**
     * Rejeitar inscrição
     */
    public function reject(string $userId, string $motivo): bool
    {
        return $this->update([
            'status' => self::STATUS_REJEITADO,
            'analisado_por_id' => $userId,
            'analisado_em' => now(),
            'observacoes_secretaria' => $motivo,
        ]);
    }

    /**
     * Marcar como matriculado
     */
    public function markAsMatriculado(): bool
    {
        return $this->update([
            'status' => self::STATUS_MATRICULADO,
        ]);
    }

    /**
     * Cancelar inscrição
     */
    public function cancel(): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELADO,
        ]);
    }

    /**
     * Escopo: Inscrições em análise
     */
    public function scopeEmAnalise($query)
    {
        return $query->where('status', self::STATUS_EM_ANALISE);
    }

    /**
     * Escopo: Inscrições aprovadas
     */
    public function scopeAprovados($query)
    {
        return $query->where('status', self::STATUS_APROVADO);
    }

    /**
     * Escopo: Inscrições pendentes de matrícula
     */
    public function scopePendentesMatricula($query)
    {
        return $query->where('status', self::STATUS_APROVADO);
    }

    /**
     * Format CPF for display
     */
    public function getCpfFormatadoAttribute(): string
    {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $this->cpf);
    }

    /**
     * Format RG for display
     */
    public function getRgFormatadoAttribute(): ?string
    {
        if (empty($this->rg)) {
            return null;
        }
        return preg_replace('/(\d{1,2})(\d{3})(\d{3})([0-9X]{1})$/', '$1.$2.$3-$4', $this->rg);
    }

    /**
     * Get full address
     */
    public function getEnderecoCompletoAttribute(): string
    {
        return "{$this->logradouro}, {$this->numero}" . 
               ($this->complemento ? " - {$this->complemento}" : "") . 
               " - {$this->bairro}, {$this->cidade}/{$this->estado}";
    }

    /**
     * Get candidate age
     */
    public function getIdadeAttribute(): int
    {
        return $this->data_nascimento->age;
    }
}
