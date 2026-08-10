<?php

namespace App\Exports\Sheets;

use App\Models\Mensalidade;
use App\Models\MovimentacaoFinanceira;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResumoSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public string $inicio;
    public string $fim;
    public ?int $contaId;
    public ?int $categoriaId;

    public function __construct(string $inicio, string $fim, ?int $contaId = null, ?int $categoriaId = null)
    {
        $this->inicio = $inicio;
        $this->fim = $fim;
        $this->contaId = $contaId;
        $this->categoriaId = $categoriaId;
    }

    public function title(): string
    {
        return 'Resumo';
    }

    public function headings(): array
    {
        return ['Métrica', 'Valor (R$)', 'Quantidade'];
    }

    public function array(): array
    {
        $base = MovimentacaoFinanceira::query()
            ->whereBetween('data_movimentacao', [$this->inicio, $this->fim])
            ->where('status', 'efetivado');

        if ($this->contaId)     { $base->where('conta_id', $this->contaId); }
        if ($this->categoriaId) { $base->where('categoria_id', $this->categoriaId); }

        $entradas = (clone $base)->where('tipo', 'entrada');
        $saidas   = (clone $base)->where('tipo', 'saida');

        $totalEntradas = (float) (clone $entradas)->sum('valor');
        $totalSaidas   = (float) (clone $saidas)->sum('valor');
        $saldo         = $totalEntradas - $totalSaidas;

        $qtdMensPagas = Mensalidade::query()
            ->where('status', 'Pago')
            ->whereBetween('data_pagamento', [$this->inicio, $this->fim])
            ->count();

        $qtdMensAbertas   = Mensalidade::query()->where('status', 'Pendente')->count();
        $qtdMensAtrasadas = Mensalidade::query()->where('status', 'Atrasado')->count();

        return [
            ['💰 Total Entradas',            number_format($totalEntradas, 2, ',', '.'), (clone $entradas)->count()],
            ['💸 Total Saídas',              number_format($totalSaidas, 2, ',', '.'),   (clone $saidas)->count()],
            ['📊 Saldo do Período',          number_format($saldo, 2, ',', '.'),         '—'],
            ['', '', ''],
            ['✅ Mensalidades Pagas (período)',  '—', $qtdMensPagas],
            ['⏳ Mensalidades Abertas (total)',   '—', $qtdMensAbertas],
            ['🔴 Mensalidades Atrasadas (total)', '—', $qtdMensAtrasadas],
            ['', '', ''],
            ['Período Início', $this->inicio, ''],
            ['Período Fim',    $this->fim,    ''],
            ['Gerado em',      now(config('app.timezone', 'America/Sao_Paulo'))->format('d/m/Y H:i'), ''],
            ['Gerado por',     auth()->user()->name ?? 'Sistema', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('003D7A');
        $sheet->getStyle('A1:C1')->getFont()->getColor()->setRGB('FFFFFF');

        return [];
    }
}