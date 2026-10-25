@php
    // Sala dedicada: não carrega os menus nem os scripts legados do portal.
    $toArray = static function ($value): array {
        if (is_string($value)) { $value = json_decode($value, true); }
        return is_array($value) ? $value : [];
    };
    $materiaisEstudio = [];
    foreach (array_merge($toArray($aula->materiais), $toArray($aula->anexos), $toArray($aula->materiais_complementares)) as $item) {
        if (is_string($item)) { $item = ['url' => $item]; }
        if (!is_array($item)) { continue; }
        $url = $item['url'] ?? $item['arquivo'] ?? $item['path'] ?? '';
        if (!$url) { continue; }
        $materiaisEstudio[] = ['url' => $url, 'titulo' => $item['titulo'] ?? $item['nome'] ?? 'Material de apoio', 'tipo' => $item['tipo'] ?? ''];
    }
    $cadernoConteudo = $caderno ? json_decode($caderno->conteudo, true) : null;
    if (is_array($cadernoConteudo)) {
        $cadernoConteudo['text'] = $progresso->notas ?? '';
    }
    $dadosEstudio = [
        'demo' => false,
        'userId' => (string) auth()->id(),
        'notebook' => $cadernoConteudo,
        'revision' => $caderno->revisao ?? 0,
        'course' => ['title' => $curso->titulo, 'url' => route('aluno.curso.show', $curso->id), 'total' => $totalAulas, 'done' => $aulasConcluidas, 'percent' => $progressoCurso],
        'lesson' => [
            'id' => $aula->id, 'title' => $aula->titulo, 'module' => $modulo->titulo ?? $modulo->nome ?? 'Módulo',
            'description' => $aula->descricao, 'html' => $aula->conteudo_texto,
            'duration' => $aula->duracao_minutos ?? $aula->duracao,
            'objectives' => $toArray($aula->objetivos), 'video' => $aula->video_url,
            'transcript' => \App\Support\TranscricaoVideo::preparar($aula->transcricao_video),
            'pdf' => $aula->pdf_url, 'pdfTitle' => $aula->pdf_titulo ?? 'Material da aula',
            'audio' => $aula->audio_url, 'audioTitle' => $aula->audio_titulo ?? 'Áudio da aula',
            'blocks' => $aula->getBlocosOrdenados(), 'materials' => $materiaisEstudio,
            'download' => (bool) $aula->permite_download, 'commentsAllowed' => (bool) $aula->permite_comentarios,
            'done' => (bool) ($aulaProgresso->concluida ?? $progresso->concluida),
            'notes' => $progresso->notas ?? '', 'minScore' => $aula->nota_minima_aprovacao ?: 60,
            'questions' => array_map(static function ($q) {
                unset($q['resposta_correta']);
                if (isset($q['alternativas'])) { $q['alternativas'] = array_map(static function ($a) { if (is_array($a)) { unset($a['correta']); } return $a; }, $q['alternativas']); }
                return $q;
            }, $toArray($aula->questoes)),
            'legacyQuiz' => array_map(static function ($q) { unset($q['resposta_correta']); return $q; }, $toArray($aula->quiz)),
        ],
        'modules' => $modulos->map(fn ($mod) => [
            'id' => $mod->id, 'title' => $mod->titulo ?? $mod->nome ?? 'Módulo',
            'lessons' => $mod->aulas->map(fn ($item) => [
                'id' => $item->id, 'title' => $item->titulo, 'url' => route('aluno.aula', $item->id),
                'done' => (bool) ($item->concluida_portal ?? false), 'available' => (bool) $item->liberada,
                'duration' => $item->duracao_minutos ?? $item->duracao,
            ])->values()->all(),
        ])->values()->all(),
        'previous' => $aulaAnterior && $aulaAnterior->liberada ? route('aluno.aula', $aulaAnterior->id) : null,
        'next' => $proximaAula && $proximaAula->liberada ? route('aluno.aula', $proximaAula->id) : null,
        'comments' => $comentarios->map(fn ($item) => [
            'name' => $item->usuario->name ?? 'Aluno', 'text' => $item->comentario,
            'date' => $item->created_at?->format('d/m/Y H:i'),
            'replies' => $item->respostas->where('aprovado', true)->map(fn ($reply) => [
                'name' => $reply->usuario->name ?? 'Aluno', 'text' => $reply->comentario,
                'date' => $reply->created_at?->format('d/m/Y H:i'),
            ])->values()->all(),
        ])->values()->all(),
        'endpoints' => [
            'notebook' => route('aluno.aula.caderno', $aula->id),
            'notes' => route('aluno.aula.notas', $aula->id), 'complete' => route('aluno.aula.concluir', $aula->id),
            'progress' => route('aluno.aula.progresso', $aula->id), 'time' => route('aluno.aula.tempo', $aula->id),
            'quiz' => route('aluno.aula.quiz', $aula->id), 'comment' => route('aluno.aula.comentar', $aula->id),
        ],
        'pdfLibrary' => asset('vendor/estudio/pdfjs/pdf.min.mjs'),
        'pdfWorker' => asset('vendor/estudio/pdfjs/pdf.worker.min.mjs'),
    ];
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#061a30">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title>{{ $aula->titulo }} — Sala de Aula CETSADP</title>
    <link rel="stylesheet" href="{{ asset('vendor/portal/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/portal/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/estudio-aula.css') }}?v=78">
    <script>window.CETSADP_STUDIO = {{ \Illuminate\Support\Js::from($dadosEstudio) }};</script>
    <script defer src="{{ asset('vendor/estudio/dompurify/purify.min.js') }}"></script>
    <script type="module" src="{{ asset('js/estudio-aula.js') }}?v=78"></script>
</head>
<body>
    @include('aluno.partials.estudio-interface')
</body>
</html>
