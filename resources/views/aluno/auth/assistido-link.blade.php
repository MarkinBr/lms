@extends('aluno.auth.assistido-layout')
@section('titulo', 'Definir senha de acesso')
@section('conteudo')
    <p id="orientacao">Continue para conferir seu link individual e escolher uma nova senha.</p>
    <form method="post" action="{{ route('aluno.assistido.comprovar', $id) }}">
        @csrf
        <input id="segredo" name="segredo" type="hidden" autocomplete="off">
        <button id="continuar" type="submit" disabled>Continuar com segurança</button>
    </form>
    <noscript><p>Ative o JavaScript para usar o link recebido ou solicite um código de atendimento à secretaria.</p></noscript>
    <script>
        (() => {
            const segredo = new URLSearchParams(location.hash.slice(1)).get('segredo');
            history.replaceState(null, '', location.pathname);
            if (segredo && /^[a-f0-9]{64}$/.test(segredo)) {
                document.getElementById('segredo').value = segredo;
                document.getElementById('continuar').disabled = false;
            } else {
                document.getElementById('orientacao').textContent = 'Abra o link completo recebido no atendimento. Se ele expirou, solicite outro à secretaria.';
            }
        })();
    </script>
@endsection
