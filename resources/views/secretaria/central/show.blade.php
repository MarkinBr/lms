@extends('secretaria.central.layout')
@section('title', 'Ficha do aluno')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.acessos.index') }}">Acessos dos alunos</a><span aria-hidden="true">/</span><span aria-current="page">Ficha do aluno</span>@endsection
@section('content')
    @php
        $dataHora = static fn ($data) => $data ? \Illuminate\Support\Carbon::parse($data)->timezone('America/Sao_Paulo')->format('d/m/Y H:i') : 'Não informada';
        $dataSimples = static fn ($data) => $data ? \Illuminate\Support\Carbon::parse($data)->format('d/m/Y') : 'Não informada';
        $matriculas = $aluno->matriculas;
        $primeiraInscricaoConhecida = $inscricoes->whereNotNull('created_at')->sortBy('created_at')->first();
        $rotulosAcoes = ['cadastro' => 'Cadastro de acesso', 'criar' => 'Cadastro de acesso', 'criacao' => 'Cadastro de acesso', 'atualizacao' => 'Atualização cadastral', 'atualizar' => 'Atualização cadastral', 'foto' => 'Atualização de foto', 'ativacao' => 'Ativação de acesso', 'recuperacao' => 'Recuperação de acesso', 'temporaria' => 'Senha temporária', 'bloquear' => 'Bloqueio de acesso', 'desbloquear' => 'Desbloqueio de acesso', 'sessoes' => 'Encerramento de sessões', 'mensagem' => 'Preparação de mensagem', 'email' => 'Confirmação de e-mail'];
    @endphp
    <a class="sc-back" href="{{ route('secretaria.acessos.index') }}">@include('secretaria.central.icone', ['nome' => 'voltar']) Voltar à lista de alunos</a>
    <div class="sc-intro"><div><p class="sc-eyebrow">Conhecer para atender melhor</p><h1>Ficha do aluno</h1><p>Cadastro e acesso, juntos no mesmo atendimento.</p></div><a class="sc-button" href="{{ route('secretaria.acessos.edit', $aluno) }}">@include('secretaria.central.icone', ['nome' => 'editar']) Editar cadastro completo</a></div>
    <section class="sc-profile-hero" aria-label="Resumo do aluno">
        @include('secretaria.central.avatar', ['grande' => true])
        <div class="sc-profile-person"><h2>{{ $aluno->name }}</h2><p>Matrícula de acesso · <strong>{{ $aluno->matricula_login ?: 'Não informada' }}</strong></p>@include('secretaria.central.status')<a class="sc-text-link" href="#foto-aluno">{{ $aluno->foto ? 'Atualizar foto do aluno' : 'Adicionar foto do aluno' }}</a></div>
        <dl class="sc-profile-context"><dt>Inscrição vinculada mais antiga conhecida</dt><dd>{{ $dataHora($primeiraInscricaoConhecida?->created_at) }}</dd><dt>Criação da conta</dt><dd>{{ $dataHora($aluno->created_at) }}</dd><dt>Vínculos acadêmicos</dt><dd>{{ $matriculas->count() }} {{ $matriculas->count() === 1 ? 'matrícula registrada' : 'matrículas registradas' }}</dd></dl>
    </section>
    <div data-tabs data-initial-tab="{{ old('acao') || old('mensagem') ? 'acesso' : 'dados' }}">
        <nav class="sc-tabs" aria-label="Seções da ficha" data-tab-list>
            <a href="#dados" id="aba-dados" data-tab-link>@include('secretaria.central.icone', ['nome' => 'pessoas']) Dados cadastrais</a>
            <a href="#acesso" id="aba-acesso" data-tab-link>@include('secretaria.central.icone', ['nome' => 'chave']) Acesso e segurança</a>
            <a href="#inscricoes" id="aba-inscricoes" data-tab-link>@include('secretaria.central.icone', ['nome' => 'livro']) Inscrições e cursos</a>
            <a href="#historico" id="aba-historico" data-tab-link>@include('secretaria.central.icone', ['nome' => 'relogio']) Histórico</a>
        </nav>
        <section id="dados" data-tab-panel aria-labelledby="aba-dados" class="sc-tab-panel">
            <h2 class="sc-section-title">Dados cadastrais</h2>
            <div class="sc-layout">
                <div class="sc-stack">@include('secretaria.central.campos', ['modo' => 'leitura'])</div>
                <aside class="sc-aside">
                    <section class="sc-side-card sc-side-gold"><h2>Uma ficha, um cadastro.</h2><p>Dados atualizados do aluno. As inscrições de origem continuam preservadas na aba Inscrições e cursos.</p></section>
                    <section class="sc-side-card" id="foto-aluno"><h2>Foto do aluno</h2><p>Envie uma foto de perfil, sem documentos pessoais. A imagem é acessível somente pela rota protegida.</p>
                        <form action="{{ route('secretaria.acessos.foto', $aluno) }}" method="post" enctype="multipart/form-data" class="sc-stack sc-photo-form">
                            @csrf
                            <input type="hidden" name="estado" value="{{ $estado }}">
                            <div class="sc-field"><label for="foto">Selecionar foto</label><input id="foto" type="file" name="foto" accept="image/jpeg,image/png,image/webp" required aria-describedby="foto-ajuda"><small id="foto-ajuda">JPG, PNG ou WebP. A imagem será validada antes de salvar.</small></div>
                            <div class="sc-field"><label for="foto-motivo">Motivo da atualização</label><textarea id="foto-motivo" name="motivo" rows="2" maxlength="500" required></textarea></div>
                            <div class="sc-field"><label for="foto-senha">Sua senha de Secretaria</label><input id="foto-senha" name="senha_confirmacao" type="password" autocomplete="current-password" required></div>
                            <button type="submit" class="sc-button">@include('secretaria.central.icone', ['nome' => 'foto']) Salvar foto</button>
                        </form>
                    </section>
                    <section class="sc-side-card"><h2>Conferências do atendimento</h2><ul>
                        @if (!$aluno->foto)<li>Foto não cadastrada.</li>@endif
                        @if ($aluno->primeiro_acesso_pendente)<li>Primeiro acesso pendente.</li>@endif
                        @if (!$aluno->email_verified_at)<li>E-mail ainda não confirmado.</li>@endif
                        @if (!$aluno->whatsapp)<li>WhatsApp não cadastrado.</li>@endif
                        <li>Confira a identidade antes de alterar contatos ou emitir credenciais.</li>
                    </ul></section>
                </aside>
            </div>
        </section>
        <section id="acesso" data-tab-panel aria-labelledby="aba-acesso" class="sc-tab-panel">
            <h2 class="sc-section-title">Acesso e segurança</h2>
            <div class="sc-layout">
                <div class="sc-stack">
                    <section class="sc-panel"><div class="sc-panel-heading"><h2>Atendimento do acesso</h2><p>Confira o aluno, escolha a operação e confirme com a sua senha de Secretaria.</p></div><div class="sc-panel-body sc-stack">
                        @include('secretaria.central.status')
                        @include('secretaria.central.acao', ['acao' => 'ativacao', 'titulo' => 'Preparar ativação', 'descricao' => 'Instruções individuais para o primeiro acesso.'])
                        @include('secretaria.central.acao', ['acao' => 'recuperacao', 'titulo' => 'Recuperar acesso', 'descricao' => 'Orientar o aluno a definir uma nova senha.'])
                        @include('secretaria.central.acao', ['acao' => 'temporaria', 'titulo' => 'Gerar senha temporária', 'descricao' => 'Código de uso único para atendimento assistido.'])
                        @if ($aluno->ativo)
                            @include('secretaria.central.acao', ['acao' => 'bloquear', 'titulo' => 'Bloquear acesso', 'descricao' => 'Impede o acesso e revoga sessões e credenciais.'])
                        @else
                            @include('secretaria.central.acao', ['acao' => 'desbloquear', 'titulo' => 'Desbloquear acesso', 'descricao' => 'Libera a conta sem confirmar e-mail ou primeiro acesso.'])
                        @endif
                        @include('secretaria.central.acao', ['acao' => 'sessoes', 'titulo' => 'Encerrar sessões', 'descricao' => 'Solicitar uma nova autenticação nos dispositivos do aluno.'])
                    </div></section>
                    <section class="sc-panel" aria-labelledby="mensagem-titulo"><div class="sc-panel-heading"><h2 id="mensagem-titulo">Enviar mensagem ao aluno</h2><p>Prepare uma orientação e revise conteúdo e destino antes de continuar.</p></div>
                        <form action="{{ route('secretaria.acessos.mensagem', $aluno) }}" method="post" class="sc-panel-body sc-stack" data-message-form>
                            @csrf
                            <input type="hidden" name="estado" value="{{ $estado }}">
                            <div class="sc-contact-review"><p><strong>WhatsApp:</strong> {{ $aluno->whatsapp ?: 'Não cadastrado' }}</p><p><strong>E-mail:</strong> {{ $aluno->email ?: 'Não cadastrado' }}</p></div>
                            <div class="sc-field"><label for="mensagem-canal">Canal de envio</label><select id="mensagem-canal" name="canal" required><option value="">Selecione o canal</option><option value="whatsapp" @disabled(!$aluno->whatsapp)>WhatsApp{{ !$aluno->whatsapp ? ' — não cadastrado' : '' }}</option><option value="email" @disabled(!$aluno->email)>E-mail{{ !$aluno->email ? ' — não cadastrado' : '' }}</option></select></div>
                            <div class="sc-field sc-template-field" hidden data-template-field><label for="mensagem-modelo">Usar um modelo de orientação</label><select id="mensagem-modelo" data-message-template><option value="">Texto personalizado</option><option value="acesso">Orientação de acesso</option><option value="assistido">Atendimento assistido</option><option value="documentacao">Documentação</option></select><small>Revise e personalize o texto para este atendimento.</small></div>
                            <div class="sc-field"><label for="mensagem-texto">Mensagem</label><textarea id="mensagem-texto" name="mensagem" rows="6" maxlength="2000" required data-message-text aria-describedby="mensagem-ajuda">{{ old('mensagem') }}</textarea><small id="mensagem-ajuda">Não inclua senha permanente, CPF ou documentos. Use as operações de acesso para emitir códigos e links individuais.</small></div>
                            <div class="sc-field"><label for="mensagem-motivo">Motivo do atendimento</label><textarea id="mensagem-motivo" name="motivo" rows="2" maxlength="500" required></textarea></div>
                            <div class="sc-field"><label for="mensagem-senha">Sua senha de Secretaria</label><input id="mensagem-senha" name="senha_confirmacao" type="password" autocomplete="current-password" required></div>
                            <label class="sc-check"><input type="checkbox" name="destino_conferido" value="1" required><span>Conferi o contato do próprio aluno e revisei o conteúdo da mensagem.</span></label>
                            <button class="sc-button sc-primary" type="submit">Revisar mensagem e destino @include('secretaria.central.icone', ['nome' => 'seta'])</button>
                        </form>
                    </section>
                </div>
                <aside class="sc-aside">
                    <section class="sc-side-card sc-side-gold"><h2>Envio com conferência.</h2><p>WhatsApp abre o rascunho no dispositivo da equipe para envio manual. Abrir a conversa não comprova envio, entrega ou leitura.</p><p>E-mail segue por fila. O recebimento depende do transporte e da caixa postal do aluno.</p></section>
                    <section class="sc-side-card"><h2>Contatos do cadastro</h2><dl class="sc-summary"><div><dt>E-mail atual</dt><dd>{{ $aluno->email ?: 'Não cadastrado' }}</dd></div><div><dt>WhatsApp pessoal</dt><dd>{{ $aluno->whatsapp ?: 'Não cadastrado' }}</dd></div>@if ($aluno->email_confirmacao_pendente)<div><dt>Confirmação pendente</dt><dd>{{ $aluno->email_confirmacao_pendente }}</dd></div>@endif</dl><a class="sc-text-link" href="{{ route('secretaria.acessos.edit', $aluno) }}#contatos">Corrigir contatos com revisão</a></section>
                    @if ($aluno->email_confirmacao_pendente)
                        <section class="sc-side-card"><h2>Confirmar novo e-mail</h2><p>O endereço pendente precisa ser confirmado pelo próprio aluno. A confirmação é enviada somente para essa caixa postal.</p><form action="{{ route('secretaria.acessos.email', $aluno) }}" method="post" class="sc-stack">
                            @csrf
                            <input type="hidden" name="estado" value="{{ $estado }}"><input type="hidden" name="canal" value="email">
                            <div class="sc-field"><label for="email-motivo">Motivo do reenvio</label><textarea id="email-motivo" name="motivo" rows="2" maxlength="500" required></textarea></div>
                            <label class="sc-check"><input type="checkbox" name="destino_conferido" value="1" required><span>Conferi o novo e-mail com o aluno.</span></label>
                            <div class="sc-field"><label for="email-senha">Sua senha de Secretaria</label><input type="password" id="email-senha" name="senha_confirmacao" autocomplete="current-password" required></div>
                            <button type="submit" class="sc-button">Preparar confirmação de e-mail</button>
                        </form></section>
                    @endif
                </aside>
            </div>
        </section>
        <section id="inscricoes" data-tab-panel aria-labelledby="aba-inscricoes" class="sc-tab-panel">
            <h2 class="sc-section-title">Inscrições e cursos</h2>
            <p class="sc-intro-note">Consulta dos vínculos do aluno. Inscrição, matrícula acadêmica e conta de acesso têm datas e situações próprias. Horários em Brasília (America/Sao_Paulo).</p>
            <div class="sc-stack">
                <section class="sc-panel"><div class="sc-panel-heading"><h2>Matrículas acadêmicas</h2><p>Todos os vínculos autorizados, inclusive históricos. Esta ficha não altera cursos ou turmas.</p></div><div class="sc-panel-body sc-stack">
                    @forelse ($matriculas as $matricula)
                        @php
                            $inscricaoVinculada = $inscricoes->firstWhere('id', $matricula->application_id);
                        @endphp
                        <article class="sc-academic-card"><div class="sc-section-heading"><h3>{{ $matricula->curso?->nome ?? 'Curso não informado' }}</h3><span class="sc-badge sc-badge-neutral">Matrícula: {{ $matricula->status ?: 'Não informado' }}</span></div>
                            <dl class="sc-data-grid"><div><dt>Número da matrícula acadêmica</dt><dd>{{ $matricula->numero_matricula ?: 'Não informado' }}</dd></div><div><dt>Turma</dt><dd>{{ $matricula->turma?->nome ?? 'Não informada' }}</dd></div><div><dt>Data da matrícula</dt><dd>{{ $dataSimples($matricula->data_matricula) }}</dd></div><div><dt>Data da inscrição vinculada</dt><dd>{{ $dataHora($inscricaoVinculada?->created_at) }}</dd></div><div><dt>Protocolo de origem</dt><dd>{{ $inscricaoVinculada?->protocol ?: 'Não informado' }}</dd></div><div><dt>Conclusão</dt><dd>{{ $dataSimples($matricula->data_conclusao) }}</dd></div></dl>
                            @unless ($inscricaoVinculada)<p class="sc-panel-note">Origem da inscrição não informada. Não foi inferido vínculo por CPF ou e-mail.</p>@endunless
                        </article>
                    @empty
                        <div class="sc-empty"><h3>Nenhuma matrícula acadêmica vinculada</h3><p>A conta de acesso não cria matrícula, curso ou cobrança.</p></div>
                    @endforelse
                </div></section>
                <section class="sc-panel"><div class="sc-panel-heading"><h2>Inscrições de origem</h2><p>Dados originais, vinculados explicitamente pelas matrículas. Não substituem o cadastro atualizado.</p></div><div class="sc-panel-body sc-stack">
                    @forelse ($inscricoes as $inscricao)
                        <article class="sc-academic-card"><div class="sc-section-heading"><h3>{{ $inscricao->curso?->nome ?? 'Curso não informado' }}</h3><span class="sc-badge sc-badge-neutral">Inscrição: {{ str_replace('_', ' ', $inscricao->status ?: 'Não informado') }}</span></div>
                            <dl class="sc-data-grid"><div><dt>Protocolo</dt><dd>{{ $inscricao->protocol ?: 'Não informado' }}</dd></div><div><dt>Data da inscrição</dt><dd>{{ $dataHora($inscricao->created_at) }}</dd></div><div><dt>Modalidade</dt><dd>{{ $inscricao->courseModality?->nome ?? $inscricao->courseModality?->name ?? 'Não informada' }}</dd></div><div><dt>Matrículas vinculadas</dt><dd>{{ $matriculas->where('application_id', $inscricao->id)->count() }}</dd></div></dl>
                            <details class="sc-source"><summary>Conferir dados originais desta inscrição</summary><dl class="sc-data-grid">
                                @foreach (['nome_completo' => ['Nome', 'name'], 'cpf' => ['CPF', 'cpf'], 'rg' => ['RG', 'rg'], 'data_nascimento' => ['Nascimento', 'data_nasc'], 'sexo' => ['Sexo', 'genero'], 'estado_civil' => ['Estado civil', 'estado_civil'], 'nacionalidade' => ['Nacionalidade', 'nacionalidade'], 'naturalidade_cidade' => ['Naturalidade — cidade', 'naturalidade_cidade'], 'naturalidade_estado' => ['Naturalidade — UF', 'naturalidade_estado'], 'email' => ['E-mail', 'email'], 'telefone' => ['Telefone', 'telefone'], 'whatsapp' => ['WhatsApp', 'whatsapp'], 'cep' => ['CEP', 'cep'], 'logradouro' => ['Logradouro', 'endereco'], 'numero' => ['Número', 'numero'], 'complemento' => ['Complemento', 'complemento'], 'bairro' => ['Bairro', 'bairro'], 'cidade' => ['Cidade', 'cidade'], 'estado' => ['UF', 'estado'], 'escolaridade' => ['Escolaridade', 'escolaridade'], 'ano_conclusao' => ['Ano de conclusão', 'ano_conclusao'], 'formacao_teologica' => ['Formação teológica anterior', 'formacao_teologica'], 'instituicao_anterior' => ['Instituição anterior', 'instituicao_anterior'], 'como_conheceu' => ['Como conheceu', 'como_conheceu'], 'indicacao_nome' => ['Nome de quem indicou', 'indicacao_nome']] as $campoOrigem => [$rotuloOrigem, $campoAtual])
                                    @php
                                        $valorOrigem = $inscricao->getAttribute($campoOrigem);
                                        $valorAtual = $aluno->getAttribute($campoAtual);
                                        if ($campoOrigem === 'data_nascimento') { $valorOrigem = $valorOrigem ? $dataSimples($valorOrigem) : null; $valorAtual = $valorAtual ? $dataSimples($valorAtual) : null; }
                                        if ($campoOrigem === 'formacao_teologica') { $valorOrigem = $valorOrigem === null ? null : ($valorOrigem ? 'Sim' : 'Não'); $valorAtual = $valorAtual === null ? null : ($valorAtual ? 'Sim' : 'Não'); }
                                    @endphp
                                    <div><dt>{{ $rotuloOrigem }}</dt><dd>{{ $valorOrigem !== null && $valorOrigem !== '' ? $valorOrigem : 'Não informado na origem' }}</dd>@if ((string) $valorOrigem !== (string) $valorAtual)<small class="sc-difference">Cadastro atual: {{ $valorAtual !== null && $valorAtual !== '' ? $valorAtual : 'Não informado' }}</small>@endif</div>
                                @endforeach
                            </dl><p class="sc-panel-note">Dados ausentes não foram presumidos. As diferenças são apresentadas para conferência, sem alterar a inscrição original.</p></details>
                        </article>
                    @empty
                        <div class="sc-empty"><h3>Nenhuma inscrição de origem conhecida</h3><p>A data de criação da conta não será usada como data de inscrição.</p></div>
                    @endforelse
                </div></section>
            </div>
        </section>
        <section id="historico" data-tab-panel aria-labelledby="aba-historico" class="sc-tab-panel">
            <h2 class="sc-section-title">Histórico de atendimento</h2>
            @if ($entregas->isNotEmpty())
                <section class="sc-panel"><div class="sc-panel-heading"><h2>Acompanhamento das entregas</h2><p>Estados de processamento; não comprovam leitura pelo aluno.</p></div><div class="sc-panel-body"><ul class="sc-timeline">
                @php
                    $estadosEntrega = ['preparado' => 'Aguardando conferência da equipe', 'enfileirado' => 'Aguardando processamento', 'enviado' => 'Aceito pelo transporte de e-mail', 'abertura_solicitada' => 'Entrega manual confirmada pela equipe', 'cancelado' => 'Cancelado após revalidação', 'expirado' => 'Prazo de entrega encerrado', 'falhou' => 'Falha no envio'];
                @endphp
                @foreach ($entregas as $item)
                    <li><h3>{{ $estadosEntrega[$item->estado] ?? 'Processamento registrado' }}</h3><p>{{ $item->canal === 'whatsapp' && $item->estado === 'abertura_solicitada' ? 'WhatsApp: abertura solicitada; envio manual não comprovado' : ucfirst($item->canal) }} · {{ \Illuminate\Support\Carbon::parse($item->updated_at)->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</p></li>
                @endforeach
                </ul></div></section>
            @endif
            <section class="sc-panel"><div class="sc-panel-heading"><h2>Cadastro e acesso auditados</h2><p>Autor, data, motivo e resultado do atendimento. Horários de Brasília. Senhas, códigos e links não são reexibidos.</p></div>
                <ol class="sc-history">
                    @forelse ($historico as $registro)
                        @php
                            $acaoRegistro = data_get($registro, 'acao', 'Atendimento');
                            $detalhesRegistro = data_get($registro, 'detalhes', []);
                            if (is_string($detalhesRegistro)) { $detalhesRegistro = json_decode($detalhesRegistro, true) ?: []; }
                        @endphp
                        <li><span class="sc-history-marker">@include('secretaria.central.icone', ['nome' => 'relogio'])</span><div><h3>{{ $rotulosAcoes[$acaoRegistro] ?? str_replace('_', ' ', $acaoRegistro) }}</h3><p class="sc-history-meta">{{ $dataHora(data_get($registro, 'created_at')) }} · {{ data_get($registro, 'autor_nome') ?: 'Autor não informado' }}</p><p>{{ data_get($registro, 'motivo') ?: 'Motivo não informado' }}</p>
                            @foreach (['canal' => 'Canal', 'status' => 'Situação', 'resultado' => 'Resultado', 'destino_mascarado' => 'Destino'] as $chaveDetalhe => $rotuloDetalhe)
                                @if (is_scalar(data_get($detalhesRegistro, $chaveDetalhe)))<span class="sc-badge sc-badge-neutral">{{ $rotuloDetalhe }}: {{ data_get($detalhesRegistro, $chaveDetalhe) }}</span>@endif
                            @endforeach
                        </div></li>
                    @empty
                        <li class="sc-empty"><h3>Nenhum atendimento registrado</h3><p>As próximas operações de cadastro e acesso aparecerão aqui.</p></li>
                    @endforelse
                </ol>
            </section>
        </section>
    </div>
@endsection
