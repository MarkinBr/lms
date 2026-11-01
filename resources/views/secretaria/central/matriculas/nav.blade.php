<nav class="sc-heading-actions" aria-label="Gestão de matrículas e turmas">
    <a class="sc-button {{ ($secao ?? 'matriculas') === 'matriculas' ? 'sc-primary' : '' }}" href="{{ route('secretaria.matriculas.index') }}">Matrículas</a>
    <a class="sc-button {{ ($secao ?? '') === 'turmas' ? 'sc-primary' : '' }}" href="{{ route('secretaria.matriculas.turmas') }}">Turmas</a>
</nav>
