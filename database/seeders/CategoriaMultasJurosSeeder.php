<?php

namespace Database\Seeders;

use App\Models\CategoriaFinanceira;
use Illuminate\Database\Seeder;

class CategoriaMultasJurosSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nome' => 'Mensalidades',     'tipo' => 'Receita',  'ativa' => true],
            ['nome' => 'Multas e Juros',   'tipo' => 'Receita',  'ativa' => true],
            ['nome' => 'Taxas Avulsas',    'tipo' => 'Receita',  'ativa' => true],
            ['nome' => 'Matrículas',       'tipo' => 'Receita',  'ativa' => true],
        ];

        foreach ($categorias as $cat) {
            CategoriaFinanceira::firstOrCreate(
                ['nome' => $cat['nome']],
                ['tipo' => $cat['tipo'], 'ativa' => $cat['ativa']]
            );
        }
    }
}