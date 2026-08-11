<?php

namespace Database\Seeders;

use App\Models\CourseModality;
use App\Models\Curso;
use Illuminate\Database\Seeder;

class CourseCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalogo = [
            'Curso Médio em Teologia' => ['Presencial', 'Híbrido', 'EAD'],
            'Habilitação ao Diaconato' => ['Presencial'],
            'Liderança Cristã Ministerial' => ['Presencial'],
        ];

        foreach ($catalogo as $titulo => $modalidades) {
            $curso = Curso::query()->firstOrCreate(
                ['titulo' => $titulo],
                ['ativo' => true],
            );

            foreach ($modalidades as $nome) {
                CourseModality::query()->firstOrCreate(
                    ['curso_id' => $curso->id, 'nome' => $nome],
                    ['ativo' => true],
                );
            }
        }
    }
}
