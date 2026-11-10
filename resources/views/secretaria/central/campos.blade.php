@php
    $ufs = array_combine($listaUfs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'], $listaUfs);
    $grupos = [
        'dados-pessoais' => ['Dados pessoais', 'Informações para identificar o aluno com segurança.', [
            'name' => ['Nome completo', 'text', 'name'], 'cpf' => ['CPF', 'text', 'off'], 'rg' => ['RG', 'text', 'off'],
            'data_nasc' => ['Data de nascimento', 'date', 'bday'],
            'genero' => ['Sexo / gênero', ['M' => 'Masculino', 'F' => 'Feminino', 'Outro' => 'Outro']],
            'estado_civil' => ['Estado civil', ['Solteiro(a)' => 'Solteiro(a)', 'Casado(a)' => 'Casado(a)', 'Viúvo(a)' => 'Viúvo(a)', 'Divorciado(a)' => 'Divorciado(a)', 'Separado(a)' => 'Separado(a)', 'União estável' => 'União estável']],
            'nacionalidade' => ['Nacionalidade', 'text', 'off'], 'naturalidade_cidade' => ['Naturalidade — cidade', 'text', 'off'], 'naturalidade_estado' => ['Naturalidade — UF', $ufs],
        ]],
        'contatos' => ['Contatos', 'Use os contatos pessoais do aluno. Telefone e WhatsApp são campos independentes.', [
            'email' => ['E-mail pessoal', 'email', 'email'], 'telefone' => ['Telefone', 'tel', 'tel'], 'whatsapp' => ['WhatsApp do aluno', 'tel', 'off'],
        ]],
        'endereco' => ['Endereço', 'Endereço completo e atualizado para o atendimento.', [
            'cep' => ['CEP', 'text', 'postal-code'], 'endereco' => ['Logradouro', 'text', 'address-line1'], 'numero' => ['Número', 'text', 'off'], 'complemento' => ['Complemento', 'text', 'address-line2'], 'bairro' => ['Bairro', 'text', 'address-level3'], 'cidade' => ['Cidade', 'text', 'address-level2'], 'estado' => ['Estado — UF', $ufs],
        ]],
        'escolaridade' => ['Escolaridade e formação', 'Dados cadastrais; a inscrição original permanece preservada.', [
            'escolaridade' => ['Escolaridade', ['fundamental_incompleto' => 'Fundamental incompleto', 'fundamental_completo' => 'Fundamental completo', 'medio_incompleto' => 'Médio incompleto', 'medio_completo' => 'Médio completo', 'superior_incompleto' => 'Superior incompleto', 'superior_completo' => 'Superior completo', 'pos_graduacao' => 'Pós-graduação', 'mestrado' => 'Mestrado', 'doutorado' => 'Doutorado']],
            'ano_conclusao' => ['Ano de conclusão', 'number', 'off'], 'formacao_teologica' => ['Já possui formação teológica?', ['1' => 'Sim', '0' => 'Não']], 'instituicao_anterior' => ['Instituição anterior', 'text', 'off'], 'como_conheceu' => ['Como conheceu o CETSADP?', ['indicacao' => 'Indicação', 'redes_sociais' => 'Redes sociais', 'igreja' => 'Igreja', 'site' => 'Site']], 'indicacao_nome' => ['Nome de quem indicou', 'text', 'off'],
        ]],
    ];
    $leitura = ($modo ?? 'edicao') === 'leitura';
@endphp
@foreach ($grupos as $grupoId => [$tituloGrupo, $descricaoGrupo, $campos])
    <section class="sc-panel sc-data-section" id="{{ $grupoId }}" aria-labelledby="{{ $grupoId }}-titulo">
        <div class="sc-panel-heading sc-heading-actions"><div><h2 id="{{ $grupoId }}-titulo">{{ $tituloGrupo }}</h2><p>{{ $descricaoGrupo }}</p></div>
            @if ($leitura)<a class="sc-button sc-button-small" href="{{ route('secretaria.acessos.edit', $aluno) }}#{{ $grupoId }}">@include('secretaria.central.icone', ['nome' => 'editar']) Editar<span class="sc-sr-only"> {{ mb_strtolower($tituloGrupo) }}</span></a>@endif
        </div>
        <div class="sc-data-grid">
            @foreach ($campos as $campo => $config)
                @php
                    [$rotulo, $tipo] = $config;
                    $valorCadastro = $aluno?->getAttribute($campo);
                    if ($campo === 'data_nasc' && $valorCadastro) { $valorCadastro = \Illuminate\Support\Carbon::parse($valorCadastro)->format('Y-m-d'); }
                    if ($campo === 'formacao_teologica' && $valorCadastro !== null) { $valorCadastro = $valorCadastro ? '1' : '0'; }
                    $valor = $leitura ? $valorCadastro : old($campo, data_get($dados ?? [], $campo, $valorCadastro));
                    if ($campo === 'genero') { $valor = ['masculino' => 'M', 'feminino' => 'F', 'outro' => 'Outro'][(string) $valor] ?? $valor; }
                    if ($campo === 'como_conheceu') { $valor = ['Indicação' => 'indicacao', 'Redes sociais' => 'redes_sociais', 'Igreja' => 'igreja', 'Site' => 'site'][(string) $valor] ?? $valor; }
                    $rotuloValor = is_array($tipo) ? ($tipo[(string) $valor] ?? $valor) : $valor;
                    if ($leitura && $campo === 'data_nasc' && $valor) { $rotuloValor = \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y'); }
                @endphp
                @if ($leitura)
                    <dl><dt>{{ $rotulo }}</dt><dd class="{{ $valor === null || $valor === '' ? 'sc-missing' : '' }}">{{ $valor !== null && $valor !== '' ? $rotuloValor : 'Não informado' }}</dd></dl>
                @else
                    <div class="sc-field">
                        <label for="campo-{{ $campo }}">{{ $rotulo }} @if ($campo === 'name')<span class="sc-required" aria-hidden="true">*</span><span class="sc-sr-only">(obrigatório)</span>@endif</label>
                        @if (is_array($tipo))
                            <select id="campo-{{ $campo }}" name="{{ $campo }}" @error($campo) aria-invalid="true" aria-describedby="erro-{{ $campo }}" @enderror>
                                <option value="">Não informado</option>
                                @if ($valor !== null && $valor !== '' && !array_key_exists((string) $valor, $tipo))<option value="{{ $valor }}" selected>{{ $valor }} (valor cadastrado)</option>@endif
                                @foreach ($tipo as $opcao => $texto)<option value="{{ $opcao }}" @selected((string) $valor === (string) $opcao && $valor !== null && $valor !== '')>{{ $texto }}</option>@endforeach
                            </select>
                        @else
                            <input id="campo-{{ $campo }}" name="{{ $campo }}" type="{{ $tipo }}" value="{{ $valor }}" autocomplete="{{ $config[2] ?? 'off' }}" @required($campo === 'name') @if($tipo === 'text' || $tipo === 'tel') maxlength="{{ $campo === 'endereco' ? 255 : ($campo === 'cpf' ? 14 : 180) }}" @endif @if($tipo === 'email') maxlength="254" @endif @if($campo === 'data_nasc') max="{{ now()->format('Y-m-d') }}" @endif @if($campo === 'ano_conclusao') min="1900" max="{{ now()->year }}" step="1" @endif @if(in_array($campo, ['cpf', 'cep'])) inputmode="numeric" @endif @error($campo) aria-invalid="true" aria-describedby="erro-{{ $campo }}" @enderror>
                        @endif
                        @if ($campo === 'whatsapp')<small>Informe com DDI e DDD. Não usaremos o telefone automaticamente como WhatsApp.</small>@endif
                        @if ($campo === 'email' && $aluno)<small>Alterar o e-mail inicia uma nova confirmação; a Secretaria não confirma a caixa postal pelo aluno.</small>@endif
                        @error($campo)<small class="sc-field-error" id="erro-{{ $campo }}">{{ $message }}</small>@enderror
                    </div>
                @endif
            @endforeach
        </div>
        @if ($leitura && $grupoId === 'contatos')
            <div class="sc-panel-note">
                @include('secretaria.central.status')
                @if ($aluno->email_confirmacao_pendente)<p>Confirmação pendente para: <strong>{{ $aluno->email_confirmacao_pendente }}</strong></p>@endif
            </div>
        @endif
    </section>
@endforeach
