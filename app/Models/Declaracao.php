<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Declaracao extends Model
{
    use HasFactory;

    protected $table = 'declaracoes';

    protected $fillable = [
        'matricula_id',
        'codigo_validacao',
        'tipo_documento',
        'data_emissao',
        'quantidade_visualizacoes',
        'ultima_visualizacao',
        'hash_documento',
    ];

    protected $casts = [
        'data_emissao' => 'datetime',
        'ultima_visualizacao' => 'datetime',
    ];

    /**
     * Relacionamento com Matrícula
     */
    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    /**
     * Gera um código de validação único
     */
    public static function gerarCodigoValidacao(): string
    {
        do {
            $codigo = strtoupper(Str::random(8) . '-' . Str::random(4));
        } while (self::where('codigo_validacao', $codigo)->exists());

        return $codigo;
    }

    /**
     * Registra uma visualização do documento
     */
    public function registrarVisualizacao(): void
    {
        $this->increment('quantidade_visualizacoes');
        $this->update(['ultima_visualizacao' => now()]);
    }

    /**
     * Verifica se o documento ainda é válido (emitido nos últimos 6 meses)
     */
    public function isValido(): bool
    {
        return $this->data_emissao->diffInMonths(now()) < 6;
    }

    /**
     * Gera o hash do documento para verificação de integridade
     */
    public function gerarHash(): string
    {
        $data = [
            'matricula_id' => $this->matricula_id,
            'codigo_validacao' => $this->codigo_validacao,
            'data_emissao' => $this->data_emissao->format('Y-m-d H:i:s'),
        ];

        return hash('sha256', json_encode($data));
    }
}