@extends('secretaria.central.layout')
@section('title',$aula?->exists ? 'Editar aula' : 'Nova aula')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.ensino.painel') }}">Ensino e aprendizagem</a><span aria-hidden="true">/</span><a href="{{ route('secretaria.ensino.aulas') }}">Aulas</a><span aria-hidden="true">/</span><span>{{ $aula?->exists ? 'Editar aula' : 'Nova aula' }}</span>@endsection
@section('content')
<a href="{{ route('secretaria.ensino.aulas') }}" class="sc-back">@include('secretaria.central.icone',['nome'=>'voltar']) Voltar às aulas</a>
<div class="sc-intro"><div><p class="sc-eyebrow">Editor da sala de aula</p><h1>{{ $aula?->exists ? $aula->titulo : 'Preparar nova aula' }}</h1>
<p>Selecione uma disciplina, monte o conteúdo e anexe os materiais que precisam acompanhar o estudante.</p></div></div>
@include('secretaria.central.ensino.nav',['secao'=>'aulas'])
<div class="sc-en-banner"><strong>Segurança da publicação:</strong> arquivos são privados e acessados na sala de aula por alunos matriculados, apenas quando a aula está liberada e o download autorizado.</div>
<div class="sc-en-two">
<section class="sc-panel"><div class="sc-panel-heading"><h2>Conteúdos da aula</h2><p>Todos os campos são salvos no cadastro real de aulas do LMS.</p></div>
<div class="sc-panel-body">
<form class="sc-en-form" method="post" enctype="multipart/form-data" action="{{ $aula?->exists ? route('secretaria.ensino.aulas.atualizar',$aula) : route('secretaria.ensino.aulas.salvar') }}">
@csrf @if($aula?->exists) @method('PUT') @endif
<div class="sc-en-fields">
<div class="wide"><label for="au-disc">Disciplina (curso e módulo)</label><select id="au-disc" name="disciplina_id" required>
<option value="">Selecione uma disciplina</option>
@foreach($disciplinas as $disc)<option value="{{ $disc->id }}" @selected(old('disciplina_id',$aula?->disciplina_id)==$disc->id)>{{ $disc->curso?->titulo ?? 'Curso' }} → {{ $modulos->firstWhere('id',$disc->modulo_id)?->titulo ?? 'Sem módulo' }} → {{ $disc->nome }}</option>@endforeach
</select><small>Disciplinas precisam estar vinculadas a um módulo válido.</small></div>
<div class="wide"><label for="au-titulo">Título da aula</label><input id="au-titulo" maxlength="255" name="titulo" required value="{{ old('titulo',$aula?->titulo) }}"></div>
<div><label for="au-ordem">Ordem na disciplina</label><input id="au-ordem" type="number" name="ordem" min="1" max="9999" required value="{{ old('ordem',$aula?->ordem??1) }}"></div>
<div><label for="au-duracao">Duração prevista (minutos)</label><input id="au-duracao" type="number" name="duracao" min="0" max="100000" value="{{ old('duracao',$aula?->duracao??0) }}"></div>
<div class="wide"><label for="au-desc">Apresentação e objetivos da aula</label><textarea id="au-desc" name="descricao" rows="3">{{ old('descricao',$aula?->descricao) }}</textarea></div>
<div class="wide"><label for="au-video">Link da videoaula (HTTPS)</label><input id="au-video" type="url" name="video_url" maxlength="1200" placeholder="https://www.youtube.com/watch?v=..." value="{{ old('video_url',$aula?->video_url) }}"><small>O link não significa que o vídeo tenha sido assistido pelo aluno.</small></div>
<div class="wide"><label for="au-texto">Texto completo da aula</label><textarea id="au-texto" name="conteudo_texto" rows="9" placeholder="Introdução, referências, desenvolvimento, leitura complementar...">{{ old('conteudo_texto',$aula?->conteudo_texto) }}</textarea></div>
<div class="wide"><label for="au-transcricao">Transcrição revisada do vídeo (opcional)</label><textarea id="au-transcricao" name="transcricao_video" rows="5" placeholder="Cole aqui a transcrição do conteúdo da videoaula">{{ old('transcricao_video',$aula?->transcricao_video) }}</textarea><small>Pode ser usada na criação assistida de rascunhos de questões.</small></div>
<div class="wide"><label for="au-arquivos">Adicionar materiais didáticos</label><input id="au-arquivos" type="file" name="materiais[]" multiple accept=".pdf,.doc,.docx,.ppt,.pptx,.odt,.odp,.txt,.mp3,.mp4,.jpg,.jpeg,.png">
<small>PDF, documentos, slides, áudio, vídeo e imagens; até 8 arquivos por envio, 15 MB por arquivo. Os arquivos anteriores serão mantidos.</small></div>
<div class="wide sc-en-inline">
<label class="sc-en-check"><input type="checkbox" name="liberada" value="1" @checked(old('liberada',$aula?->liberada??false))> Aula liberada</label>
<label class="sc-en-check"><input type="checkbox" name="permite_download" value="1" @checked(old('permite_download',$aula?->permite_download??true))> Permitir download</label>
<label class="sc-en-check"><input type="checkbox" name="permite_comentarios" value="1" @checked(old('permite_comentarios',$aula?->permite_comentarios??true))> Permitir comentários</label></div>
</div>
<button class="sc-button sc-primary" type="submit">{{ $aula?->exists ? 'Salvar alterações da aula' : 'Criar aula e salvar materiais' }}</button>
</form></div></section>
<aside class="sc-aside">
<section class="sc-side-card sc-side-gold"><h2>Prévia dos conteúdos cadastrados</h2>
@if($aula?->exists)
<p><strong>{{ $aula->titulo }}</strong></p>
<p>{{ $aula->disciplina?->nome ?? 'Disciplina pendente' }} · {{ $aula->video_url ? 'Vídeo associado' : 'Sem vídeo' }}</p>
@if($aula->video_url)<p><a class="sc-text-link" href="{{ $aula->video_url }}" rel="noopener noreferrer" target="_blank">Abrir vídeo em outra guia ↗</a></p>@endif
@if($aula->conteudo_texto)<p style="white-space:pre-wrap;margin-top:12px">{{ \Illuminate\Support\Str::limit($aula->conteudo_texto,800) }}</p>@endif
@else<p>Após criar a aula, confira nesta área os conteúdos e arquivos vinculados.</p>@endif
</section>
<section class="sc-side-card"><h2>Arquivos anexados</h2>
@forelse($aula?->anexos??[] as $indice=>$arquivo)
@if(is_array($arquivo)&&isset($arquivo['caminho']))
<div class="sc-en-file"><span>{{ $arquivo['nome']??'Material' }}</span><a class="sc-text-link" href="{{ route('secretaria.ensino.aulas.material',[$aula,$indice]) }}">Baixar</a></div>
@endif
@empty<p>Nenhum arquivo enviado por este editor.</p>@endforelse
</section>
@if($aula?->exists)
<section class="sc-side-card"><h2>Quiz vinculado à aula</h2><p>Crie um quiz com questões revisadas no banco. Não confunda com as provas e simulados independentes.</p><a class="sc-text-link" href="{{ route('secretaria.ensino.avaliacoes',['tipo'=>'quiz']) }}">Abrir quizzes →</a></section>
@endif
</aside></div>
@endsection
