@extends('errors.layout')

@section('titulo', 'Acesso não permitido')
@section('descricao')
    @if(request()->is('aluno/acesso-assistido*'))
        Não foi possível continuar. Sua credencial pode estar inválida ou expirada. Solicite uma nova à Secretaria.
    @else
        Você não tem permissão para acessar esta página.
    @endif
@endsection
