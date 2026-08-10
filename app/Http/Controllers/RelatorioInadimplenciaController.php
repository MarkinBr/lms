<?php

namespace App\Http\Controllers;

use App\Helpers\InstituicaoHelper;
use App\Models\Mensalidade;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RelatorioInadimplenciaController extends Controller
{
    /**
     * REL-06: Relatório de Inadimplência por aging.
     * Buckets: 0-30, 31-60, 61-90, 91+ dias.
     */
    public function aging(Request $request)
    {
        // BUG-A04: validação rigorosa
        $data = $request->validate([
            'curso_id' => 'sometimes|nullable|integer|exists:cursos,id',
            'turma_id' => 'sometimes|nullable|integer|exists:turmas,id',
            'formato'  => 'sometimes|in:pdf,html',
        ]);

        $cacheKey = 'rel:aging:' . md5(json_encode($data));

        $payload = Cache::remember($cacheKey, now()->addHour(), function () use ($data) {
            return $this->montarPayloadAging($data);
        });

        // REL-16: registrar auditoria
        $this->registrarAuditoria('inadimplencia_aging', $data, $payload['totais']['qtd_total']);

        if (($data['formato'] ?? 'pdf') === 'html') {
            return view('pdf.aging-inadimplencia', $payload);
        }

        $pdf = Pdf::loadView('pdf.aging-inadimplencia', $payload)
            ->setPaper('a4', 'landscape');

        return $pdf->stream('relatorio-inadimplencia-aging-' . now()->format('Y-m-d') . '.pdf');
    }

    private function montarPayloadAging(array $filtros): array
    {
        $buckets = [
            '0_30'  => ['label' => '0–30 dias',  'min' => 0,  'max' => 30],
            '31_60' => ['label' => '31–60 dias', 'min' => 31, 'max' => 60],
            '61_90' => ['label' => '61–90 dias', 'min' => 61, 'max' => 90],
            '91+'   => ['label' => '91+ dias',   'min' => 91, 'max' => 99999],
        ];

        $resultado    = [];
        $totalGeral   = 0;
        $qtdTotal     = 0;

        foreach ($buckets as $key => $b) {
            $query = Mensalidade::query()
                ->whereIn('status', ['Atrasado', 'Pendente'])
                ->whereDate('data_vencimento', '<=', now()->subDays($b['min']))
                ->whereDate('data_vencimento', '>=', now()->subDays($b['max']))
                ->with(['matricula.user', 'matricula.curso']);

            if (!empty($filtros['curso_id'])) {
                $query->whereHas('matricula', fn ($q) => $q->where('curso_id', $filtros['curso_id']));
            }
            if (!empty($filtros['turma_id'])) {
                $query->whereHas('matricula', fn ($q) => $q->where('turma_id', $filtros['turma_id']));
            }

            $itens = $query->get();
            $valor = (float) $itens->sum(fn ($m) => $m->calcularValorComMulta());

            $resultado[$key] = [
                'label'        => $b['label'],
                'qtd'          => $itens->count(),
                'valor_total'  => $valor,
                'agrupado_por_aluno' => $itens->groupBy('matricula.user.name')
                    ->map(fn ($grp) => [
                        'aluno'  => $grp->first()->matricula?->user?->name ?? 'N/A',
                        'curso'  => $grp->first()->matricula?->curso?->titulo ?? 'N/A',
                        'qtd'    => $grp->count(),
                        'valor'  => (float) $grp->sum(fn ($m) => $m->calcularValorComMulta()),
                        'mais_antiga' => $grp->min('data_vencimento'),
                    ])
                    ->sortByDesc('valor')
                    ->values(),
            ];

            $totalGeral += $valor;
            $qtdTotal   += $itens->count();
        }

        return [
            'buckets'      => $resultado,
            'instituicao'  => InstituicaoHelper::class,
            'totais'       => [
                'valor_total' => $totalGeral,
                'qtd_total'   => $qtdTotal,
            ],
            'gerado_em'    => now(),
        ];
    }

    private function registrarAuditoria(string $tipo, array $params, int $registros): void
    {
        try {
            DB::table('relatorios_gerados')->insert([
                'user_id'    => auth()->id(),
                'tipo'       => $tipo,
                'formato'    => 'pdf',
                'parametros' => json_encode($params),
                'registros_processados' => $registros,
                'ip'         => request()->ip(),
                'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Não bloqueia geração do relatório se a tabela ainda não existir
            \Log::warning('Falha ao registrar auditoria de relatório', ['error' => $e->getMessage()]);
        }
    }
}