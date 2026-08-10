<?php

namespace App\Exports\Sheets;

use App\Models\Mensalidade;
use App\Models\MovimentacaoFinanceira;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PorMesSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
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
        return 'Por Mês';
    }

    public function headings(): array
    {
        return ['Mês/Ano', 'Entradas (R$)', 'Saídas (R$)', 'Saldo (R$)', 'Acumulado (R$)'];
    }

    public function array(): array
    {
        $tz = config('app.timezone', 'America/Sao_Paulo');
        $inicio = Carbon::parse($this->inicio, $tz)->startOfMonth();
        $fim    = Carbon::parse($this->fim, $tz)->endOfMonth();
        $period = CarbonPeriod::create($inicio, '1 month', $fim);

        $acumulado = 0.0;
        $out = [];

        foreach ($period as $mes) {
            $base = MovimentacaoFinanceira::query()
                ->whereYear('data_movimentacao', $mes->year)
                ->whereMonth('data_movimentacao', $mes->month)
                ->where('status', 'efetivado');

            $ent = (float) (clone $base)->where('tipo', 'entrada')->sum('valor');
            $sai = (float) (clone $base)->where('tipo', 'saida')->sum('valor');
            $saldo = $ent - $sai;
            $acumulado += $saldo;

            $out[] = [
                $mes->locale('pt-BR')->isoFormat('MMMM/YYYY'),
                number_format($ent, 2, ',', '.'),
                number_format($sai, 2, ',', '.'),
                number_format($saldo, 2, ',', '.'),
                number_format($acumulado, 2, ',', '.'),
            ];
        }

        return $out;
    }
}

class MensalidadesSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
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
        return 'Mensalidades';
    }

    public function query()
    {
        return Mensalidade::query()
            ->with(['matricula.user', 'matricula.curso'])
            ->where(function ($q) {
                $q->whereBetween('data_vencimento', [$this->inicio, $this->fim])
                  ->orWhereBetween('data_pagamento', [$this->inicio, $this->fim]);
            })
            ->orderBy('data_vencimento');
    }

    public function headings(): array
    {
        return ['Matrícula', 'Aluno', 'Curso', 'Vencimento', 'Valor (R$)', 'Status',
                'Data Pagto', 'Valor Pago (R$)', 'Método', 'Dias Atraso'];
    }

    public function map($m): array
    {
        return [
            $m->matricula?->numero_matricula ?? '—',
            $m->matricula?->user?->name ?? '—',
            $m->matricula?->curso?->titulo ?? '—',
            optional($m->data_vencimento)->format('d/m/Y') ?? '—',
            number_format((float) $m->valor, 2, ',', '.'),
            (string) $m->status,
            optional($m->data_pagamento)->format('d/m/Y') ?? '—',
            $m->valor_pago ? number_format((float) $m->valor_pago, 2, ',', '.') : '—',
            $m->metodo_pagamento ?? '—',
            (int) ($m->dias_atraso ?? 0),
        ];
    }
}