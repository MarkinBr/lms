<?php

namespace App\Exports\Sheets;

use App\Models\MovimentacaoFinanceira;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class DetalhadoSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
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
        return 'Detalhado';
    }

    public function query()
    {
        return MovimentacaoFinanceira::query()
            ->with(['conta', 'categoria', 'criadoPor'])
            ->whereBetween('data_movimentacao', [$this->inicio, $this->fim])
            ->where('status', 'efetivado')
            ->when($this->contaId,     fn ($q) => $q->where('conta_id', $this->contaId))
            ->when($this->categoriaId, fn ($q) => $q->where('categoria_id', $this->categoriaId))
            ->orderBy('data_movimentacao');
    }

    public function headings(): array
    {
        return ['Data', 'Tipo', 'Descrição', 'Categoria', 'Conta', 'Valor (R$)', 'Pagador/Beneficiário', 'Criado Por'];
    }

    public function map($mov): array
    {
        return [
            optional($mov->data_movimentacao)->format('d/m/Y') ?? '—',
            $mov->tipo === 'entrada' ? '⬇️ Entrada' : '⬆️ Saída',
            (string) $mov->descricao,
            $mov->categoria?->nome ?? '—',
            $mov->conta?->nome ?? '—',
            number_format((float) $mov->valor, 2, ',', '.'),
            $mov->tipo === 'entrada' ? ($mov->pagador ?? '—') : ($mov->beneficiario ?? '—'),
            $mov->criadoPor?->name ?? 'Sistema',
        ];
    }
}