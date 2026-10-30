<div class="sc-badges" aria-label="Situação do acesso">
    <span class="sc-badge {{ $aluno->ativo ? 'sc-badge-green' : 'sc-badge-red' }}">{{ $aluno->ativo ? 'Conta ativa' : 'Conta bloqueada' }}</span>
    <span class="sc-badge {{ $aluno->primeiro_acesso_pendente ? 'sc-badge-gold' : 'sc-badge-neutral' }}">{{ $aluno->primeiro_acesso_pendente ? 'Primeiro acesso pendente' : 'Primeiro acesso concluído' }}</span>
    <span class="sc-badge {{ $aluno->email_verified_at ? 'sc-badge-green' : 'sc-badge-neutral' }}">{{ $aluno->email_verified_at ? 'E-mail confirmado' : 'E-mail não confirmado' }}</span>
    @if ($aluno->email_confirmacao_pendente)
        <span class="sc-badge sc-badge-gold">Novo e-mail aguardando confirmação</span>
    @endif
</div>
