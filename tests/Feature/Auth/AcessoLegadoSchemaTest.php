<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class AcessoLegadoSchemaTest extends PortalAuthTestCase
{
    use RefreshDatabase;

    public function test_matricula_e_email_opcional_preservam_id_e_hash_do_importador(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'matricula_login'));
        $id = \DB::table('users')->insertGetId([
            'name' => 'Aluno sintético', 'email' => null, 'matricula_login' => 'leg-001',
            'password' => '$2b$04$abcdefghijklmnopqrstuuGxpf2Nndb2pk8/eTdDAK5sIlvVxRLr2',
            'nivel' => 'aluno', 'ativo' => true,
        ]);
        $aluno = User::findOrFail($id);
        $this->assertNull($aluno->email);
        $this->assertSame('$2b$04$abcdefghijklmnopqrstuuGxpf2Nndb2pk8/eTdDAK5sIlvVxRLr2', $aluno->password);
        $this->assertSame($id, $aluno->id);
    }

    public function test_matricula_e_normalizada_e_unica(): void
    {
        $aluno = User::factory()->create(['matricula_login' => '  MAT-ABC  ']);
        $this->assertSame('mat-abc', $aluno->fresh()->matricula_login);
        $this->expectException(QueryException::class);
        User::factory()->create(['matricula_login' => 'mat-abc']);
    }
}
