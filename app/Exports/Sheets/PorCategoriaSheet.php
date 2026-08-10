<?php

namespace App\Exports\Sheets;

use App\Models\MovimentacaoFinanceira;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PorCategoriaSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public string $inicio;
    public string $fim;

    public function __construct(string $inicio, string $fim)
    {
        $this->inicio = $inicio;
        $this->fim = $fim;
    }

    public function title(): string
    {
        return 'Por Categoria';
    }

    public function headings(): array
    {
        return ['Categoria', 'Tipo', 'Entradas (R$)', 'Saídas (R$)', 'Saldo (R$)', '# Mov.'];
    }

    public function array(): array
    {
        $rows = MovimentacaoFinanceira::query()
            ->with('categoria')
            ->whereBetween('data_movimentacao', [$this->inicio, $this->fim])
            ->where('status', 'efetivado')
            ->select(
                'categoria_id',
                DB::raw("SUM(CASE WHEN tipo='entrada' THEN valor ELSE 0 END) as entradas"),
                DB::raw("SUM(CASE WHEN tipo='saida'   THEN valor ELSE 0 END) as saidas"),
                DB::raw('COUNT(*) as qtd')
            )
            ->groupBy('categoria_id')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $ent = (float) $r->entradas;
            $sai = (float) $r->saidas;
            $out[] = [
                $r->categoria?->nome ?? '(Sem categoria)',
                $r->categoria?->tipo ?? '—',
                number_format($ent, 2, ',', '.'),
                number_format($sai, 2, ',', '.'),
                number_format($ent - $sai, 2, ',', '.'),
                (int) $r->qtd,
            ];
        }

        return $out;
    }
}