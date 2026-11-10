{{-- Estado honesto para funcionalidades ainda não implementadas.
     Sem formulários simulados, contagens fictícias ou ações que gravem dados. --}}
<section class="mx-auto max-w-5xl space-y-7" aria-labelledby="recurso-titulo">
    <nav class="flex items-center gap-2 text-sm text-slate-500" aria-label="Navegação">
        <a href="{{ route('aluno.dashboard') }}" class="font-semibold transition hover:text-[#003366] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500">Portal do Aluno</a>
        <i class="fa-solid fa-chevron-right text-[10px] text-slate-400" aria-hidden="true"></i>
        <span class="font-semibold text-[#003366]" aria-current="page">{{ $titulo }}</span>
    </nav>

    <div class="relative isolate overflow-hidden rounded-3xl bg-[#092849] p-7 text-white shadow-xl shadow-blue-950/10 sm:p-10 lg:p-12">
        <div class="pointer-events-none absolute -right-16 -top-24 h-72 w-72 rounded-full border border-white/10 bg-white/5" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-28 right-12 h-56 w-56 rounded-full bg-[#C69D54]/10 blur-2xl" aria-hidden="true"></div>

        <div class="relative max-w-2xl">
            <span class="inline-flex items-center gap-2 rounded-full border border-[#E9CE96]/30 bg-[#D8B579]/10 px-3 py-1.5 text-xs font-bold tracking-wide text-[#F4D79E]">
                <span class="h-2 w-2 rounded-full bg-[#D8B579]" aria-hidden="true"></span>
                Recurso em preparação
            </span>
            <div class="mt-7 flex h-16 w-16 items-center justify-center rounded-2xl border border-white/15 bg-white/10 shadow-lg" aria-hidden="true">
                <i class="fa-solid {{ $icone }} text-2xl text-[#E9CE96]"></i>
            </div>
            <h1 id="recurso-titulo" class="mt-6 text-3xl font-black tracking-tight sm:text-4xl">{{ $titulo }}</h1>
            <p class="mt-4 max-w-xl text-base leading-7 text-blue-100">{{ $descricao }}</p>
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-[1fr_auto] md:items-center rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="max-w-xl">
            <h2 class="text-lg font-extrabold text-slate-900">Enquanto isso, continue sua jornada</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $orientacao }}</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row md:flex-col">
            <a href="{{ route($rotaPrincipal) }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#003366] px-5 py-3 text-sm font-bold text-white shadow-md shadow-blue-950/10 transition hover:bg-[#124977] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500">
                {{ $textoPrincipal }}
                <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </a>
            <a href="{{ route($rotaSecundaria) }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500">
                {{ $textoSecundario }}
            </a>
        </div>
    </div>

    <p class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-900">
        <i class="fa-solid fa-circle-info mt-1 shrink-0" aria-hidden="true"></i>
        Este módulo ainda não permite publicar, enviar ou receber conteúdos. Os atalhos acima levam a áreas já disponíveis do portal.
    </p>
</section>
