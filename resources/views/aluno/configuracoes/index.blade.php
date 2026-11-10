@extends('layouts.aluno')

@section('title', 'Configurações — CETSADP')
@section('header_title', 'Configurações')
@section('header_subtitle', 'Preferências da conta')

@section('content')
    @include('aluno.partials.recurso-em-preparacao', [
        'titulo' => 'Configurações do portal',
        'icone' => 'fa-sliders',
        'descricao' => 'As preferências avançadas do portal ainda estão em desenvolvimento. Nenhuma configuração é alterada nesta tela.',
        'orientacao' => 'Para atualizar seus dados pessoais ou sua senha, use a página Meu Perfil, que já está disponível.',
        'rotaPrincipal' => 'aluno.perfil',
        'textoPrincipal' => 'Ir para Meu Perfil',
        'rotaSecundaria' => 'aluno.dashboard',
        'textoSecundario' => 'Voltar ao início',
    ])
@endsection
