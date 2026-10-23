<?php
namespace App\Filament\Secretario\Resources;
use App\Filament\Secretario\Resources\EventoPublicoResource\Pages;
use App\Models\EventoPublico;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
class EventoPublicoResource extends Resource {
 protected static ?string $model=EventoPublico::class;
 protected static ?string $navigationLabel='Eventos do Site';
 protected static ?string $navigationGroup='Acadêmico';
 protected static ?string $navigationIcon='heroicon-o-megaphone';
 protected static ?int $navigationSort=4;
 public static function canAccess(): bool {return auth()->check() && auth()->user()->ativo && in_array(auth()->user()->nivel,['admin','secretario'],true);}
 public static function form(Form $form): Form {return $form->schema([
  Forms\Components\Section::make('Programação pública')->schema([
   Forms\Components\TextInput::make('titulo')->required()->maxLength(255)->columnSpanFull(),
   Forms\Components\Textarea::make('descricao')->rows(3)->columnSpanFull(),
   Forms\Components\Select::make('categoria')->options(EventoPublico::CATEGORIAS)->required(),
   Forms\Components\TextInput::make('local')->maxLength(255),
   Forms\Components\DateTimePicker::make('data_inicio')->required()->seconds(false),
   Forms\Components\DateTimePicker::make('data_fim')->afterOrEqual('data_inicio')->seconds(false),
   Forms\Components\TextInput::make('vagas')->numeric()->integer()->minValue(1),
   Forms\Components\TextInput::make('imagem_url')->label('URL HTTPS da imagem')->url()->regex('/^https:\/\//i')->maxLength(2048)->columnSpanFull(),
   Forms\Components\TextInput::make('inscricao_url')->label('URL HTTPS para inscrições')->url()->regex('/^https:\/\//i')->maxLength(2048)->columnSpanFull(),
   Forms\Components\Toggle::make('publicado')->label('Publicar no site')->helperText('Somente eventos publicados e futuros aparecem para visitantes.')->default(false)
  ])->columns(2)
 ]);}
 public static function table(Table $table): Table {return $table->columns([
  Tables\Columns\TextColumn::make('titulo')->searchable()->sortable(),
  Tables\Columns\TextColumn::make('categoria')->formatStateUsing(fn ($state)=>EventoPublico::CATEGORIAS[$state]??$state),
  Tables\Columns\TextColumn::make('data_inicio')->dateTime('d/m/Y H:i')->sortable(),
  Tables\Columns\TextColumn::make('local')->limit(35),
  Tables\Columns\IconColumn::make('publicado')->boolean()->label('Publicado')
 ])->filters([Tables\Filters\TernaryFilter::make('publicado')])->actions([Tables\Actions\EditAction::make(),Tables\Actions\DeleteAction::make()])->defaultSort('data_inicio');}
 public static function getPages(): array {return ['index'=>Pages\ListEventosPublicos::route('/'),'create'=>Pages\CreateEventoPublico::route('/create'),'edit'=>Pages\EditEventoPublico::route('/{record}/edit')];}
}
