@extends('secretaria.central.layout')
@section('title', $novo ? 'Revisar novo acesso' : 'Revisar alterações cadastrais')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.acessos.index') }}">Acessos dos alunos</a><span aria-hidden="true">/</span><span aria-current="page">Revisão do atendimento</span>@endsection
@section('content')
    @php
        $rotulos = ['name' => 'Nome completo', 'cpf' => 'CPF', 'rg' => 'RG', 'data_nasc' => 'Data de nascimento', 'genero' => 'Sexo / gênero', 'estado_civil' => 'Estado civil', 'nacionalidade' => 'Nacionalidade', 'naturalidade_cidade' => 'Naturalidade — cidade', 'naturalidade_estado' => 'Naturalidade — UF', 'email' => 'E-mail pessoal', 'telefone' => 'Telefone', 'whatsapp' => 'WhatsApp do aluno', 'cep' => 'CEP', 'endereco' => 'Logradouro', 'numero' => 'Número', 'complemento' => 'Complemento', 'bairro' => 'Bairro', 'cidade' => 'Cidade', 'estado' => 'Estado — UF', 'escolaridade' => 'Escolaridade', 'ano_conclusao' => 'Ano de conclusão', 'formacao_teologica' => 'Formação teológica anterior', 'instituicao_anterior' => 'Instituição anterior', 'como_conheceu' => 'Como conheceu o CETSADP', 'indicacao_nome' => 'Nome de quem indicou'];
        $formatarValor = static function ($campo, $valor) {
            if ($valor === null || $valor === '') { return 'Não informado'; }
            if ($campo === 'formacao_teologica') { return $valor ? 'Sim' : 'Não'; }
            if ($campo === 'data_nasc') { return \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y'); }
            if ($campo === 'genero') { return ['M' => 'Masculino', 'F' => 'Feminino', 'Outro' => 'Outro', 'masculino' => 'Masculino', 'feminino' => 'Feminino', 'outro' => 'Outro'][(string) $valor] ?? $valor; }
            if ($campo === 'como_conheceu') { return ['indicacao' => 'Indicação', 'redes_sociais' => 'Redes sociais', 'igreja' => 'Igreja', 'site' => 'Site'][(string) $valor] ?? $valor; }
            return is_scalar($valor) ? str_replace('_', ' ', (string) $valor) : 'Não informado';
        };
        $canalRevisao = data_get($dados, 'canal', $canal ?? '');
        $destinoRevisao = data_get($dados, $canalRevisao === 'whatsapp' ? 'whatsapp' : 'email');
    @endphp
    <div class="sc-intro"><div><p class="sc-eyebrow">Conferir antes de confirmar</p><h1>{{ $novo ? 'Revisar cadastro e entrega' : 'Revisar alterações' }}</h1><p>{{ $novo ? 'Confira os dados do aluno e o canal das instruções iniciais.' : 'Confira o antes e o depois. A inscrição de origem e os vínculos acadêmicos permanecem preservados.' }}</p></div><span class="sc-tag">@include('secretaria.central.icone', ['nome' => 'escudo']) Revisão do atendimento</span></div>
    <form action="{{ $novo ? route('secretaria.acessos.store') : route('secretaria.acessos.update', $aluno) }}" method="post" class="sc-layout">
        @csrf
        @unless ($novo)@method('PUT')<input type="hidden" name="_estado" value="{{ $estado }}">@endunless
        <input type="hidden" name="confirmar" value="1">
        @isset($operacao)<input type="hidden" name="operacao" value="{{ $operacao }}">@endisset
        <input type="hidden" name="motivo" value="{{ $motivo }}">
        <input type="hidden" name="destino_conferido" value="{{ data_get($dados, 'destino_conferido', $destino_conferido ?? '1') }}">
        @if ($canalRevisao !== '')<input type="hidden" name="canal" value="{{ $canalRevisao }}">@endif
        @foreach ($rotulos as $campo => $rotulo)
            @if (array_key_exists($campo, $dados) && (is_scalar($dados[$campo]) || $dados[$campo] === null))<input type="hidden" name="{{ $campo }}" value="{{ is_bool($dados[$campo]) ? (int) $dados[$campo] : $dados[$campo] }}">@endif
        @endforeach
        <div class="sc-stack">
            <section class="sc-panel"><div class="sc-panel-heading"><h2>{{ $novo ? 'Dados do novo acesso' : $aluno->name }}</h2><p>{{ $novo ? 'A matrícula de acesso será gerada pelo servidor na confirmação.' : 'Somente os campos alterados são destacados abaixo.' }}</p></div>
                <dl class="sc-review-list">
                    @foreach ($rotulos as $campo => $rotulo)
                        @php
                            $valorAntes = $aluno?->getAttribute($campo);
                            $valorDepois = array_key_exists($campo, $dados) ? $dados[$campo] : $valorAntes;
                            if ($campo === 'data_nasc' && $valorAntes) { $valorAntes = \Illuminate\Support\Carbon::parse($valorAntes)->format('Y-m-d'); }
                            $mudou = (string) $valorAntes !== (string) $valorDepois;
                        @endphp
                        @if (($novo && array_key_exists($campo, $dados)) || (!$novo && $mudou))
                            <div class="sc-review-change"><dt>{{ $rotulo }}</dt><dd>@unless ($novo)<span><small>Antes</small>{{ $formatarValor($campo, $valorAntes) }}</span>@include('secretaria.central.icone', ['nome' => 'seta'])@endunless<span class="sc-review-after"><small>{{ $novo ? 'Cadastro' : 'Depois' }}</small>{{ $formatarValor($campo, $valorDepois) }}</span></dd></div>
                        @endif
                    @endforeach
                </dl>
            </section>
            <section class="sc-panel"><div class="sc-panel-heading"><h2>Motivo do atendimento</h2></div><div class="sc-panel-body"><p class="sc-preserve-lines">{{ $motivo }}</p></div></section>
            @if ($novo)
                <section class="sc-panel"><div class="sc-panel-heading"><h2>Primeiro acesso do aluno</h2></div><div class="sc-panel-body sc-stack"><dl class="sc-summary"><div><dt>Canal escolhido</dt><dd>{{ $canalRevisao === 'whatsapp' ? 'WhatsApp' : 'E-mail' }}</dd></div><div><dt>Destinatário conferido</dt><dd>{{ $destinoRevisao ?: 'Não informado' }}</dd></div></dl><p>Após a criação, você revisará a mensagem com a matrícula e o link individual para definir a senha.</p><p class="sc-note">{{ $canalRevisao === 'whatsapp' ? 'A conversa só será aberta depois de uma nova confirmação explícita na tela de entrega. O envio é manual.' : 'O e-mail segue pela fila após a confirmação da entrega. A chegada à caixa de entrada não é garantida.' }}</p></div></section>
            @endif
            <div class="sc-alert sc-alert-warning">{{ $novo ? 'Confirmar cria somente a conta de acesso. Não cria matrícula acadêmica ou cobrança.' : 'Alterações de contato podem invalidar credenciais e propostas de entrega anteriores. O novo e-mail precisa de confirmação do próprio aluno.' }}</div>
            <div class="sc-actions"><a class="sc-button" href="{{ $novo ? route('secretaria.acessos.create') : route('secretaria.acessos.edit', $aluno) }}" data-review-back>Voltar e editar</a><button type="submit" class="sc-button sc-primary">{{ $novo ? 'Confirmar cadastro e preparar entrega' : 'Confirmar alterações' }}</button></div>
        </div>
        <aside class="sc-aside"><section class="sc-side-card sc-side-gold">@include('secretaria.central.icone', ['nome' => 'escudo'])<h2>Última conferência</h2><p>Confira o nome, a identidade e os contatos pessoais do aluno. A confirmação revalida a proposta para proteger contra alterações simultâneas.</p><p>Se o atendimento tiver expirado, volte ao formulário e refaça a revisão.</p></section></aside>
    </form>
@endsection
