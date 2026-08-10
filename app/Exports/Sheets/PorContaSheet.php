<?php

namespace App\Exports\Sheets;

use App\Models\Conta;
use App\Models\MovimentacaoFinanceira;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PorContaSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
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
        return 'Por Conta';
    }

    public function headings(): array
    {
        return ['Conta', 'Tipo', 'Saldo Inicial Ajustado', 'Entradas Período', 'Saídas Período', 'Saldo Final', '# Mov.'];
    }

    public function array(): array
    {
        $tz = config('app.timezone', 'America/Sao_Paulo');
        $contas = Conta::all();
        $out = [];

        foreach ($contas as $conta) {
            // BUG-A06: saldo_inicial só é válido a partir de data_saldo_inicial
            $dataBase = $conta->data_saldo_inicial
                ? Carbon::parse($conta->data_saldo_inicial, $tz)
                : Carbon::parse($this->inicio, $tz);

            // Acumula movimentações entre data_saldo_inicial e o início do período
            $entreBaseInicio = MovimentacaoFinanceira::query()
                ->where('conta_id', $conta->id)
                ->where('status', 'efetivado')
                ->whereBetween('data_movimentacao', [$dataBase, $this->inicio]);

            $entAnt = (float) (clone $entreBaseInicio)->where('tipo', 'entrada')->sum('valor');
            $saiAnt = (float) (clone $entreBaseInicio)->where('tipo', 'saida')->sum('valor');
            $saldoAjustado = (float) ($conta->saldo_inicial ?? 0) + $entAnt - $saiAnt;

            $base = MovimentacaoFinanceira::query()
                ->where('conta_id', $conta->id)
                ->where('status', 'efetivado')
                ->whereBetween('data_movimentacao', [$this->inicio, $this->fim]);

            $ent = (float) (clone $base)->where('tipo', 'entrada')->sum('valor');
            $sai = (float) (clone $base)->where('tipo', 'saida')->sum('valor');
            $qtd = (int) (clone $base)->count();
            $saldoFinal = $saldoAjustado + $ent - $sai;

            $out[] = [
                $conta->nome,
                $conta->tipo,
                number_format($saldoAjustado, 2, ',', '.'),
                number_format($ent, 2, ',', '.'),
                number_format($sai, 2, ',', '.'),
                number_format($saldoFinal, 2, ',', '.'),
                $qtd,
            ];
        }

        return $out;
    }
}