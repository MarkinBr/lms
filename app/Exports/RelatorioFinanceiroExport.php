<?php

namespace App\Exports;

use App\Exports\Sheets\DetalhadoSheet;
use App\Exports\Sheets\MensalidadesSheet;
use App\Exports\Sheets\PorCategoriaSheet;
use App\Exports\Sheets\PorContaSheet;
use App\Exports\Sheets\PorMesSheet;
use App\Exports\Sheets\ResumoSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RelatorioFinanceiroExport implements WithMultipleSheets
{
    public string $dataInicio;
    public string $dataFim;
    public ?int $contaId;
    public ?int $categoriaId;

    public function __construct(
        string $dataInicio,
        string $dataFim,
        ?int $contaId = null,
        ?int $categoriaId = null
    ) {
        $this->dataInicio = $dataInicio;
        $this->dataFim = $dataFim;
        $this->contaId = $contaId;
        $this->categoriaId = $categoriaId;
    }

    public function sheets(): array
    {
        return [
            'Resumo'        => new ResumoSheet($this->dataInicio, $this->dataFim, $this->contaId, $this->categoriaId),
            'Detalhado'     => new DetalhadoSheet($this->dataInicio, $this->dataFim, $this->contaId, $this->categoriaId),
            'Por Categoria' => new PorCategoriaSheet($this->dataInicio, $this->dataFim),
            'Por Conta'     => new PorContaSheet($this->dataInicio, $this->dataFim),
            'Por Mês'       => new PorMesSheet($this->dataInicio, $this->dataFim),
            'Mensalidades'  => new MensalidadesSheet($this->dataInicio, $this->dataFim),
        ];
    }
}