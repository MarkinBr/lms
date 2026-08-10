<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * ApplicationDocument - Documento de Inscrição
 * 
 * Representa um documento enviado pelo candidato durante a inscrição
 * 
 * @property string $id
 * @property string $application_id
 * @property string $tipo (foto_3x4, rg, cpf, comprovante_residencia, historico_escolar, certificado_conclusao, outro)
 * @property string $nome_original
 * @property string $nome_armazenado
 * @property string $mime_type
 * @property int $tamanho
 * @property string $caminho_arquivo
 * @property string|null $url_publica
 * @property bool $validado
 * @property string|null $observacoes_validacao
 * @property string|null $validado_por_id
 * @property \Carbon\Carbon|null $validado_em
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class ApplicationDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'application_documents';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Atributos que podem ser preenchidos em massa
     */
    protected $fillable = [
        'application_id',
        'tipo',
        'nome_original',
        'nome_armazenado',
        'mime_type',
        'tamanho',
        'caminho_arquivo',
        'url_publica',
        'validado',
        'observacoes_validacao',
        'validado_por_id',
        'validado_em',
    ];

    /**
     * Atributos que devem ser convertidos
     */
    protected $casts = [
        'validado' => 'boolean',
        'validado_em' => 'datetime',
        'tamanho' => 'integer',
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
        });
    }

    /**
     * Relacionamento com Application
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * Relacionamento com Usuário que validou
     */
    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por_id');
    }

    /**
     * Validar documento
     */
    public function validateDocument(string $userId, ?string $observacoes = null): bool
    {
        return $this->update([
            'validado' => true,
            'validado_por_id' => $userId,
            'validado_em' => now(),
            'observacoes_validacao' => $observacoes ?? $this->observacoes_validacao,
        ]);
    }

    /**
     * Invalidar documento
     */
    public function invalidateDocument(string $userId, string $motivo): bool
    {
        return $this->update([
            'validado' => false,
            'validado_por_id' => $userId,
            'validado_em' => now(),
            'observacoes_validacao' => $motivo,
        ]);
    }

    /**
     * Get file size formatted
     */
    public function getTamanhoFormatadoAttribute(): string
    {
        $bytes = $this->tamanho;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get document type label
     */
    public function getTipoLabelAttribute(): string
    {
        $labels = [
            'foto_3x4' => 'Foto 3x4',
            'rg' => 'RG',
            'cpf' => 'CPF',
            'comprovante_residencia' => 'Comprovante de Residência',
            'historico_escolar' => 'Histórico Escolar',
            'certificado_conclusao' => 'Certificado de Conclusão',
            'outro' => 'Outro',
        ];
        
        return $labels[$this->tipo] ?? $this->tipo;
    }

    /**
     * Check if document is validated
     */
    public function isValidado(): bool
    {
        return $this->validado === true;
    }

    /**
     * Get full storage path
     */
    public function getCaminhoCompletoAttribute(): string
    {
        return storage_path('app/' . $this->caminho_arquivo);
    }

    /**
     * Get temporary URL (for S3/R2)
     */
    public function getUrlTemporariaAttribute(): ?string
    {
        if ($this->url_publica) {
            return $this->url_publica;
        }
        
        // Se estiver usando S3/R2, gerar URL temporária
        try {
            return \Storage::disk('s3')->temporaryUrl(
                $this->caminho_arquivo,
                now()->addMinutes(15)
            );
        } catch (\Exception $e) {
            return null;
        }
    }
}
