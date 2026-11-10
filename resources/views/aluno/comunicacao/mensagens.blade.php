@extends('layouts.aluno')

@section('title', 'Mensagens — CETSADP')
@section('header_title', 'Mensagens')
@section('header_subtitle', 'Comunicação acadêmica')

@section('content')
    @include('aluno.partials.recurso-em-preparacao', [
        'titulo' => 'Mensagens diretas',
        'icone' => 'fa-envelope-open-text',
        'descricao' => 'O envio e o recebimento de mensagens diretas ainda não estão disponíveis neste módulo. Não há uma caixa de entrada ativa aqui.',
        'orientacao' => 'Para consultar comunicados da instituição, visite o mural de avisos. Para solicitar atendimento, utilize a Secretaria.',
        'rotaPrincipal' => 'aluno.comunicacao',
        'textoPrincipal' => 'Abrir mural de avisos',
        'rotaSecundaria' => 'aluno.secretaria',
        'textoSecundario' => 'Atendimento da Secretaria',
    ])
@endsection
