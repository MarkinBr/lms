@extends('layouts.aluno')

@section('title', 'Fórum de estudos — CETSADP')
@section('header_title', 'Fórum de estudos')
@section('header_subtitle', 'Comunidade acadêmica')

@section('content')
    @include('aluno.partials.recurso-em-preparacao', [
        'titulo' => 'Fórum de estudos',
        'icone' => 'fa-people-group',
        'descricao' => 'Um espaço de diálogo acadêmico está sendo preparado para nossa comunidade. A publicação de tópicos e respostas ainda não está disponível.',
        'orientacao' => 'Acompanhe os avisos institucionais no mural ou envie uma solicitação à Secretaria quando precisar de orientação.',
        'rotaPrincipal' => 'aluno.comunicacao',
        'textoPrincipal' => 'Ver mural de avisos',
        'rotaSecundaria' => 'aluno.secretaria',
        'textoSecundario' => 'Falar com a Secretaria',
    ])
@endsection
