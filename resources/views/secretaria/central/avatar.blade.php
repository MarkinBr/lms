<span class="sc-avatar {{ !empty($grande) ? 'sc-avatar-large' : '' }}">
    @if ($aluno->foto)
        <img src="{{ route('secretaria.acessos.foto.ver', $aluno) }}" alt="Foto de {{ $aluno->name }}" width="{{ !empty($grande) ? 104 : 48 }}" height="{{ !empty($grande) ? 104 : 48 }}" @if(empty($grande)) loading="lazy" @endif decoding="async">
    @else
        <span aria-label="Sem foto cadastrada">{{ mb_strtoupper(mb_substr(trim($aluno->name ?? ''), 0, 1)) ?: '—' }}</span>
    @endif
</span>
