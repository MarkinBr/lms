<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Aula extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Aula $aula): void {
            if (! $aula->disciplina_id) {
                if (! $aula->exists || $aula->isDirty('disciplina_id')) {
                    throw ValidationException::withMessages([
                        'disciplina_id' => 'Informe a disciplina da aula.',
                    ]);
                }

                // Registros anteriores à Issue #57 continuam editáveis até a
                // Secretaria concluir o vínculo manual com uma disciplina.
                return;
            }

            $disciplina = Disciplina::query()->find($aula->disciplina_id);

            if (! $disciplina) {
                throw ValidationException::withMessages([
                    'disciplina_id' => 'A disciplina selecionada não existe.',
                ]);
            }

            if (! $disciplina->modulo_id) {
                throw ValidationException::withMessages([
                    'disciplina_id' => 'Vincule a disciplina a um módulo antes de cadastrar aulas.',
                ]);
            }

            $modulo = $disciplina->modulo;

            if (! $modulo || (int) $modulo->curso_id !== (int) $disciplina->curso_id) {
                throw ValidationException::withMessages([
                    'disciplina_id' => 'A disciplina possui um vínculo acadêmico inconsistente.',
                ]);
            }

            if ($aula->modulo_id && (int) $disciplina->modulo_id !== (int) $aula->modulo_id) {
                throw ValidationException::withMessages([
                    'disciplina_id' => 'A disciplina selecionada não pertence ao módulo da aula.',
                ]);
            }

            // Mantém o vínculo legado preenchido para que o portal e relatórios
            // antigos continuem encontrando a aula dentro do módulo.
            $aula->modulo_id = $disciplina->modulo_id;
        });
    }

    protected $fillable = [
        'modulo_id',
        'disciplina_id',
        'titulo',
        'descricao',
        'conteudo_texto',
        'tipo_conteudo',
        'video_url',
        'video_provider',
        'duracao',
        'materiais',
        'ordem',
        'liberada',
        'data_liberacao',
        'tipo_liberacao',

        // Novos campos - Estratégia Style
        'audio_url',
        'audio_titulo',
        'pdf_url',
        'pdf_titulo',
        'thumbnail',
        'blocos_conteudo',
        'questoes',
        'questoes_quantidade',
        'questoes_aleatorias',
        'nota_minima_aprovacao',
        'anexos',
        'permite_download',
        'permite_comentarios',
        'tempo_minimo_minutos',
    ];

    protected $casts = [
        'materiais' => 'array',
        'blocos_conteudo' => 'array',
        'questoes' => 'array',
        'anexos' => 'array',
        'liberada' => 'boolean',
        'permite_download' => 'boolean',
        'permite_comentarios' => 'boolean',
        'questoes_aleatorias' => 'boolean',
        'data_liberacao' => 'datetime',
        'questoes_quantidade' => 'integer',
        'nota_minima_aprovacao' => 'integer',
        'tempo_minimo_minutos' => 'integer',
    ];

    protected $attributes = [
        'tipo_conteudo' => 'video',
        'liberada' => true,
        'tipo_liberacao' => 'imediata',
        'permite_download' => true,
        'permite_comentarios' => true,
        'questoes_aleatorias' => false,
        'questoes_quantidade' => 0,
    ];

    // ═══════════════════════════════════
    // RELACIONAMENTOS
    // ═══════════════════════════════════

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class);
    }

    /**
     * Disciplina curricular à qual a aula pertence.
     *
     * `modulo_id` permanece por compatibilidade com aulas legadas; aulas
     * novas devem ser criadas pela disciplina e também recebem o módulo
     * correspondente automaticamente.
     */
    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function comentarios()
    {
        return $this->hasMany(ComentarioAula::class)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Relacionamento Mágico: Traz APENAS o progresso do usuário logado.
     */
    public function progresso()
    {
        return $this->hasOne(Progresso::class)->where('user_id', Auth::id());
    }

    /**
     * Relacionamento Geral: Traz todos os progressos.
     */
    public function progressos()
    {
        return $this->hasMany(Progresso::class);
    }

    // ═══════════════════════════════════
    // VERIFICAÇÕES DE CONTEÚDO
    // ═══════════════════════════════════

    public function foiConcluidaPor($userId)
    {
        return $this->progressos()
            ->where('user_id', $userId)
            ->where('concluida', true)
            ->exists();
    }

    public function getProgressoDoAluno($userId)
    {
        return $this->progressos()
            ->where('user_id', $userId)
            ->first();
    }

    public function temConteudoTexto()
    {
        return ! empty($this->conteudo_texto) &&
               in_array($this->tipo_conteudo, ['texto', 'misto']);
    }

    public function temVideo()
    {
        return ! empty($this->video_url) &&
               in_array($this->tipo_conteudo, ['video', 'misto']);
    }

    public function temAudio()
    {
        return ! empty($this->audio_url);
    }

    public function temPdf()
    {
        return ! empty($this->pdf_url);
    }

    public function temQuestoes()
    {
        return ! empty($this->questoes) && count($this->questoes) > 0;
    }

    public function temBlocosConteudo()
    {
        return ! empty($this->blocos_conteudo) && count($this->blocos_conteudo) > 0;
    }

    public function temMateriais()
    {
        return ! empty($this->materiais) && count($this->materiais) > 0;
    }

    public function temAnexos()
    {
        return ! empty($this->anexos) && count($this->anexos) > 0;
    }

    // ═══════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════

    /**
     * Retorna a URL de embed do vídeo (YouTube, Vimeo, etc.)
     */
    public function getVideoEmbedUrl(): ?string
    {
        $url = $this->video_url;
        if (empty($url)) {
            return null;
        }

        // YouTube
        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            preg_match('/(?:v=|\\/)([\\w-]{11})/', $url, $matches);
            $videoId = $matches[1] ?? null;

            return $videoId ? "https://www.youtube.com/embed/{$videoId}" : $url;
        }

        // Vimeo
        if (str_contains($url, 'vimeo.com')) {
            preg_match('/vimeo\\.com\\/(\\d+)/', $url, $matches);
            $videoId = $matches[1] ?? null;

            return $videoId ? "https://player.vimeo.com/video/{$videoId}" : $url;
        }

        return $url;
    }

    /**
     * Retorna os blocos ordenados
     */
    public function getBlocosOrdenados(): array
    {
        $blocos = $this->blocos_conteudo ?? [];
        usort($blocos, fn ($a, $b) => ($a['ordem'] ?? 0) <=> ($b['ordem'] ?? 0));

        return $blocos;
    }

    /**
     * Calcula a nota do quiz
     */
    public function calcularNotaQuiz(array $respostas): int
    {
        if (! $this->temQuestoes()) {
            return 0;
        }

        $totalPontos = 0;
        $pontosObtidos = 0;

        foreach ($this->questoes as $index => $questao) {
            $pontos = $questao['pontos'] ?? 1;
            $totalPontos += $pontos;

            if (isset($respostas[$index])) {
                if ($questao['tipo'] === 'multipla_escolha') {
                    $corretas = collect($questao['alternativas'] ?? [])
                        ->filter(fn ($alt) => $alt['correta'] ?? false)
                        ->pluck('letra')
                        ->toArray();
                    if (in_array($respostas[$index], $corretas)) {
                        $pontosObtidos += $pontos;
                    }
                } elseif (in_array($questao['tipo'], ['verdadeiro_falso', 'certo_errado'])) {
                    if ($respostas[$index] === ($questao['resposta_correta'] ?? false)) {
                        $pontosObtidos += $pontos;
                    }
                }
            }
        }

        return $totalPontos > 0 ? round(($pontosObtidos / $totalPontos) * 100) : 0;
    }
}
