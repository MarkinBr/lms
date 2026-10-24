<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EventoPublico extends Model {
    protected $table = 'eventos_publicos';
    protected $fillable = ['titulo','descricao','categoria','data_inicio','data_fim','local','vagas','imagem_url','inscricao_url','publicado'];
    protected $casts = ['data_inicio'=>'datetime','data_fim'=>'datetime','publicado'=>'boolean','vagas'=>'integer'];
    public const CATEGORIAS = ['aula'=>'Aulas especiais','seminario'=>'Seminários','workshop'=>'Workshops','cerimonia'=>'Cerimônias','retiro'=>'Retiros','congresso'=>'Congressos','outros'=>'Outros'];
    public function getCategoriaLabelAttribute(): string { return self::CATEGORIAS[$this->categoria] ?? 'Evento'; }
    public function scopeVisiveis($query) { return $query->where('publicado',true)->where('data_inicio','>=',now())->orderBy('data_inicio'); }
}
