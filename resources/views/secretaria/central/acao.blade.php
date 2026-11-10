@php
    $credencial = in_array($acao, ['ativacao', 'recuperacao', 'temporaria']);
    $indisponivel = $credencial && !$aluno->ativo;
    $emailElegivel = filled($aluno->email) && ($acao !== 'recuperacao' || filled($aluno->email_verified_at));
@endphp
<details class="sc-operation" id="operacao-{{ $acao }}">
    <summary><span><strong>{{ $titulo }}</strong><small>{{ $descricao }}</small></span>@include('secretaria.central.icone', ['nome' => $credencial ? 'chave' : 'escudo'])</summary>
    <form action="{{ route('secretaria.acessos.acao', $aluno) }}" method="post" class="sc-operation-body sc-stack">
        @csrf
        <input type="hidden" name="acao" value="{{ $acao }}">
        <input type="hidden" name="estado" value="{{ $estado }}">
        @if ($indisponivel)<p class="sc-alert sc-alert-warning">A conta está bloqueada. Desbloqueie-a antes de emitir uma credencial.</p>@endif
        <fieldset @disabled($indisponivel) class="sc-stack sc-plain-fieldset">
            <legend class="sc-sr-only">{{ $titulo }}</legend>
            @if ($credencial)
                <div class="sc-contact-review"><p><strong>WhatsApp do aluno:</strong> {{ $aluno->whatsapp ?: 'Não cadastrado' }}</p><p><strong>E-mail:</strong> {{ $aluno->email ?: 'Não cadastrado' }}</p><small>A emissão revalida o cadastro e o destino. Alterações posteriores podem invalidar a entrega.</small></div>
                <div class="sc-field"><label for="{{ $acao }}-canal">Canal de entrega <span class="sc-required">*</span></label><select id="{{ $acao }}-canal" name="canal" required>
                    <option value="">Escolha o canal</option>
                    <option value="whatsapp" @disabled(!$aluno->whatsapp)>WhatsApp{{ !$aluno->whatsapp ? ' — não cadastrado' : '' }}</option>
                    <option value="email" @disabled(!$emailElegivel)>E-mail{{ !$emailElegivel ? ' — indisponível para esta ação' : '' }}</option>
                    @if ($acao === 'temporaria')<option value="presencial">Atendimento presencial</option>@endif
                </select></div>
                @if ($acao === 'recuperacao' && !$aluno->email_verified_at)<p class="sc-note">A recuperação por e-mail exige endereço confirmado. O atendimento por WhatsApp não confirma o e-mail.</p>@endif
                @if ($acao === 'temporaria')<p class="sc-alert sc-alert-warning">O código de 8 dígitos vale por 30 minutos e será exibido uma única vez. A emissão substitui credenciais anteriores e encerra as sessões. O aluno deverá escolher uma senha definitiva.</p>@else<p class="sc-note">O link é individual, expira e só pode ser usado uma vez. Emissões anteriores podem ser revogadas. Você revisará a mensagem antes do envio.</p>@endif
            @elseif ($acao === 'bloquear')
                <p class="sc-alert sc-alert-warning">O aluno perderá o acesso, inclusive nas sessões abertas. O histórico acadêmico será preservado.</p>
            @elseif ($acao === 'desbloquear')
                <p class="sc-note">Desbloquear a conta não confirma e-mail nem conclui o primeiro acesso.</p>
            @else
                <p class="sc-note">Encerra as sessões e credenciais persistentes do aluno. A conta permanece com a situação atual.</p>
            @endif
            <div class="sc-field"><label for="{{ $acao }}-motivo">Motivo do atendimento <span class="sc-required">*</span></label><textarea id="{{ $acao }}-motivo" name="motivo" rows="2" maxlength="500" required></textarea><small>Não inclua senhas, códigos ou documentos no motivo.</small></div>
            <label class="sc-check"><input type="checkbox" name="destino_conferido" value="1" required><span>{{ $credencial ? 'Conferi a identidade e o destino com o próprio aluno, ou sua identidade para entrega presencial.' : 'Conferi a identidade do aluno e confirmo os efeitos desta ação.' }}</span></label>
            <div class="sc-field"><label for="{{ $acao }}-senha">Sua senha de Secretaria <span class="sc-required">*</span></label><input id="{{ $acao }}-senha" name="senha_confirmacao" type="password" autocomplete="current-password" required></div>
            <button class="sc-button {{ in_array($acao, ['bloquear', 'sessoes']) ? 'sc-danger' : 'sc-primary' }}" type="submit">{{ $credencial ? 'Confirmar e preparar entrega' : $titulo }}</button>
        </fieldset>
    </form>
</details>
