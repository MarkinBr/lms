<?php

namespace App\Filament\Professor\Resources;

use App\Filament\Professor\Resources\AulaResource\Pages;
use App\Models\Aula;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Support\TranscricaoVideo;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class AulaResource extends Resource
{
    protected static ?string $model = Aula::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';

    protected static ?string $navigationLabel = 'Aulas';

    protected static ?string $modelLabel = 'Aula';

    protected static ?string $pluralModelLabel = 'Aulas';

    protected static ?string $navigationGroup = 'Gerenciamento de Conteúdo';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ═══════════════════════════════════════════════════════
                // MEGA FORM - EDITOR DE AULA ESTILO ESTRATÉGIA CONCURSOS
                // ═══════════════════════════════════════════════════════
                Tabs::make('Editor de Aula')
                    ->tabs([
                        // ───────────────────────────────────────
                        // TAB 1: INFORMAÇÕES E CONFIGURAÇÃO
                        // ───────────────────────────────────────
                        Tabs\Tab::make('📋 Informações')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Identificação da Aula')
                                    ->description('Dados básicos da aula')
                                    ->icon('heroicon-o-document-text')
                                    ->collapsible()
                                    ->schema([
                                        Forms\Components\Select::make('disciplina_id')
                                            ->label('Disciplina')
                                            ->options(function () {
                                                $professorId = auth()->id();

                                                return Disciplina::query()
                                                    ->where('ativa', true)
                                                    ->whereNotNull('modulo_id')
                                                    ->whereHas('modulo.curso', fn ($query) => $query->where('instrutor_id', $professorId))
                                                    ->with('modulo.curso')
                                                    ->orderBy('ordem')
                                                    ->get()
                                                    ->mapWithKeys(fn (Disciplina $disciplina) => [
                                                        $disciplina->id => $disciplina->modulo->curso->titulo.' → '.$disciplina->modulo->titulo.' → '.$disciplina->nome,
                                                    ]);
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpanFull()
                                            ->helperText('Selecione a disciplina; o módulo será definido automaticamente no servidor.'),

                                        Forms\Components\TextInput::make('titulo')
                                            ->label('Título da Aula')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Ex: Aula 01 - Princípios do Direito Administrativo')
                                            ->columnSpanFull()
                                            ->autofocus(),

                                        Forms\Components\Textarea::make('descricao')
                                            ->label('Descrição / Ementa')
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->placeholder('Breve descrição do conteúdo desta aula...'),

                                        Forms\Components\Select::make('tipo_conteudo')
                                            ->label('Tipo de Conteúdo Principal')
                                            ->options([
                                                'video' => '🎬 Vídeo Aula',
                                                'texto' => '📝 Texto / Artigo',
                                                'misto' => '📦 Completo (Vídeo + Texto + Materiais)',
                                                'quiz' => '❓ Quiz / Avaliação',
                                            ])
                                            ->default('misto')
                                            ->required()
                                            ->reactive()
                                            ->helperText('Escolha "Completo" para ter tudo na mesma página, como no Estratégia'),

                                        Forms\Components\TextInput::make('duracao')
                                            ->label('Duração')
                                            ->placeholder('Ex: 45:30 ou 1:15:30')
                                            ->helperText('Formato: MM:SS ou HH:MM:SS'),

                                        Forms\Components\TextInput::make('ordem')
                                            ->label('Ordem')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->minValue(1),
                                    ])
                                    ->columns(3),

                                Section::make('Configurações de Liberação')
                                    ->icon('heroicon-o-lock-open')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Forms\Components\Toggle::make('liberada')
                                            ->label('Aula Liberada')
                                            ->default(true)
                                            ->inline(false),

                                        Forms\Components\Select::make('tipo_liberacao')
                                            ->label('Tipo de Liberação')
                                            ->options([
                                                'imediata' => '⚡ Imediata',
                                                'agendada' => '📅 Agendada',
                                                'sequencial' => '🔗 Sequencial (após anterior)',
                                            ])
                                            ->default('imediata')
                                            ->reactive(),

                                        Forms\Components\DateTimePicker::make('data_liberacao')
                                            ->label('Data de Liberação')
                                            ->visible(fn (Get $get) => $get('tipo_liberacao') === 'agendada'),

                                        Forms\Components\Toggle::make('permite_download')
                                            ->label('Permitir Download de Materiais')
                                            ->default(true)
                                            ->inline(false),

                                        Forms\Components\Toggle::make('permite_comentarios')
                                            ->label('Permitir Comentários')
                                            ->default(true)
                                            ->inline(false),

                                        Forms\Components\TextInput::make('tempo_minimo_minutos')
                                            ->label('Tempo Mínimo na Aula (min)')
                                            ->numeric()
                                            ->placeholder('Opcional')
                                            ->helperText('Tempo mínimo para poder marcar como concluída'),
                                    ])
                                    ->columns(3),
                            ]),

                        // ───────────────────────────────────────
                        // TAB 2: VÍDEO AULA
                        // ───────────────────────────────────────
                        Tabs\Tab::make('🎬 Vídeo')
                            ->icon('heroicon-o-video-camera')
                            ->schema([
                                Section::make('Vídeo Principal')
                                    ->description('Configure o vídeo principal da aula')
                                    ->icon('heroicon-o-play')
                                    ->schema([
                                        Forms\Components\Select::make('video_provider')
                                            ->label('Plataforma do Vídeo')
                                            ->options([
                                                'youtube' => '▶️ YouTube',
                                                'vimeo' => '🎬 Vimeo',
                                                'bunny' => '🐰 Bunny Stream',
                                                'direto' => '🔗 Link Direto (MP4)',
                                                'upload' => '📤 Upload Direto',
                                            ])
                                            ->default('youtube')
                                            ->reactive()
                                            ->helperText('Selecione onde seu vídeo está hospedado'),

                                        Forms\Components\TextInput::make('video_url')
                                            ->label('URL do Vídeo')
                                            ->placeholder(fn (Get $get) => match ($get('video_provider')) {
                                                'youtube' => 'https://www.youtube.com/watch?v=XXXXX',
                                                'vimeo' => 'https://vimeo.com/XXXXX',
                                                'bunny' => 'https://iframe.mediadelivery.net/embed/XXXXX',
                                                default => 'https://exemplo.com/video.mp4',
                                            })
                                            ->url()
                                            ->columnSpanFull()
                                            ->visible(fn (Get $get) => $get('video_provider') !== 'upload')
                                            ->helperText('Cole a URL completa do vídeo'),

                                        Forms\Components\Textarea::make('transcricao_video')
                                            ->label('Transcrição e legendas do vídeo')
                                            ->helperText('Cole a transcrição em texto simples ou o conteúdo de um arquivo WebVTT (.vtt). WebVTT habilita legendas e trechos clicáveis. Revise o texto antes de publicar.')
                                            ->rows(8)
                                            ->maxLength(TranscricaoVideo::MAX_CARACTERES)
                                            ->columnSpanFull(),

                                        Forms\Components\FileUpload::make('video_upload')
                                            ->label('Upload do Vídeo')
                                            ->disk('public')
                                            ->directory('aulas/videos')
                                            ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg'])
                                            ->maxSize(512000) // 500MB
                                            ->visible(fn (Get $get) => $get('video_provider') === 'upload')
                                            ->columnSpanFull()
                                            ->helperText('Máximo 500MB. Formatos: MP4, WebM, OGG'),

                                        Forms\Components\FileUpload::make('thumbnail')
                                            ->label('Thumbnail do Vídeo')
                                            ->image()
                                            ->directory('aulas/thumbnails')
                                            ->imageEditor()
                                            ->maxSize(2048)
                                            ->helperText('Imagem que aparece antes de reproduzir (16:9)')
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('duracao')
                                            ->label('Duração do Vídeo')
                                            ->placeholder('45:30')
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),

                                Section::make('Prévia do Vídeo')
                                    ->visible(fn (Get $get) => ! empty($get('video_url')))
                                    ->schema([
                                        Forms\Components\Placeholder::make('video_preview')
                                            ->label('')
                                            ->content(function (Get $get) {
                                                $url = $get('video_url');
                                                if (empty($url)) {
                                                    return 'Nenhum vídeo configurado';
                                                }

                                                // Extrair ID do YouTube
                                                if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
                                                    preg_match('/(?:v=|\/)([\w-]{11})/', $url, $matches);
                                                    $videoId = $matches[1] ?? '';
                                                    if ($videoId) {
                                                        return new HtmlString(
                                                            '<div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.15);">
                                                                <iframe src="https://www.youtube.com/embed/'.$videoId.'"
                                                                    style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;"
                                                                    allowfullscreen></iframe>
                                                            </div>'
                                                        );
                                                    }
                                                }

                                                return new HtmlString(
                                                    '<div style="padding:20px;background:#f3f4f6;border-radius:12px;text-align:center;">
                                                        <p>🎬 Vídeo configurado: <a href="'.e($url).'" target="_blank" style="color:#3b82f6;">'.e($url).'</a></p>
                                                    </div>'
                                                );
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ───────────────────────────────────────
                        // TAB 3: CONTEÚDO TEXTO (EDITOR RICO)
                        // ───────────────────────────────────────
                        Tabs\Tab::make('📝 Conteúdo Texto')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Editor de Conteúdo')
                                    ->description('Escreva o conteúdo completo da aula. Pode incluir formatação rica, imagens, tabelas e muito mais.')
                                    ->icon('heroicon-o-pencil-square')
                                    ->schema([
                                        Forms\Components\RichEditor::make('conteudo_texto')
                                            ->label('Conteúdo da Aula')
                                            ->toolbarButtons([
                                                'blockquote',
                                                'bold',
                                                'bulletList',
                                                'codeBlock',
                                                'h2',
                                                'h3',
                                                'h4',
                                                'italic',
                                                'link',
                                                'orderedList',
                                                'redo',
                                                'strike',
                                                'underline',
                                                'undo',
                                            ])
                                            ->columnSpanFull()
                                            ->helperText('Anexos devem ser publicados pelo fluxo privado de documentos.'),
                                    ]),

                                Section::make('Blocos de Conteúdo Estruturado')
                                    ->description('Adicione blocos adicionais de conteúdo organizados (destaques, resumos, dicas, etc.)')
                                    ->icon('heroicon-o-squares-plus')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Forms\Components\Repeater::make('blocos_conteudo')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\Select::make('tipo')
                                                    ->label('Tipo do Bloco')
                                                    ->options([
                                                        'destaque' => '⭐ Destaque / Importante',
                                                        'resumo' => '📋 Resumo',
                                                        'dica' => '💡 Dica do Professor',
                                                        'atencao' => '⚠️ Atenção',
                                                        'exemplo' => '📖 Exemplo Prático',
                                                        'jurisprudencia' => '⚖️ Jurisprudência',
                                                        'lei_seca' => '📜 Lei Seca',
                                                        'exercicio' => '✏️ Exercício Resolvido',
                                                        'mnemonica' => '🧠 Técnica Mnemônica',
                                                        'video_extra' => '🎬 Vídeo Complementar',
                                                        'audio' => '🎧 Áudio',
                                                        'imagem' => '🖼️ Imagem / Diagrama',
                                                    ])
                                                    ->required()
                                                    ->reactive()
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('titulo')
                                                    ->label('Título do Bloco')
                                                    ->placeholder('Ex: Resumo do Artigo 37')
                                                    ->columnSpan(2),

                                                Forms\Components\RichEditor::make('conteudo')
                                                    ->label('Conteúdo')
                                                    ->toolbarButtons([
                                                        'bold', 'italic', 'underline', 'strike',
                                                        'bulletList', 'orderedList',
                                                        'h3', 'h4', 'link', 'blockquote',
                                                        'undo', 'redo',
                                                    ])
                                                    ->columnSpanFull()
                                                    ->visible(fn (Get $get) => ! in_array($get('tipo'), ['video_extra', 'audio', 'imagem'])),

                                                Forms\Components\TextInput::make('url')
                                                    ->label('URL do Recurso')
                                                    ->url()
                                                    ->columnSpanFull()
                                                    ->visible(fn (Get $get) => in_array($get('tipo'), ['video_extra', 'audio', 'imagem']))
                                                    ->placeholder(fn (Get $get) => match ($get('tipo')) {
                                                        'video_extra' => 'https://www.youtube.com/watch?v=...',
                                                        'audio' => 'https://exemplo.com/audio.mp3',
                                                        'imagem' => 'https://exemplo.com/imagem.png',
                                                        default => '',
                                                    }),

                                                Forms\Components\TextInput::make('ordem')
                                                    ->label('Ordem')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->columnSpan(1),
                                            ])
                                            ->columns(3)
                                            ->collapsible()
                                            ->collapsed()
                                            ->itemLabel(fn (array $state): ?string => ($state['tipo'] ?? '').': '.($state['titulo'] ?? 'Sem título')
                                            )
                                            ->defaultItems(0)
                                            ->addActionLabel('+ Adicionar Bloco de Conteúdo')
                                            ->reorderable()
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ───────────────────────────────────────
                        // TAB 4: ÁUDIO DA AULA
                        // ───────────────────────────────────────
                        Tabs\Tab::make('🎧 Áudio')
                            ->icon('heroicon-o-musical-note')
                            ->schema([
                                Section::make('Áudio da Aula')
                                    ->description('Adicione áudio para os alunos ouvirem (aula em áudio, podcast, etc.)')
                                    ->icon('heroicon-o-speaker-wave')
                                    ->schema([
                                        Forms\Components\TextInput::make('audio_titulo')
                                            ->label('Título do Áudio')
                                            ->placeholder('Ex: Áudio da Aula 01 - Princípios')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('audio_url')
                                            ->label('URL do Áudio')
                                            ->url()
                                            ->placeholder('https://exemplo.com/audio.mp3')
                                            ->helperText('Cole a URL do arquivo de áudio (MP3, WAV, OGG)')
                                            ->columnSpan(1),

                                        Forms\Components\FileUpload::make('audio_upload')
                                            ->label('Ou faça Upload do Áudio')
                                            ->disk('public')
                                            ->directory('aulas/audios')
                                            ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/m4a'])
                                            ->maxSize(102400) // 100MB
                                            ->helperText('Máximo 100MB. Formatos: MP3, WAV, OGG, M4A')
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),
                            ]),

                        // ───────────────────────────────────────
                        // TAB 5: PDFs E MATERIAIS DE APOIO
                        // ───────────────────────────────────────
                        Tabs\Tab::make('📎 Materiais')
                            ->icon('heroicon-o-paper-clip')
                            ->badge(fn (?Aula $record) => $record ? count($record->materiais ?? []) + count($record->anexos ?? []) : null)
                            ->schema([
                                Section::make('PDF Principal da Aula')
                                    ->description('PDF com o conteúdo da matéria (tipo apostila do Estratégia)')
                                    ->icon('heroicon-o-document')
                                    ->schema([
                                        Forms\Components\TextInput::make('pdf_titulo')
                                            ->label('Título do PDF')
                                            ->placeholder('Ex: Apostila - Princípios Administrativos'),

                                        Forms\Components\TextInput::make('pdf_url')
                                            ->label('URL do PDF')
                                            ->url()
                                            ->placeholder('https://exemplo.com/apostila.pdf')
                                            ->helperText('URL externa do PDF'),

                                        Forms\Components\FileUpload::make('pdf_upload')
                                            ->label('Ou faça Upload do PDF')
                                            ->disk('public')
                                            ->directory('aulas/pdfs')
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->maxSize(51200) // 50MB
                                            ->helperText('Máximo 50MB')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Section::make('Materiais de Apoio')
                                    ->description('Adicione slides, exercícios, resumos, mapas mentais, etc.')
                                    ->icon('heroicon-o-folder-arrow-down')
                                    ->schema([
                                        Forms\Components\Repeater::make('materiais')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\TextInput::make('titulo')
                                                    ->label('Nome do Material')
                                                    ->required()
                                                    ->placeholder('Ex: Slides da Aula')
                                                    ->columnSpan(2),

                                                Forms\Components\Select::make('tipo')
                                                    ->label('Tipo')
                                                    ->options([
                                                        'pdf' => '📄 PDF',
                                                        'slide' => '📊 Slides/PPT',
                                                        'exercicio' => '✏️ Lista de Exercícios',
                                                        'resumo' => '📋 Resumo',
                                                        'mapa_mental' => '🧠 Mapa Mental',
                                                        'planilha' => '📈 Planilha',
                                                        'video' => '🎬 Vídeo Extra',
                                                        'audio' => '🎧 Áudio Extra',
                                                        'link' => '🔗 Link Externo',
                                                        'arquivo' => '📁 Outro Arquivo',
                                                    ])
                                                    ->required()
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('url')
                                                    ->label('URL do Material')
                                                    ->url()
                                                    ->placeholder('https://...')
                                                    ->columnSpan(2),

                                                Forms\Components\TextInput::make('descricao')
                                                    ->label('Descrição')
                                                    ->placeholder('Breve descrição...')
                                                    ->columnSpan(1),
                                            ])
                                            ->columns(6)
                                            ->collapsible()
                                            ->collapsed()
                                            ->itemLabel(fn (array $state): ?string => ($state['titulo'] ?? 'Material sem nome'))
                                            ->defaultItems(0)
                                            ->addActionLabel('+ Adicionar Material')
                                            ->reorderable()
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Upload em Lote de Anexos')
                                    ->description('Faça upload de múltiplos arquivos de uma vez')
                                    ->icon('heroicon-o-arrow-up-tray')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Forms\Components\FileUpload::make('anexos')
                                            ->label('Anexos')
                                            ->multiple()
                                            ->disk('public')
                                            ->directory('aulas/anexos')
                                            ->maxSize(51200)
                                            ->maxFiles(20)
                                            ->helperText('Arraste e solte até 20 arquivos. Máximo 50MB cada.')
                                            ->columnSpanFull()
                                            ->reorderable(),
                                    ]),
                            ]),

                        // ───────────────────────────────────────
                        // TAB 6: QUESTÕES / QUIZ
                        // ───────────────────────────────────────
                        Tabs\Tab::make('❓ Questões')
                            ->icon('heroicon-o-question-mark-circle')
                            ->badge(fn (?Aula $record) => $record?->questoes_quantidade ?: null)
                            ->schema([
                                Section::make('Configurações do Quiz')
                                    ->description('Configure como as questões serão apresentadas ao aluno')
                                    ->icon('heroicon-o-cog')
                                    ->collapsible()
                                    ->schema([
                                        Forms\Components\Toggle::make('questoes_aleatorias')
                                            ->label('Embaralhar Questões')
                                            ->helperText('As questões aparecerão em ordem aleatória para cada aluno')
                                            ->default(false)
                                            ->inline(false),

                                        Forms\Components\TextInput::make('nota_minima_aprovacao')
                                            ->label('Nota Mínima para Aprovação (%)')
                                            ->numeric()
                                            ->placeholder('Ex: 70')
                                            ->suffix('%')
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->helperText('Deixe vazio se não houver nota mínima'),

                                        Forms\Components\TextInput::make('questoes_quantidade')
                                            ->label('Quantidade de Questões')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->helperText('Calculado automaticamente'),
                                    ])
                                    ->columns(3),

                                Section::make('Banco de Questões')
                                    ->description('Crie questões de múltipla escolha, verdadeiro/falso, dissertativas, etc.')
                                    ->icon('heroicon-o-clipboard-document-list')
                                    ->schema([
                                        Forms\Components\Repeater::make('questoes')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\Select::make('tipo')
                                                    ->label('Tipo da Questão')
                                                    ->options([
                                                        'multipla_escolha' => '🔘 Múltipla Escolha',
                                                        'verdadeiro_falso' => '✅ Verdadeiro ou Falso',
                                                        'certo_errado' => '☑️ Certo ou Errado',
                                                        'dissertativa' => '📝 Dissertativa',
                                                    ])
                                                    ->required()
                                                    ->default('multipla_escolha')
                                                    ->reactive()
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('pontos')
                                                    ->label('Pontos')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(1)
                                                    ->columnSpan(1),

                                                Forms\Components\Select::make('banca')
                                                    ->label('Banca (opcional)')
                                                    ->options([
                                                        'CESPE/CEBRASPE' => 'CESPE/CEBRASPE',
                                                        'FCC' => 'FCC',
                                                        'FGV' => 'FGV',
                                                        'VUNESP' => 'VUNESP',
                                                        'IBFC' => 'IBFC',
                                                        'IDECAN' => 'IDECAN',
                                                        'AOCP' => 'AOCP',
                                                        'Elaboração Própria' => 'Elaboração Própria',
                                                    ])
                                                    ->placeholder('Selecione...')
                                                    ->columnSpan(1),

                                                Forms\Components\RichEditor::make('enunciado')
                                                    ->label('Enunciado da Questão')
                                                    ->required()
                                                    ->toolbarButtons([
                                                        'bold', 'italic', 'underline',
                                                        'bulletList', 'orderedList',
                                                        'link',
                                                    ])
                                                    ->columnSpanFull(),

                                                // Alternativas para Múltipla Escolha
                                                Forms\Components\Repeater::make('alternativas')
                                                    ->label('Alternativas')
                                                    ->schema([
                                                        Forms\Components\TextInput::make('letra')
                                                            ->label('Letra')
                                                            ->maxLength(2)
                                                            ->placeholder('A')
                                                            ->columnSpan(1),

                                                        Forms\Components\TextInput::make('texto')
                                                            ->label('Texto da Alternativa')
                                                            ->required()
                                                            ->columnSpan(4),

                                                        Forms\Components\Toggle::make('correta')
                                                            ->label('Correta?')
                                                            ->inline(false)
                                                            ->columnSpan(1),
                                                    ])
                                                    ->columns(6)
                                                    ->defaultItems(4)
                                                    ->addActionLabel('+ Alternativa')
                                                    ->visible(fn (Get $get) => $get('tipo') === 'multipla_escolha')
                                                    ->columnSpanFull(),

                                                // Para V/F e C/E
                                                Forms\Components\Toggle::make('resposta_correta')
                                                    ->label('A afirmação é VERDADEIRA/CERTA?')
                                                    ->visible(fn (Get $get) => in_array($get('tipo'), ['verdadeiro_falso', 'certo_errado']))
                                                    ->inline(false)
                                                    ->columnSpanFull(),

                                                // Comentário/Resolução
                                                Forms\Components\RichEditor::make('comentario_professor')
                                                    ->label('💡 Comentário / Resolução do Professor')
                                                    ->helperText('Explicação detalhada que aparece após o aluno responder')
                                                    ->toolbarButtons([
                                                        'bold', 'italic', 'underline',
                                                        'bulletList', 'orderedList',
                                                        'h3', 'link',
                                                    ])
                                                    ->columnSpanFull(),

                                                Forms\Components\Textarea::make('referencia_legal')
                                                    ->label('📜 Referência Legal / Fonte')
                                                    ->placeholder('Ex: Art. 37, CF/88 | Súmula 473 STF')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(3)
                                            ->collapsible()
                                            ->collapsed()
                                            ->itemLabel(fn (array $state): ?string => 'Q'.($state['tipo'] ?? '').': '.Str::limit(strip_tags($state['enunciado'] ?? ''), 60)
                                            )
                                            ->defaultItems(0)
                                            ->addActionLabel('+ Adicionar Questão')
                                            ->reorderable()
                                            ->columnSpanFull()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                $set('questoes_quantidade', count($state ?? []));
                                            }),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ordem')
                    ->label('#')
                    ->sortable()
                    ->alignCenter()
                    ->width(50),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Aula')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => Str::limit($record->descricao, 60))
                    ->wrap(),

                Tables\Columns\TextColumn::make('modulo.titulo')
                    ->label('Módulo')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('modulo.curso.titulo')
                    ->label('Curso')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tipo_conteudo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'video' => 'primary',
                        'texto' => 'success',
                        'misto' => 'warning',
                        'quiz' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'video' => '🎬 Vídeo',
                        'texto' => '📝 Texto',
                        'misto' => '📦 Completo',
                        'quiz' => '❓ Quiz',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('duracao')
                    ->label('Duração')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('liberada')
                    ->label('Liberada')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('progressos_count')
                    ->counts('progressos')
                    ->label('Acessos')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Editada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('modulo_id')
                    ->label('Módulo')
                    ->relationship('modulo', 'titulo', function (Builder $query) {
                        $query->whereHas('curso', function ($q) {
                            $q->where('instrutor_id', auth()->id());
                        });
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('curso')
                    ->label('Curso')
                    ->options(function () {
                        return Curso::where('instrutor_id', auth()->id())
                            ->pluck('titulo', 'id');
                    })
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('modulo', function ($q) use ($data) {
                                $q->where('curso_id', $data['value']);
                            });
                        }
                    }),

                Tables\Filters\SelectFilter::make('tipo_conteudo')
                    ->label('Tipo')
                    ->options([
                        'video' => '🎬 Vídeo',
                        'texto' => '📝 Texto',
                        'misto' => '📦 Completo',
                        'quiz' => '❓ Quiz',
                    ]),

                Tables\Filters\TernaryFilter::make('liberada')
                    ->label('Liberada')
                    ->placeholder('Todas')
                    ->trueLabel('Liberadas')
                    ->falseLabel('Bloqueadas'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar Aula')
                    ->icon('heroicon-o-pencil-square'),

                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => route('aluno.aula', $record->id))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('duplicar')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (Aula $record) {
                        $nova = $record->replicate();
                        $nova->titulo = $record->titulo.' (Cópia)';
                        $nova->ordem = $record->ordem + 1;
                        $nova->save();

                        Notification::make()
                            ->title('Aula duplicada!')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('ordem')
            ->defaultSort('ordem')
            ->emptyStateHeading('Nenhuma aula encontrada')
            ->emptyStateDescription('Crie aulas a partir das disciplinas atribuídas aos seus cursos.')
            ->emptyStateIcon('heroicon-o-play-circle');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAulas::route('/'),
            'create' => Pages\CreateAula::route('/create'),
            'edit' => Pages\EditAula::route('/{record}/edit'),
        ];
    }

    // Filtra apenas aulas dos cursos do professor logado
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('modulo.curso', function ($query) {
                $query->where('instrutor_id', auth()->id());
            });
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}
