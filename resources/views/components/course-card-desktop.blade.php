@props(['course'])
@php
    $portalProgress = max(0, min(100, (float) ($course->progresso ?? 0)));
    $portalCover = $course->imagem ?? null;
    if (!$portalCover && !empty($course->capa)) {
        $portalCover = str_starts_with($course->capa, 'http') ? $course->capa : asset('storage/' . ltrim($course->capa, '/'));
    }
@endphp
<a class="portal-course-row" href="{{ route('aluno.curso.show', $course->id) }}">
    <div class="portal-course-cover">
        @if($portalCover)
            <img src="{{ $portalCover }}" alt="" loading="lazy" width="128" height="80">
        @else
            <img src="{{ asset('assets/portal/biblia-banner.webp') }}" alt="" loading="lazy" width="128" height="80">
            <span>{{ $course->titulo }}</span>
        @endif
    </div>
    <div class="portal-course-info">
        <h3>{{ $course->titulo }}</h3>
        <p>{{ $course->instrutor ?? 'Corpo Docente' }}</p>
        <div class="portal-progress-line"><div class="portal-progress" role="progressbar" aria-label="Progresso de {{ $course->titulo }}" aria-valuenow="{{ $portalProgress }}" aria-valuemin="0" aria-valuemax="100"><span style="width: {{ $portalProgress }}%"></span></div><b>{{ $portalProgress }}%</b></div>
    </div>
    <i class="fa-solid fa-chevron-right portal-course-arrow" aria-hidden="true"></i>
</a>
