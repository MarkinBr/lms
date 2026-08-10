<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mensalidades', function (Blueprint $t) {
            try {
                $t->dropForeign(['matricula_id']);
            } catch (\Throwable $e) {}
        });

        Schema::table('mensalidades', function (Blueprint $t) {
            $t->foreign('matricula_id')
              ->references('id')
              ->on('matriculas')
              ->restrictOnDelete()
              ->cascadeOnUpdate();
        });

        Schema::table('mensalidades', function (Blueprint $t) {
            if (!Schema::hasColumn('mensalidades', 'deleted_at')) {
                $t->softDeletes()->index();
            }
        });

        Schema::table('mensalidades', function (Blueprint $t) {
            if (!Schema::hasColumn('mensalidades', 'codigo_validacao')) {
                $t->uuid('codigo_validacao')->nullable()->after('id');
            }
        });

        DB::table('mensalidades')
            ->whereNull('codigo_validacao')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('mensalidades')
                        ->where('id', $row->id)
                        ->update(['codigo_validacao' => (string) Str::uuid()]);
                }
            });

        Schema::table('mensalidades', function (Blueprint $t) {
            $t->unique('codigo_validacao', 'uq_mensalidades_codigo_validacao');
        });

        Schema::table('mensalidades', function (Blueprint $t) {
            if (!Schema::hasColumn('mensalidades', 'valor_pago')) {
                $t->decimal('valor_pago', 10, 2)->nullable()->after('valor');
            }
        });

        DB::table('mensalidades')
            ->where('status', 'Pago')
            ->whereNull('valor_pago')
            ->update(['valor_pago' => DB::raw('valor')]);

        Schema::table('mensalidades', function (Blueprint $t) {
            if (!Schema::hasColumn('mensalidades', 'pagamento_tentativa')) {
                $t->unsignedSmallInteger('pagamento_tentativa')
                  ->default(1)
                  ->after('transacao_id');
            }
            if (!Schema::hasColumn('mensalidades', 'pix_expira_em')) {
                $t->timestamp('pix_expira_em')
                  ->nullable()
                  ->after('pix_qr_base64');
            }
        });

        $duplicadas = DB::select("
            SELECT matricula_id, data_vencimento, COUNT(*) AS qtd, MIN(id) AS keep_id
              FROM mensalidades
             WHERE deleted_at IS NULL
          GROUP BY matricula_id, data_vencimento
            HAVING COUNT(*) > 1
        ");

        foreach ($duplicadas as $dup) {
            DB::table('mensalidades')
                ->where('matricula_id', $dup->matricula_id)
                ->whereDate('data_vencimento', $dup->data_vencimento)
                ->where('id', '!=', $dup->keep_id)
                ->update([
                    'deleted_at' => now(),
                    'observacao' => DB::raw("COALESCE(observacao, '') || ' [v1.1] Duplicata removida'"),
                ]);
        }

        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS uq_mensalidades_matricula_venc_active
                ON mensalidades (matricula_id, data_vencimento)
                WHERE deleted_at IS NULL
        ');

        Schema::table('mensalidades', function (Blueprint $t) {
            $this->addIndexIfMissing($t, 'mensalidades', ['status'], 'idx_mensalidades_status');
            $this->addIndexIfMissing($t, 'mensalidades', ['data_vencimento'], 'idx_mensalidades_venc');
            $this->addIndexIfMissing($t, 'mensalidades', ['data_pagamento'], 'idx_mensalidades_pago');
            $this->addIndexIfMissing($t, 'mensalidades', ['status', 'data_vencimento'], 'idx_mensalidades_status_venc');
            $this->addIndexIfMissing($t, 'mensalidades', ['matricula_id', 'status'], 'idx_mensalidades_matricula_status');
        });

        if (Schema::hasTable('movimentacoes_financeiras')) {
            Schema::table('movimentacoes_financeiras', function (Blueprint $t) {
                $this->addIndexIfMissing($t, 'movimentacoes_financeiras', ['data_movimentacao', 'tipo'], 'idx_movf_data_tipo');
                $this->addIndexIfMissing($t, 'movimentacoes_financeiras', ['mensalidade_id'], 'idx_movf_mensalidade');
                $this->addIndexIfMissing($t, 'movimentacoes_financeiras', ['status'], 'idx_movf_status');
            });

            DB::statement("
                CREATE UNIQUE INDEX IF NOT EXISTS uq_movf_unique_per_mensalidade
                    ON movimentacoes_financeiras (mensalidade_id)
                    WHERE tipo = 'entrada' AND status = 'efetivado' AND mensalidade_id IS NOT NULL
            ");
        }

        if (Schema::hasTable('contas') && !Schema::hasColumn('contas', 'data_saldo_inicial')) {
            Schema::table('contas', function (Blueprint $t) {
                $t->date('data_saldo_inicial')->nullable()->after('saldo_inicial');
            });
            
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("
                    UPDATE contas
                       SET data_saldo_inicial = COALESCE(created_at::date, CURRENT_DATE)
                     WHERE data_saldo_inicial IS NULL
                ");
            } else {
                DB::statement("
                    UPDATE contas
                       SET data_saldo_inicial = COALESCE(date(created_at), date('now'))
                     WHERE data_saldo_inicial IS NULL
                ");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('movimentacoes_financeiras')) {
            DB::statement('DROP INDEX IF EXISTS uq_movf_unique_per_mensalidade');
            Schema::table('movimentacoes_financeiras', function (Blueprint $t) {
                $this->dropIndexIfExists($t, 'idx_movf_data_tipo');
                $this->dropIndexIfExists($t, 'idx_movf_mensalidade');
                $this->dropIndexIfExists($t, 'idx_movf_status');
            });
        }

        DB::statement('DROP INDEX IF EXISTS uq_mensalidades_matricula_venc_active');

        Schema::table('mensalidades', function (Blueprint $t) {
            $this->dropIndexIfExists($t, 'idx_mensalidades_status');
            $this->dropIndexIfExists($t, 'idx_mensalidades_venc');
            $this->dropIndexIfExists($t, 'idx_mensalidades_pago');
            $this->dropIndexIfExists($t, 'idx_mensalidades_status_venc');
            $this->dropIndexIfExists($t, 'idx_mensalidades_matricula_status');

            try { $t->dropUnique('uq_mensalidades_codigo_validacao'); } catch (\Throwable $e) {}

            $t->dropColumn(['pagamento_tentativa', 'pix_expira_em', 'valor_pago', 'codigo_validacao']);
            $t->dropSoftDeletes();

            try { $t->dropForeign(['matricula_id']); } catch (\Throwable $e) {}
        });

        Schema::table('mensalidades', function (Blueprint $t) {
            $t->foreign('matricula_id')
              ->references('id')
              ->on('matriculas')
              ->onDelete('cascade');
        });

        if (Schema::hasTable('contas') && Schema::hasColumn('contas', 'data_saldo_inicial')) {
            Schema::table('contas', function (Blueprint $t) {
                $t->dropColumn('data_saldo_inicial');
            });
        }
    }

    private function addIndexIfMissing(Blueprint $t, string $table, array $columns, string $indexName): void
    {
        $existing = [];
        
        if (DB::getDriverName() === 'pgsql') {
            $existing = collect(DB::select("SELECT indexname FROM pg_indexes WHERE tablename = ?", [$table]))->pluck('indexname')->toArray();
        } elseif (DB::getDriverName() === 'sqlite') {
            $existing = collect(DB::select("SELECT name as indexname FROM sqlite_master WHERE type = 'index' AND tbl_name = ?", [$table]))->pluck('indexname')->toArray();
        }

        if (!in_array($indexName, $existing, true)) {
            $t->index($columns, $indexName);
        }
    }

    private function dropIndexIfExists(Blueprint $t, string $indexName): void
    {
        try {
            $t->dropIndex($indexName);
        } catch (\Throwable $e) {}
    }
};