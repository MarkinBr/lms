<?php
namespace Tests\Feature\Site;
use App\Models\EventoPublico;
use App\Models\User;
use App\Filament\Secretario\Resources\EventoPublicoResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class EventosPublicosTest extends TestCase {
 use RefreshDatabase;
 public function test_apenas_eventos_publicados_e_futuros_sao_visiveis(): void {
  EventoPublico::create(['titulo'=>'Evento público real','categoria'=>'seminario','data_inicio'=>now()->addDays(7),'publicado'=>true]);
  EventoPublico::create(['titulo'=>'Evento rascunho','categoria'=>'seminario','data_inicio'=>now()->addDays(8),'publicado'=>false]);
  EventoPublico::create(['titulo'=>'Evento encerrado','categoria'=>'seminario','data_inicio'=>now()->subDay(),'publicado'=>true]);
  $response=$this->get('/');$response->assertOk()->assertSee('Evento público real')->assertDontSee('Evento rascunho')->assertDontSee('Evento encerrado');
 }
 public function test_apenas_secretaria_ou_admin_ativos_gerenciam_eventos(): void {
  foreach(['aluno'=>false,'professor'=>false,'secretario'=>true,'admin'=>true] as $nivel=>$permissao){
   $user=User::create(['name'=>'Pessoa Teste','email'=>uniqid().$nivel.'@example.test','password'=>'segredo','nivel'=>$nivel,'ativo'=>true]);
   $this->actingAs($user);$this->assertSame($permissao,EventoPublicoResource::canAccess());
  }
  auth()->logout();$this->assertFalse(EventoPublicoResource::canAccess());
 }
}
