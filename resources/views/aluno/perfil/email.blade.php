@extends('layouts.aluno')

@section('title', 'E-mail pessoal')
@section('header_title', 'E-mail pessoal')
@section('header_subtitle', 'Confirme seu endereço para acesso e recuperação de senha')

@section('content')
<div class="max-w-2xl mx-auto p-6 space-y-6">
    <h1 class="text-2xl font-bold">Seu e-mail pessoal</h1>
    <p>Sua matrícula de acesso: <strong>{{ $aluno->matricula_login ?? 'Consulte a secretaria' }}</strong>.</p>
    <p>Você pode continuar estudando sem e-mail. Para ajuda com acesso ou senha, procure a secretaria.</p>
    <p>E-mail atual: <strong>{{ $aluno->email ?? 'Não cadastrado' }}</strong>
        @if($aluno->email) — {{ $aluno->email_verified_at ? 'Confirmado' : 'Ainda não confirmado' }} @endif
    </p>
    @if($aluno->email_confirmacao_pendente)
        <p role="status">Aguardando confirmação de {{ $aluno->email_confirmacao_pendente }}. O endereço atual permanece até a confirmação. Uma nova solicitação invalida o link anterior.</p>
    @endif
    @if(session('status'))
        <p role="status" class="rounded-lg bg-green-50 p-4">{{ session('status') }}</p>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-lg bg-red-50 p-4">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('aluno.email.solicitar') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email-pessoal" class="block font-semibold">E-mail pessoal para confirmar</label>
            <input id="email-pessoal" name="email" type="email" autocomplete="email" required maxlength="255"
                value="{{ old('email', $aluno->email_confirmacao_pendente ?? $aluno->email) }}" class="w-full rounded-lg border-gray-300">
        </div>
        <div>
            <label for="senha-atual" class="block font-semibold">Senha atual</label>
            <input id="senha-atual" name="senha_atual" type="password" autocomplete="current-password" required maxlength="1024" class="w-full rounded-lg border-gray-300">
        </div>
        <button type="submit" class="rounded-lg bg-[#061a30] text-white px-6 py-3">Enviar link de confirmação</button>
    </form>
    <p>O link expira em 60 minutos e só pode ser usado uma vez. Abra-o autenticado com sua matrícula.</p>
    <a href="{{ route('aluno.perfil') }}" class="underline">Voltar ao perfil</a>
</div>
@endsection