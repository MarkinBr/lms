<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaginaInicialResource\Pages;
use App\Models\PaginaInicial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor;

class PaginaInicialResource extends Resource
{
    protected static ?string $model = PaginaInicial::class;
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Site Institucional';
    protected static ?string $modelLabel = 'Página Inicial';

    public static function form(Form $form): Form
    {
        return $form->schema([
            
            // === CONTROLE DE VERSÃO ===
            Section::make('🎨 Configuração do Tema')
                ->description('Nomeie esta versão e defina se ela está ativa')
                ->schema([
                    TextInput::make('titulo_identificacao')
                        ->label('Nome desta Versão')
                        ->placeholder('Ex: Campanha Páscoa 2025')
                        ->required()
                        ->columnSpan(1),

                    Toggle::make('ativa')
                        ->label('Publicar no site?')
                        ->onColor('success')
                        ->offColor('danger')
                        ->helperText('Ativar desativa automaticamente as outras versões')
                        ->columnSpan(1),
                ])->columns(2)->collapsible(),

            // === LAYOUT PRINCIPAL ===
            Group::make()->columnSpan(7)->schema([
                
                Tabs::make('Conteúdo')->tabs([

                    // ========== ABA: HERO ==========
                    Tabs\Tab::make('🏠 Topo (Hero)')
                        ->schema([
                            TextInput::make('hero_titulo_principal')->label('Título Principal')->default('Transforme Seu'),
                            Grid::make(2)->schema([
                                TextInput::make('hero_destaque_1')->label('Palavra 1 (Colorida)')->default('Chamado'),
                                TextInput::make('hero_destaque_2')->label('Palavra 2 (Colorida)')->default('Ministério'),
                            ]),
                            Textarea::make('hero_subtitulo')->label('Subtítulo')->rows(2),
                            
                            Section::make('Mídias do Hero')->schema([
                                FileUpload::make('hero_imagem')->label('Imagem Principal')->image()->directory('hero'),
                                TextInput::make('hero_video_url')->label('URL do Vídeo (YouTube/Vimeo)')->placeholder('https://youtube.com/watch?v=...'),
                            ])->columns(2),

                            Section::make('Botões de Ação')->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('hero_botao_texto')->default('Inscreva-se Agora'),
                                    TextInput::make('hero_botao_link')->default('/admin/register'),
                                ]),
                                Grid::make(2)->schema([
                                    TextInput::make('hero_botao_secundario_texto')->placeholder('Conhecer Cursos'),
                                    TextInput::make('hero_botao_secundario_link')->placeholder('#cursos'),
                                ]),
                            ]),
                        ]),

                    // ========== ABA: ESTATÍSTICAS ==========
                    Tabs\Tab::make('📊 Estatísticas')
                        ->schema([
                            Repeater::make('estatisticas')
                                ->label('Números que Impressionam')
                                ->schema([
                                    TextInput::make('numero')->label('Número')->placeholder('500+')->required(),
                                    TextInput::make('texto')->label('Descrição')->placeholder('Alunos Formados')->required(),
                                    Select::make('icone')->options([
                                        'fas fa-users' => 'Pessoas',
                                        'fas fa-graduation-cap' => 'Formandos',
                                        'fas fa-book' => 'Cursos',
                                        'fas fa-certificate' => 'Certificados',
                                        'fas fa-star' => 'Estrela',
                                        'fas fa-trophy' => 'Troféu',
                                    ])->default('fas fa-users'),
                                ])->columns(3)->defaultItems(4),
                        ]),

                    // ========== ABA: SOBRE ==========
                    Tabs\Tab::make('ℹ️ Sobre Nós')
                        ->schema([
                            TextInput::make('sobre_titulo')->default('Quem Somos'),
                            RichEditor::make('sobre_texto')->label('Texto Completo')->columnSpanFull(),
                            
                            Grid::make(2)->schema([
                                FileUpload::make('sobre_imagem')->image()->directory('sobre'),
                                TextInput::make('sobre_video_url')->label('Vídeo Institucional (URL)'),
                            ]),

                            Repeater::make('sobre_diferenciais')
                                ->label('Nossos Diferenciais')
                                ->schema([
                                    TextInput::make('titulo')->required(),
                                    Textarea::make('descricao')->rows(2),
                                    Select::make('icone')->options([
                                        'fas fa-check-circle' => 'Check',
                                        'fas fa-heart' => 'Coração',
                                        'fas fa-laptop' => 'Tecnologia',
                                        'fas fa-users' => 'Comunidade',
                                        'fas fa-shield-alt' => 'Segurança',
                                    ])->default('fas fa-check-circle'),
                                ])->columns(2),

                            Section::make('Missão, Visão e Valores')->schema([
                                Textarea::make('missao')->rows(3),
                                Textarea::make('visao')->rows(3),
                                Textarea::make('valores')->rows(3),
                            ])->columns(3)->collapsible(),
                        ]),

                    // ========== ABA: CURSOS ==========
                    Tabs\Tab::make('🎓 Cursos')
                        ->schema([
                            TextInput::make('cursos_titulo')->default('Nossos Cursos'),
                            Textarea::make('cursos_descricao')->rows(2),

                            // CORREÇÃO 1: Removido ->simple()
                            Repeater::make('cursos_categorias')
                                ->label('Categorias de Cursos')
                                ->schema([
                                    TextInput::make('nome')->placeholder('Teologia, Ministério, Música...'),
                                ])->columnSpanFull(),

                            Repeater::make('cursos_lista')
                                ->label('Lista de Cursos')
                                ->schema([
                                    FileUpload::make('imagem')->image()->directory('cursos')->columnSpanFull(),
                                    TextInput::make('titulo')->required(),
                                    Select::make('categoria')->label('Categoria')->options(function ($get) {
                                        $cats = $get('../../cursos_categorias') ?? [];
                                        return collect($cats)->pluck('nome', 'nome');
                                    }),
                                    Select::make('nivel')->options([
                                        'INICIANTE' => 'Iniciante',
                                        'INTERMEDIÁRIO' => 'Intermediário',
                                        'AVANÇADO' => 'Avançado',
                                    ])->default('INICIANTE'),
                                    RichEditor::make('descricao_completa')->label('Descrição Completa')->columnSpanFull(),
                                    Textarea::make('resumo')->rows(2)->columnSpanFull(),
                                    
                                    Grid::make(3)->schema([
                                        TextInput::make('duracao')->placeholder('6 meses'),
                                        TextInput::make('carga_horaria')->placeholder('200h'),
                                        TextInput::make('modalidade')->default('Online'),
                                    ]),
                                    
                                    Grid::make(3)->schema([
                                        TextInput::make('investimento')->placeholder('R$ 150/mês'),
                                        TextInput::make('certificado')->default('Certificado Livre'),
                                        TextInput::make('vagas')->placeholder('Limitadas'),
                                    ]),

                                    TextInput::make('link')->default('/admin/register'),
                                ])
                                ->columns(1)
                                ->collapsible()
                                ->itemLabel(fn ($state) => $state['titulo'] ?? 'Novo Curso'),
                        ]),

                    // ========== ABA: METODOLOGIA ==========
                    Tabs\Tab::make('🔄 Como Funciona')
                        ->schema([
                            TextInput::make('metodologia_titulo')->default('Nossa Metodologia'),
                            Textarea::make('metodologia_descricao')->rows(2),
                            
                            Repeater::make('metodologia_passos')
                                ->label('Passos do Processo')
                                ->schema([
                                    TextInput::make('numero')->label('Passo')->placeholder('1')->required(),
                                    TextInput::make('titulo')->placeholder('Inscrição')->required(),
                                    Textarea::make('descricao')->rows(2),
                                    Select::make('icone')->options([
                                        'fas fa-user-plus' => 'Cadastro',
                                        'fas fa-file-alt' => 'Documentos',
                                        'fas fa-credit-card' => 'Pagamento',
                                        'fas fa-play-circle' => 'Começar',
                                        'fas fa-graduation-cap' => 'Estudar',
                                        'fas fa-certificate' => 'Certificado',
                                    ]),
                                ])->columns(2)->defaultItems(4),
                        ]),

                    // ========== ABA: DEPOIMENTOS ==========
                    Tabs\Tab::make('💬 Depoimentos')
                        ->schema([
                            TextInput::make('depoimentos_titulo')->default('O Que Nossos Alunos Dizem'),
                            Textarea::make('depoimentos_descricao')->rows(2),
                            
                            Repeater::make('depoimentos_lista')
                                ->schema([
                                    FileUpload::make('foto')->label('Foto do Aluno')->image()->avatar()->directory('depoimentos'),
                                    TextInput::make('nome')->required(),
                                    TextInput::make('cargo')->placeholder('Ex: Pastor, Estudante...'),
                                    Textarea::make('depoimento')->rows(4)->required(),
                                    Select::make('estrelas')->label('Avaliação')->options([
                                        5 => '⭐⭐⭐⭐⭐',
                                        4 => '⭐⭐⭐⭐',
                                        3 => '⭐⭐⭐',
                                    ])->default(5),
                                ])->columns(2)->collapsible(),
                        ]),

                    // ========== ABA: GALERIA ==========
                    Tabs\Tab::make('📸 Galeria')
                        ->schema([
                            TextInput::make('galeria_titulo')->default('Nossa Galeria'),
                            
                            FileUpload::make('galeria_imagens')
                                ->label('Fotos')
                                ->image()
                                ->multiple()
                                ->directory('galeria')
                                ->maxFiles(20)
                                ->columnSpanFull(),

                            // CORREÇÃO 2: Removido ->simple()
                            Repeater::make('galeria_videos')
                                ->label('Vídeos (YouTube/Vimeo)')
                                ->schema([
                                    TextInput::make('titulo'),
                                    TextInput::make('url')->url()->required(),
                                ]),
                        ]),

                    // ========== ABA: PARCEIROS ==========
                    Tabs\Tab::make('🤝 Parceiros')
                        ->schema([
                            TextInput::make('parceiros_titulo')->default('Nossos Parceiros'),
                            
                            Repeater::make('parceiros_lista')
                                ->schema([
                                    FileUpload::make('logo')->image()->directory('parceiros'),
                                    TextInput::make('nome'),
                                    TextInput::make('link')->url(),
                                ])->columns(3),
                        ]),

                    // ========== ABA: VANTAGENS ==========
                    Tabs\Tab::make('⭐ Diferenciais')
                        ->schema([
                            TextInput::make('vantagens_titulo')->default('Por Que Escolher o CETSADP?'),
                            Textarea::make('vantagens_descricao')->rows(2),
                            
                            Repeater::make('vantagens_lista')
                                ->schema([
                                    TextInput::make('titulo')->required(),
                                    Textarea::make('descricao')->rows(2),
                                    TextInput::make('icone')->default('fas fa-star'),
                                ])->columns(2),
                        ]),

                    // ========== ABA: FAQ ==========
                    Tabs\Tab::make('❓ FAQ')
                        ->schema([
                            TextInput::make('faq_titulo')->default('Perguntas Frequentes'),
                            Textarea::make('faq_descricao')->rows(2),
                            
                            Repeater::make('faq_lista')
                                ->schema([
                                    TextInput::make('pergunta')->required()->columnSpanFull(),
                                    Textarea::make('resposta')->rows(3)->required()->columnSpanFull(),
                                ]),
                        ]),

                    // ========== ABA: NEWSLETTER ==========
                    Tabs\Tab::make('📧 Newsletter')
                        ->schema([
                            Toggle::make('newsletter_ativo')->label('Exibir seção de Newsletter')->default(true),
                            TextInput::make('newsletter_titulo')->default('Receba Novidades'),
                            Textarea::make('newsletter_descricao')->rows(2),
                        ]),

                    // ========== ABA: CONTATO ==========
                    Tabs\Tab::make('📞 Contato')
                        ->schema([
                            TextInput::make('contato_titulo')->default('Entre em Contato'),
                            Textarea::make('contato_descricao')->rows(2),
                            
                            Grid::make(2)->schema([
                                TextInput::make('telefone'),
                                TextInput::make('whatsapp'),
                            ]),
                            
                            Grid::make(2)->schema([
                                TextInput::make('email')->email(),
                                TextInput::make('horario_funcionamento')->placeholder('Seg-Sex: 8h-18h'),
                            ]),
                            
                            Textarea::make('endereco')->columnSpanFull(),
                            
                            Textarea::make('mapa_embedcode')
                                ->label('Código do Google Maps')
                                ->placeholder('<iframe src="https://maps.google.com/...">')
                                ->rows(3)
                                ->columnSpanFull(),

                            Section::make('Redes Sociais')->schema([
                                TextInput::make('link_facebook')->prefix('facebook.com/'),
                                TextInput::make('link_instagram')->prefix('instagram.com/'),
                                TextInput::make('link_youtube')->prefix('youtube.com/'),
                                TextInput::make('link_linkedin')->prefix('linkedin.com/'),
                                TextInput::make('link_twitter')->prefix('twitter.com/'),
                                TextInput::make('link_tiktok')->prefix('tiktok.com/@'),
                            ])->columns(3),
                        ]),

                    // ========== ABA: CTA FLUTUANTE ==========
                    Tabs\Tab::make('🎯 CTA Flutuante')
                        ->schema([
                            Toggle::make('cta_flutuante_ativo')->label('Ativar banner fixo no rodapé')->default(true),
                            TextInput::make('cta_flutuante_texto')->placeholder('Pronto para começar sua jornada?'),
                            TextInput::make('cta_flutuante_botao')->placeholder('Inscreva-se Grátis'),
                            TextInput::make('cta_flutuante_link')->default('/admin/register'),
                        ]),

                    // ========== ABA: SEO ==========
                    Tabs\Tab::make('🔍 SEO')
                        ->schema([
                            TextInput::make('seo_titulo')->label('Título SEO')->placeholder('CETSADP - Centro Educacional Teológico'),
                            Textarea::make('seo_descricao')->label('Meta Description')->rows(3),
                            TextInput::make('seo_palavras_chave')->label('Palavras-chave')->placeholder('teologia, cursos, educação'),
                            FileUpload::make('seo_imagem')->label('Imagem Open Graph')->image()->directory('seo'),
                        ]),

                    // ========== ABA: AVANÇADO ==========
                    Tabs\Tab::make('⚙️ Avançado')
                        ->schema([
                            Section::make('Identidade Visual')->schema([
                                FileUpload::make('logo')->image()->directory('branding'),
                                FileUpload::make('favicon')->image()->directory('branding'),
                            ])->columns(2),

                            Section::make('Cores Personalizadas')->schema([
                                Grid::make(3)->schema([
                                    ColorPicker::make('cores_personalizadas.primary')->label('Cor Primária')->default('#003D7A'),
                                    ColorPicker::make('cores_personalizadas.secondary')->label('Cor Secundária')->default('#D62828'),
                                    ColorPicker::make('cores_personalizadas.accent')->label('Cor de Destaque')->default('#F77F00'),
                                ]),
                            ]),

                            Section::make('Rastreamento')->schema([
                                Textarea::make('codigo_analytics')->label('Google Analytics')->rows(3)->placeholder(''),
                                Textarea::make('codigo_pixel')->label('Facebook Pixel')->rows(3),
                            ])->columns(2)->collapsible(),

                            Toggle::make('modo_manutencao')->label('Modo Manutenção (Site Offline)')->helperText('Visitantes verão uma página de "Em Breve"'),
                        ]),

                ]), // Fim das Tabs
            ]), // Fim do Group Formulário

            // === PREVIEW LATERAL ===
            Group::make()->columnSpan(5)->schema([
                View::make('filament.components.site-preview'),
            ]),

        ])->columns(12);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo_identificacao')
                    ->label('Versão')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\ToggleColumn::make('ativa')
                    ->label('Ativo')
                    ->onColor('success')
                    ->offColor('danger'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Edição')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->label('Duplicar')
                    ->excludeAttributes(['ativa'])
                    ->successNotificationTitle('Tema duplicado com sucesso!'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaginaInicials::route('/'),
            'create' => Pages\CreatePaginaInicial::route('/create'),
            'edit' => Pages\EditPaginaInicial::route('/{record}/edit'),
        ];
    }
}