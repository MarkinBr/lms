@extends('layouts.aluno')

@section('title', 'Documentos da inscrição')
@section('header_title', 'Documentos da inscrição')
@section('header_subtitle', 'Envie os documentos solicitados pela Secretaria')

@section('content')
    @php
        /** @var array $resumo */
        $percentual = $resumo['total'] > 0
            ? (int) round(($resumo['enviados'] / $resumo['total']) * 100)
            : 0;
    @endphp

    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8" aria-labelledby="titulo-central">
        <header class="mb-8">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-[#003366]">Central acadêmica</p>
            <h1 id="titulo-central" class="mt-1 text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                Documentos da inscrição
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-gray-300">
                Envie e acompanhe os documentos exigidos pela secretaria para concluir sua inscrição.
                Todos os arquivos ficam armazenados em ambiente privado e só podem ser vistos por você
                e pela equipe autorizada.
            </p>
        </header>

        @if (session('sucesso_documento'))
            <div role="status" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('sucesso_documento') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">Não foi possível enviar o documento:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($errors->all() as $mensagem)
                        <li>{{ $mensagem }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! $resumo['tem_inscricao'])
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Nenhuma inscrição encontrada</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    Ainda não localizamos uma inscrição vinculada ao seu cadastro. Se você já se inscreveu,
                    aguarde alguns instantes e recarregue a página.
                </p>
            </section>
        @else
            {{-- Cartão de progresso --}}
            <section aria-labelledby="titulo-progresso"
                     class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 id="titulo-progresso" class="text-lg font-semibold text-gray-900 dark:text-white">
                            Progresso do envio
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ $resumo['enviados'] }} de {{ $resumo['total'] }} documentos enviados.
                        </p>
                    </div>

                    <div class="w-full sm:w-72">
                        <div class="mb-1 flex items-center justify-between text-xs font-medium text-gray-700 dark:text-gray-200">
                            <span>{{ $percentual }}%</span>
                            <span>{{ $resumo['pendentes'] }} pendente(s)</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"
                             role="progressbar"
                             aria-valuenow="{{ $percentual }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                 style="width: {{ $percentual }}%"></div>
                        </div>
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-center">
                        <dt class="text-xs font-semibold text-amber-800">Pendentes</dt>
                        <dd class="mt-1 text-xl font-black text-amber-900">{{ $resumo['pendentes'] }}</dd>
                    </div>
                    <div class="rounded-xl border border-sky-200 bg-sky-50 p-3 text-center">
                        <dt class="text-xs font-semibold text-sky-800">Enviados</dt>
                        <dd class="mt-1 text-xl font-black text-sky-900">{{ $resumo['enviados'] }}</dd>
                    </div>
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-3 text-center">
                        <dt class="text-xs text-blue-700 dark:text-blue-300">Em análise</dt>
                        <dd class="mt-1 text-xl font-black text-indigo-900">{{ $resumo['em_analise'] }}</dd>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-center">
                        <dt class="text-xs text-emerald-700 dark:text-emerald-300">Aprovados</dt>
                        <dd class="mt-1 text-xl font-black text-emerald-900">{{ $resumo['aprovados'] }}</dd>
                    </div>
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-center">
                        <dt class="text-xs text-red-700 dark:text-red-300">Rejeitados</dt>
                        <dd class="mt-1 text-xl font-black text-rose-900">{{ $resumo['rejeitados'] }}</dd>
                    </div>
                </dl>
            </section>

            {{-- Lista de documentos --}}
            <section aria-labelledby="titulo-lista" class="space-y-4">
                <h2 id="titulo-lista" class="sr-only">Documentos exigidos</h2>

                @foreach ($resumo['documentos'] as $documento)
                    @php
                        $cores = [
                            'pendente' => ['bg' => 'bg-amber-100 text-amber-900 ring-1 ring-inset ring-amber-300', 'chip' => 'bg-amber-600', 'border' => 'border-l-amber-500'],
                            'enviado' => ['bg' => 'bg-sky-100 text-sky-900 ring-1 ring-inset ring-sky-300', 'chip' => 'bg-sky-600', 'border' => 'border-l-sky-500'],
                            'em_analise' => ['bg' => 'bg-indigo-100 text-indigo-900 ring-1 ring-inset ring-indigo-300', 'chip' => 'bg-indigo-600', 'border' => 'border-l-indigo-500'],
                            'aprovado' => ['bg' => 'bg-emerald-100 text-emerald-900 ring-1 ring-inset ring-emerald-300', 'chip' => 'bg-emerald-600', 'border' => 'border-l-emerald-500'],
                            'rejeitado' => ['bg' => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-300', 'chip' => 'bg-rose-600', 'border' => 'border-l-rose-500'],
                        ][$documento['status']] ?? ['bg' => 'bg-slate-100 text-slate-800 ring-1 ring-inset ring-slate-300', 'chip' => 'bg-slate-600', 'border' => 'border-l-slate-500'];

                        $idForm = 'form-doc-'.$documento['tipo'];
                        $idInput = 'input-doc-'.$documento['tipo'];
                    @endphp

                    <article
                        aria-labelledby="titulo-doc-{{ $documento['tipo'] }}"
                        class="rounded-2xl border border-slate-200 border-l-4 {{ $cores['border'] }} bg-white p-5 shadow-md transition-shadow hover:shadow-lg dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 id="titulo-doc-{{ $documento['tipo'] }}"
                                        class="text-base font-semibold text-gray-900 dark:text-white">
                                        {{ $documento['label'] }}
                                    </h3>
                                    @if ($documento['obrigatorio'])
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                            Obrigatório
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            Opcional
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $documento['descricao'] }}
                                </p>

                                @if ($documento['nome_original'])
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        Arquivo atual: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $documento['nome_original'] }}</span>
                                        @if ($documento['tamanho_formatado'])
                                            · {{ $documento['tamanho_formatado'] }}
                                        @endif
                                    </p>
                                @endif

                                @if ($documento['status'] === 'rejeitado' && $documento['justificativa_publica'])
                                    <p role="alert" class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                                        <strong class="font-semibold">Justificativa da secretaria:</strong>
                                        {{ $documento['justificativa_publica'] }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex shrink-0 flex-col items-start gap-2 sm:items-end">
                                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium {{ $cores['bg'] }}">
                                    <span class="h-2 w-2 rounded-full {{ $cores['chip'] }}" aria-hidden="true"></span>
                                    {{ $documento['status_label'] }}
                                </span>

                                @if ($resumo['pode_enviar'] && $documento['status'] !== 'aprovado')
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-lg bg-[#003366] px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-[#002244] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#003366] focus-visible:ring-offset-2"
                                        onclick="document.getElementById('{{ $idForm }}').classList.toggle('hidden'); document.getElementById('{{ $idInput }}').focus();"
                                        aria-expanded="false"
                                        aria-controls="{{ $idForm }}"
                                    >
                                        Enviar documento
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if ($resumo['pode_enviar'] && $documento['status'] !== 'aprovado')
                            <form
                                id="{{ $idForm }}"
                                action="{{ route('aluno.documentos-inscricao.store') }}"
                                method="POST"
                                enctype="multipart/form-data"
                                class="mt-4 hidden rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800"
                                data-dropzone
                            >
                                @csrf
                                <input type="hidden" name="tipo" value="{{ $documento['tipo'] }}">

                                <label for="{{ $idInput }}" class="block text-sm font-medium text-gray-800 dark:text-gray-100">
                                    Selecionar arquivo (JPG, PNG ou PDF, até 10 MB)
                                </label>
                                <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <input
                                        id="{{ $idInput }}"
                                        type="file"
                                        name="arquivo"
                                        accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
                                        required
                                        class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-emerald-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-emerald-700 dark:text-gray-200"
                                        aria-describedby="ajuda-{{ $documento['tipo'] }}"
                                        data-file-input
                                    >
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2"
                                    >
                                        Enviar
                                    </button>
                                </div>
                                <p id="ajuda-{{ $documento['tipo'] }}" class="mt-2 text-xs text-gray-500 dark:text-gray-400" data-file-preview>
                                    Nenhum arquivo selecionado.
                                </p>
                            </form>
                        @endif
                    </article>
                @endforeach
            </section>

            <script>
                (function () {
                    document.querySelectorAll('[data-dropzone]').forEach(function (form) {
                        var input = form.querySelector('[data-file-input]');
                        var preview = form.querySelector('[data-file-preview]');
                        if (!input || !preview) return;

                        function atualizarPreview(file) {
                            if (!file) {
                                preview.textContent = 'Nenhum arquivo selecionado.';
                                return;
                            }
                            var kb = (file.size / 1024).toFixed(0);
                            preview.textContent = file.name + ' — ' + kb + ' KB — ' + (file.type || 'tipo desconhecido');
                        }

                        input.addEventListener('change', function () {
                            atualizarPreview(input.files && input.files[0]);
                        });

                        ['dragenter', 'dragover'].forEach(function (ev) {
                            form.addEventListener(ev, function (e) {
                                e.preventDefault();
                                form.classList.add('ring-2', 'ring-emerald-400');
                            });
                        });
                        ['dragleave', 'drop'].forEach(function (ev) {
                            form.addEventListener(ev, function (e) {
                                e.preventDefault();
                                form.classList.remove('ring-2', 'ring-emerald-400');
                            });
                        });
                        form.addEventListener('drop', function (e) {
                            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                                input.files = e.dataTransfer.files;
                                atualizarPreview(e.dataTransfer.files[0]);
                            }
                        });
                    });
                })();
            </script>
        @endif
    </main>
@endsection
