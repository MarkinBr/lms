@extends('secretaria.central.layout')
@section('title', 'Ficha da matrícula')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.matriculas.index') }}">Matrículas e turmas</a><span aria-hidden="true">/</span><span aria-current="page">Ficha da matrícula</span>
    <section class="sc-panel" aria-labelledby="mudar-situacao" style="margin-top:20px">
        <div class="sc-panel-heading">
            <h2 id="mudar-situacao">Alterar situação da matrícula</h2>
            <p>Trancar, reativar, concluir ou cancelar uma matrícula, preservando o histórico e registrando o motivo.</p>
        </div>
        <form method="post" class="sc-panel-body sc-ac-form" action="{{ route('secretaria.matriculas.situacao',$matricula) }}">
            @csrf
            <input type="hidden" name="situacao_anterior" value="{{ $matricula->status }}">
            <div><label for="situacao-nova">Nova situação acadêmica</label>
                <select name="situacao" id="situacao-nova" required>
                    <option value="">Selecione a nova situação</option>
                    @foreach(['ativa'=>'Ativa','trancada'=>'Trancada','concluida'=>'Concluída','cancelada'=>'Cancelada'] as $valor=>$rotulo)
                        <option value="{{ $valor }}" @selected(old('situacao') === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <div><label for="situacao-motivo">Justificativa</label><textarea id="situacao-motivo" name="motivo" rows="2" minlength="5" maxlength="500" required>{{ old('motivo') }}</textarea></div>
            <div class="sc-ac-warning">
                Esta operação não altera mensalidades automaticamente. A Secretaria precisa conferir as consequências acadêmicas e financeiras após registrar a mudança.
            </div>
            <label style="display:flex;gap:10px;align-items:flex-start;font-weight:500" for="ciente-financeiro">
                <input id="ciente-financeiro" type="checkbox" name="ciente_financeiro" value="1" style="width:auto;min-height:0;margin-top:3px" required>
                Estou ciente de que pode ser necessária revisão das cobranças e do histórico.
            </label>
            <div><label for="situacao-senha">Senha administrativa</label><input id="situacao-senha" type="password" name="senha_confirmacao" autocomplete="current-password" required></div>
            <button class="sc-button sc-primary" type="submit">Confirmar alteração de situação</button>
        </form>
    </section>
    @php
        $origens = collect();
        if ($matricula->turma) { $origens->push($matricula->turma); }
        foreach($matricula->turmasHistorico as $turmaVinculada) {
            if($turmaVinculada->pivot->status === \App\Models\MatriculaTurma::STATUS_ATIVO && !$origens->contains('id',$turmaVinculada->id)) {
                $origens->push($turmaVinculada);
            }
        }
    @endphp
    <section class="sc-panel" aria-labelledby="transferir-turma" style="margin-top:20px">
        <div class="sc-panel-heading"><h2 id="transferir-turma">Transferir entre turmas</h2>
            <p>Transferência apenas entre turmas da mesma disciplina e do mesmo curso, preservando o vínculo anterior no histórico.</p>
        </div>
        <div class="sc-panel-body">
            @if($origens->isNotEmpty() && $turmasDisponiveis->isNotEmpty())
                <form class="sc-ac-form" method="post" action="{{ route('secretaria.matriculas.transferir-turma',$matricula) }}">
                    @csrf
                    <div><label for="transferencia-origem">Turma de origem</label>
                        <select id="transferencia-origem" name="turma_origem_id" required>
                            <option value="">Selecione a turma atual</option>
                            @foreach($origens as $turmaAtual)
                                <option value="{{ $turmaAtual->id }}" @selected(old('turma_origem_id')==$turmaAtual->id)>{{ $turmaAtual->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label for="transferencia-destino">Turma de destino</label>
                        <select name="turma_destino_id" id="transferencia-destino" required>
                            <option value="">Selecione outra turma da mesma disciplina</option>
                            @foreach($turmasDisponiveis as $turmaDisponivel)
                                @if(!$origens->contains('id',$turmaDisponivel->id))
                                    <option value="{{ $turmaDisponivel->id }}" @selected(old('turma_destino_id')==$turmaDisponivel->id)>{{ $turmaDisponivel->nome }}</option>
                                @endif
                            @endforeach
                        </select><small>O sistema validará novamente disciplina, situação, curso e capacidade antes de confirmar.</small>
                    </div>
                    <div><label for="transferencia-motivo">Justificativa</label><textarea id="transferencia-motivo" name="motivo" minlength="5" maxlength="500" rows="2" required>{{ old('motivo') }}</textarea></div>
                    <div><label for="transferencia-senha">Senha administrativa</label><input id="transferencia-senha" name="senha_confirmacao" type="password" autocomplete="current-password" required></div>
                    <button type="submit" class="sc-button sc-primary">Confirmar transferência</button>
                </form>
            @else
                <p class="sc-ac-muted">Não há turmas de origem e destino disponíveis para transferência. Consulte as turmas antes de movimentar o aluno.</p>
            @endif
        </div>
    </section>
    @if($outrasMatriculas->isNotEmpty())
        <section class="sc-panel" style="margin-top:20px">
            <div class="sc-panel-heading"><h2>Outras matrículas deste aluno</h2><p>Cada curso possui matrícula acadêmica independente.</p></div>
            <div class="sc-panel-body sc-ac-button-row">
                @foreach($outrasMatriculas as $outra)
                    <a class="sc-button sc-button-small" href="{{ route('secretaria.matriculas.show',$outra) }}">{{ $outra->curso?->titulo ?? 'Curso' }} · {{ $outra->numero_matricula ?: 'Sem número' }}</a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
@section('content')
    <a href="{{ route('secretaria.matriculas.index') }}" class="sc-back">@include('secretaria.central.icone', ['nome' => 'voltar']) Voltar às matrículas</a>
    <div class="sc-intro">
        <div><p class="sc-eyebrow">Dados acadêmicos</p><h1>Ficha da matrícula</h1><p>{{ $matricula->user?->name ?? 'Aluno não vinculado' }} — {{ $matricula->curso?->titulo ?? 'Curso não informado' }}</p></div>
        <a class="sc-button" href="{{ route('filament.secretario.resources.matricula-secretarias.edit', $matricula) }}">Editar dados acadêmicos @include('secretaria.central.icone', ['nome' => 'seta'])</a>
    </div>
    @include('secretaria.central.matriculas.nav', ['secao' => 'matriculas'])
    <section class="sc-profile-hero" style="margin-bottom:22px">
        <span class="sc-avatar sc-avatar-large" aria-hidden="true">@include('secretaria.central.icone',['nome'=>'pessoas'])</span>
        <div class="sc-profile-person">
            <h2>{{ $matricula->user?->name ?? 'Aluno não vinculado' }}</h2>
            <p>{{ $matricula->curso?->titulo ?? 'Curso não informado' }} · Nº acadêmico {{ $matricula->numero_matricula ?: 'Não informado' }}</p>
            <div class="sc-badges"><span class="sc-ac-badge is-neutral">{{ ucfirst($matricula->status ?? 'Sem situação') }}</span>
                @if(!$matricula->turma && !$matricula->turmasHistorico->contains(fn($t) => $t->pivot->status === \App\Models\MatriculaTurma::STATUS_ATIVO))
                    <span class="sc-ac-badge is-amber">Sem turma ativa</span>
                @endif
            </div>
        </div>
        <div class="sc-profile-context">
            <dl><div><dt>Registro acadêmico</dt><dd class="sc-ac-mono">{{ $matricula->numero_matricula ?: 'Não informado' }}</dd></div>
                <div><dt>Data da matrícula</dt><dd>{{ $matricula->data_matricula?->format('d/m/Y') ?? 'Não informada' }}</dd></div></dl>
        </div>
    </section>
    <div class="sc-ac-warning" style="margin-bottom:20px">
        A ficha acadêmica preserva os identificadores de login e os dados cadastrais do aluno. Trancamentos, conclusão e transferências podem exigir revisão de mensalidades e frequência.
    </div>
    <section class="sc-panel">
        <div class="sc-panel-heading"><h2>Identificação e situação</h2><p>O número acadêmico é independente do login do aluno.</p></div>
        <dl class="sc-data-grid">
            <div><dt>Aluno</dt><dd>{{ $matricula->user?->name ?? 'Não vinculado' }}</dd></div>
            <div><dt>Número da matrícula acadêmica</dt><dd>{{ $matricula->numero_matricula ?: 'Não informado' }}</dd></div>
            <div><dt>Matrícula de acesso ao portal (não será alterada)</dt><dd>{{ $matricula->user?->matricula_login ?: 'Não informada' }}</dd></div>
            <div><dt>Curso</dt><dd>{{ $matricula->curso?->titulo ?? 'Não informado' }}</dd></div>
            <div><dt>Situação</dt><dd>{{ ucfirst($matricula->status ?? 'Não informada') }}</dd></div>
            <div><dt>Turma principal (legado)</dt><dd>{{ $matricula->turma?->nome ?? 'Não vinculada' }}</dd></div>
            <div><dt>Data da matrícula</dt><dd>{{ $matricula->data_matricula?->format('d/m/Y') ?? 'Não informada' }}</dd></div>
            <div><dt>Vínculos de turma registrados</dt><dd>{{ $matricula->turmasHistorico->count() }}</dd></div>
        </dl>
    </section>

    <div class="sc-layout">
        <div class="sc-stack">
            <section class="sc-panel" aria-labelledby="corrigir-numero">
                <div class="sc-panel-heading"><h2 id="corrigir-numero">Corrigir número de matrícula</h2><p>Corrige o identificador acadêmico. Não altera CPF, senha ou matrícula de login.</p></div>
                <form class="sc-panel-body sc-stack" method="post" action="{{ route('secretaria.matriculas.corrigir-numero', $matricula) }}">
                    @csrf
                    <input type="hidden" name="numero_atual" value="{{ $matricula->numero_matricula ?? '' }}">
                    <div class="sc-field"><label for="novo-numero">Novo número da matrícula acadêmica</label>
                        <input id="novo-numero" name="numero_matricula" type="text" maxlength="20" pattern="[A-Za-z0-9._/-]+" value="{{ old('numero_matricula', $matricula->numero_matricula) }}" required></div>
                    <div class="sc-field"><label for="numero-motivo">Justificativa da correção</label><textarea id="numero-motivo" name="motivo" rows="2" minlength="5" maxlength="500" required>{{ old('motivo') }}</textarea></div>
                    <div class="sc-field"><label for="numero-senha">Senha administrativa</label><input id="numero-senha" name="senha_confirmacao" type="password" autocomplete="current-password" required></div>
                    <button class="sc-button sc-primary" type="submit">Confirmar correção do número</button>
                </form>
            </section>
            <section class="sc-panel" aria-labelledby="vinculos-turmas">
                <div class="sc-panel-heading"><h2 id="vinculos-turmas">Turmas vinculadas</h2><p>O vínculo legado e os vínculos acadêmicos complementares são preservados.</p></div>
                <div class="sc-panel-body sc-stack">
                    @if($matricula->turma)<p><strong>{{ $matricula->turma->nome }}</strong> · vínculo principal legado</p>@endif
                    @forelse ($matricula->turmasHistorico as $turma)
                        <p><strong><a class="sc-text-link" href="{{ route('secretaria.matriculas.turma', $turma) }}">{{ $turma->nome }}</a></strong> · {{ \App\Models\MatriculaTurma::STATUS_LABELS[$turma->pivot->status] ?? $turma->pivot->status }}</p>
                    @empty
                        @if(!$matricula->turma)<p>Nenhuma turma vinculada.</p>@endif
                    @endforelse
                </div>
            </section>
            <section class="sc-panel" aria-labelledby="historico-matricula">
                <div class="sc-panel-heading"><h2 id="historico-matricula">Histórico de correções e vínculos</h2><p>Operações registradas pela nova Central.</p></div>
                <div class="sc-panel-body sc-stack">
                    @forelse($historico as $evento)
                        <div class="sc-panel-note"><p><strong>{{ $evento->tipo === 'correcao_numero' ? 'Correção de número' : 'Vinculação à turma' }}</strong>
                            · {{ \Illuminate\Support\Carbon::parse($evento->created_at)->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</p>
                            <p>{{ $evento->valor_anterior ?? 'Não informado' }} → {{ $evento->valor_novo ?? 'Não informado' }}</p>
                            <p>Responsável: {{ $evento->autor ?? 'Usuário indisponível' }} · Motivo: {{ $evento->motivo }}</p>
                        </div>
                    @empty
                        <p>Não há movimentações registradas nesta etapa da Central.</p>
                    @endforelse
                </div>
            </section>
        </div>
        <aside class="sc-aside">
            <section class="sc-side-card sc-side-gold"><h2>Adicionar vínculo com turma</h2><p>Somente matrícula ativa e turma aberta do mesmo curso. Esta ação não transfere nem encerra outro vínculo.</p>
                @if ($turmasDisponiveis->isNotEmpty())
                    <form class="sc-stack" method="post" action="{{ route('secretaria.matriculas.vincular-turma', $matricula) }}">
                        @csrf
                        <div class="sc-field"><label for="vinculo-turma">Turma</label>
                            <select id="vinculo-turma" name="turma_id" required>
                                <option value="">Selecione a turma</option>
                                @foreach($turmasDisponiveis as $turma)
                                    <option value="{{ $turma->id }}" @selected(old('turma_id') == $turma->id)>{{ $turma->nome }}{{ $turma->capacidade ? ' · até '.$turma->capacidade.' vagas' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sc-field"><label for="vinculo-motivo">Justificativa do vínculo</label><textarea id="vinculo-motivo" name="motivo" rows="2" minlength="5" maxlength="500" required>{{ old('motivo') }}</textarea></div>
                        <div class="sc-field"><label for="vinculo-senha">Senha administrativa</label><input type="password" name="senha_confirmacao" id="vinculo-senha" autocomplete="current-password" required></div>
                        <button type="submit" class="sc-button sc-primary">Vincular à turma</button>
                    </form>
                @else
                    <p>Não há turmas abertas disponíveis para este curso.</p>
                @endif
            </section>
            <section class="sc-side-card"><h2>Cadastro e acesso</h2>
                @if($matricula->user?->nivel === 'aluno')<a class="sc-text-link" href="{{ route('secretaria.acessos.show', $matricula->user) }}">Abrir cadastro do aluno na Gestão de Acessos</a>@endif
                <p>Alterações acadêmicas não modificam a autenticação do Portal do Aluno.</p>
            </section>
        </aside>
    </div>
@endsection
