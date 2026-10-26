<?php

namespace App\Services\Telegram;

use App\Models\Application;
use App\Models\Aula;
use App\Models\Aviso;
use App\Models\ChamadaFrequencia;
use App\Models\Disciplina;
use App\Models\EventoCalendario;
use App\Models\Frequencia;
use App\Models\Matricula;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ConfirmacoesTelegram
{
    public function prepararAula(string $chatId, string $conteudo, int $updateId): array
    {
        $partes = array_map('trim', explode('|', $conteudo));
        if (count($partes) !== 4 || ! ctype_digit($partes[0]) || (int) $partes[0] < 1
            || mb_strlen($partes[1]) < 1 || mb_strlen($partes[1]) > 200
            || preg_match('/[\x00-\x1F\x7F]/u', $partes[1]) === 1
            || ! $this->urlMaterialValida($partes[2]) || ! $this->urlMaterialValida($partes[3])
            || ($partes[2] === '-' && $partes[3] === '-')) {
            return ['text' => 'Uso: /novaaula DISCIPLINA_ID | TÍTULO | URL_VIDEO | URL_PDF. Use - para material ausente; URLs devem usar HTTPS.'];
        }
        $disciplina = Disciplina::with('modulo')->find((int) $partes[0]);
        if (! $disciplina || ! $disciplina->modulo
            || (int) $disciplina->modulo->curso_id !== (int) $disciplina->curso_id) {
            return ['text' => 'Disciplina inexistente ou sem módulo válido.'];
        }

        return $this->prepararOperacao($chatId, $updateId, 'criar_aula', [
            'disciplina_id' => $disciplina->id, 'titulo' => $partes[1],
            'video_url' => $partes[2] === '-' ? null : $partes[2],
            'pdf_url' => $partes[3] === '-' ? null : $partes[3],
        ], "Nova aula (não liberada):\nDisciplina #{$disciplina->id} — {$disciplina->nome}\nTítulo: {$partes[1]}\nVídeo: {$partes[2]}\nPDF: {$partes[3]}", 'Criar rascunho');
    }

    public function prepararLiberacaoAula(string $chatId, string $conteudo, int $updateId): array
    {
        if (! preg_match('/^[1-9][0-9]*$/D', trim($conteudo))) {
            return ['text' => 'Uso: /liberaraula AULA_ID.'];
        }
        $aula = Aula::find((int) trim($conteudo));
        if (! $aula || $aula->liberada || ! $aula->disciplina_id) {
            return ['text' => 'Aula indisponível, já liberada ou sem disciplina vinculada.'];
        }

        return $this->prepararOperacao($chatId, $updateId, 'liberar_aula', [
            'aula_id' => $aula->id, 'estado_hash' => $this->estadoRegistroHash($aula),
        ], "Liberar a aula #{$aula->id} — {$aula->titulo} para os alunos?", 'Liberar aula');
    }

    private function estadoRegistroHash(Model $registro): string
    {
        $atributos = $registro->getAttributes();
        ksort($atributos);

        return hash('sha256', json_encode($atributos, JSON_THROW_ON_ERROR));
    }

    private function estadoChamadaHash(ChamadaFrequencia $chamada): string
    {
        return hash('sha256', json_encode([
            'chamada' => $this->estadoRegistroHash($chamada),
            'ativos' => $chamada->alunosAtivos()->pluck('id')->sort()->values()->all(),
            'frequencias' => $chamada->frequencias()->orderBy('id')
                ->get(['id', 'matricula_id', 'situacao', 'observacao', 'registrado_por'])->toArray(),
        ], JSON_THROW_ON_ERROR));
    }

    public function prepararConclusaoChamada(string $chatId, string $conteudo, int $updateId): array
    {
        if (! preg_match('/^[1-9][0-9]*$/D', trim($conteudo))) {
            return ['text' => 'Uso: /concluirchamada CHAMADA_ID.'];
        }
        $chamada = ChamadaFrequencia::find((int) trim($conteudo));
        if (! $chamada || $chamada->status !== ChamadaFrequencia::STATUS_RASCUNHO
            || ! $this->chamadaCompleta($chamada)) {
            return ['text' => 'Chamada indisponível ou com frequência incompleta. Registre todos os alunos ativos antes de concluir.'];
        }

        return $this->prepararOperacao($chatId, $updateId, 'concluir_chamada', [
            'chamada_id' => $chamada->id, 'estado_hash' => $this->estadoChamadaHash($chamada),
        ], "Concluir chamada #{$chamada->id} de {$chamada->data_aula->format('d/m/Y')}? A frequência será consolidada.", 'Concluir chamada');
    }

    private function urlMaterialValida(string $valor): bool
    {
        if ($valor === '-') {
            return true;
        }

        return strlen($valor) <= 255 && filter_var($valor, FILTER_VALIDATE_URL) !== false
            && parse_url($valor, PHP_URL_SCHEME) === 'https'
            && parse_url($valor, PHP_URL_HOST) !== null;
    }

    private function chamadaCompleta(ChamadaFrequencia $chamada): bool
    {
        $ativos = $chamada->alunosAtivos()->pluck('id')->sort()->values()->all();
        $registrados = $chamada->frequencias()->pluck('matricula_id')->sort()->values()->all();

        return count($ativos) > 0 && $ativos === $registrados;
    }

    public function prepararEvento(string $chatId, string $conteudo, int $updateId): array
    {
        $partes = array_map('trim', explode('|', $conteudo, 3));
        if (count($partes) !== 3 || mb_strlen($partes[0]) < 1 || mb_strlen($partes[0]) > 120
            || mb_strlen($partes[2]) < 1 || mb_strlen($partes[2]) > 1500
            || ! $this->dataValida($partes[1], 'Y-m-d H:i')
            || preg_match('/[\x00-\x1F\x7F]/u', $partes[0]) === 1) {
            return ['text' => 'Uso: /novoevento TÍTULO | AAAA-MM-DD HH:MM | DESCRIÇÃO.'];
        }

        return $this->prepararOperacao($chatId, $updateId, 'criar_evento', [
            'titulo' => $partes[0], 'data_inicio' => $partes[1], 'descricao' => $partes[2],
        ], "Prévia do evento público:\n{$partes[0]}\n{$partes[1]}\n{$partes[2]}", 'Publicar evento');
    }

    public function prepararChamada(string $chatId, string $conteudo, int $updateId): array
    {
        if (! preg_match('/^([1-9][0-9]*)\s+([1-9][0-9]*)\s+(\d{4}-\d{2}-\d{2})$/D', trim($conteudo), $m)
            || ! $this->dataValida($m[3], 'Y-m-d')) {
            return ['text' => 'Uso: /novachamada TURMA_ID DISCIPLINA_ID AAAA-MM-DD.'];
        }
        $turma = Turma::find((int) $m[1]);
        $disciplina = Disciplina::find((int) $m[2]);
        if (! $turma || ! $disciplina || (int) $turma->curso_id !== (int) $disciplina->curso_id) {
            return ['text' => 'Turma ou disciplina inválida, ou pertencem a cursos diferentes.'];
        }
        if (ChamadaFrequencia::where('turma_id', $turma->id)->where('disciplina_id', $disciplina->id)
            ->whereDate('data_aula', $m[3])->exists()) {
            return ['text' => 'Já existe uma chamada para essa turma, disciplina e data.'];
        }

        return $this->prepararOperacao($chatId, $updateId, 'criar_chamada', [
            'turma_id' => $turma->id, 'disciplina_id' => $disciplina->id, 'data_aula' => $m[3],
        ], "Nova chamada em rascunho:\nTurma #{$turma->id} — {$turma->nome}\nDisciplina #{$disciplina->id} — {$disciplina->nome}\nData: {$m[3]}", 'Criar chamada');
    }

    public function prepararPresenca(string $chatId, string $conteudo, int $updateId): array
    {
        if (! preg_match('/^([1-9][0-9]*)\s+([1-9][0-9]*)\s+(presente|falta|falta_justificada)$/D', trim($conteudo), $m)) {
            return ['text' => 'Uso: /registrarpresenca CHAMADA_ID MATRICULA_ID presente|falta|falta_justificada.'];
        }
        $chamada = ChamadaFrequencia::find((int) $m[1]);
        if (! $chamada || $chamada->status !== ChamadaFrequencia::STATUS_RASCUNHO
            || ! $this->matriculaElegivel($chamada, (int) $m[2])) {
            return ['text' => 'Chamada indisponível ou matrícula sem vínculo ativo com a turma.'];
        }
        if (Frequencia::where('chamada_frequencia_id', $chamada->id)->where('matricula_id', (int) $m[2])->exists()) {
            return ['text' => 'A presença desta matrícula já foi registrada. Consulte a secretaria para corrigir.'];
        }

        return $this->prepararOperacao($chatId, $updateId, 'registrar_presenca', [
            'chamada_id' => $chamada->id, 'matricula_id' => (int) $m[2], 'situacao' => $m[3],
        ], "Registrar frequência:\nChamada #{$chamada->id}\nMatrícula #{$m[2]}\nSituação: {$m[3]}", 'Registrar frequência');
    }

    private function dataValida(string $valor, string $formato): bool
    {
        $data = \DateTimeImmutable::createFromFormat('!'.$formato, $valor);

        return $data !== false && $data->format($formato) === $valor;
    }

    private function matriculaElegivel(ChamadaFrequencia $chamada, int $matriculaId): bool
    {
        return Matricula::query()->whereKey($matriculaId)
            ->where(function ($query) use ($chamada) {
                $query->where(function ($legado) use ($chamada) {
                    $legado->where('turma_id', $chamada->turma_id)->ativas();
                })->orWhereHas('turmasHistorico', function ($pivot) use ($chamada) {
                    $pivot->where('turmas.id', $chamada->turma_id)
                        ->where('matricula_turma.status', MatriculaTurma::STATUS_ATIVO);
                });
            })->exists();
    }

    private function prepararOperacao(string $chatId, int $updateId, string $acao, array $dados, string $previa, string $botao): array
    {
        return DB::transaction(function () use ($chatId, $updateId, $acao, $dados, $previa, $botao) {
            $usuario = $this->donoComVinculoBloqueado($chatId);
            $codigo = bin2hex(random_bytes(16));
            $id = DB::table('telegram_confirmacoes')->insertGetId([
                'user_id' => $usuario->id, 'chat_id' => $chatId, 'codigo_hash' => hash('sha256', $codigo),
                'acao' => $acao, 'status' => 'pendente', 'expira_em' => now()->addMinutes(10),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('telegram_publicacoes')->insert([
                'confirmacao_id' => $id,
                'dados_criptografados' => Crypt::encryptString(json_encode($dados, JSON_THROW_ON_ERROR)),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('telegram_auditoria')->insert([
                'user_id' => $usuario->id, 'confirmacao_id' => $id, 'update_id' => $updateId,
                'acao' => $acao, 'antes' => 'inexistente', 'depois' => 'pendente', 'created_at' => now(),
            ]);

            return ['text' => $previa."\n\nConfirme em até 10 minutos ou cancele.",
                'reply_markup' => ['inline_keyboard' => [[
                    ['text' => $botao, 'callback_data' => 'confirmar:'.$codigo],
                    ['text' => 'Cancelar', 'callback_data' => 'cancelar:'.$codigo],
                ]]],
            ];
        });
    }

    public function prepararAviso(string $chatId, string $conteudo, int $updateId): array
    {
        $partes = explode('|', $conteudo, 2);
        $titulo = trim($partes[0] ?? '');
        $mensagem = trim($partes[1] ?? '');

        if (count($partes) !== 2 || mb_strlen($titulo) < 1 || mb_strlen($titulo) > 120
            || mb_strlen($mensagem) < 1 || mb_strlen($mensagem) > 1500
            || preg_match('/[\x00-\x1F\x7F]/u', $titulo) === 1) {
            return ['text' => 'Uso: /novoaviso TÍTULO | MENSAGEM (título até 120 e mensagem até 1500 caracteres).'];
        }

        return DB::transaction(function () use ($chatId, $titulo, $mensagem, $updateId) {
            $usuario = $this->donoComVinculoBloqueado($chatId);
            $codigo = bin2hex(random_bytes(16));
            $id = DB::table('telegram_confirmacoes')->insertGetId([
                'user_id' => $usuario->id, 'chat_id' => $chatId,
                'codigo_hash' => hash('sha256', $codigo),
                'acao' => 'publicar_aviso', 'status' => 'pendente',
                'expira_em' => now()->addMinutes(10),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('telegram_publicacoes')->insert([
                'confirmacao_id' => $id,
                'dados_criptografados' => Crypt::encryptString(json_encode([
                    'titulo' => $titulo, 'mensagem' => $mensagem,
                ], JSON_THROW_ON_ERROR)),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('telegram_auditoria')->insert([
                'user_id' => $usuario->id, 'confirmacao_id' => $id,
                'update_id' => $updateId, 'acao' => 'publicar_aviso',
                'antes' => 'inexistente', 'depois' => 'pendente',
                'created_at' => now(),
            ]);

            return [
                'text' => "Prévia do aviso público:\n".$titulo."\n\n".$mensagem
                    ."\n\nAo confirmar, o aviso será publicado para os alunos. Válido por 10 minutos.",
                'reply_markup' => ['inline_keyboard' => [[
                    ['text' => 'Publicar aviso', 'callback_data' => 'confirmar:'.$codigo],
                    ['text' => 'Cancelar', 'callback_data' => 'cancelar:'.$codigo],
                ]]],
            ];
        });
    }

    public function preparar(string $chatId, int $updateId): array
    {
        return DB::transaction(function () use ($chatId, $updateId) {
            $usuario = $this->donoComVinculoBloqueado($chatId);
            $codigo = bin2hex(random_bytes(16));
            $id = DB::table('telegram_confirmacoes')->insertGetId([
                'user_id' => $usuario->id, 'chat_id' => $chatId,
                'codigo_hash' => hash('sha256', $codigo),
                'acao' => 'simulacao', 'status' => 'pendente',
                'expira_em' => now()->addMinutes(10),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('telegram_auditoria')->insert([
                'user_id' => $usuario->id, 'confirmacao_id' => $id,
                'update_id' => $updateId, 'acao' => 'simulacao',
                'antes' => 'inexistente', 'depois' => 'pendente',
                'created_at' => now(),
            ]);

            return [
                'text' => 'Simulação administrativa'."\n".'Esta confirmação é apenas um teste: NÃO aprova inscrições nem altera matrículas.'."\n"
                    .'Válida por 10 minutos. Confirme ou cancele.',
                'reply_markup' => ['inline_keyboard' => [[
                    ['text' => 'Confirmar', 'callback_data' => 'confirmar:'.$codigo],
                    ['text' => 'Cancelar', 'callback_data' => 'cancelar:'.$codigo],
                ]]],
            ];
        });
    }

    public function prepararRevisao(string $chatId, string $inscricaoId, int $updateId): array
    {
        return DB::transaction(function () use ($chatId, $inscricaoId, $updateId) {
            $usuario = $this->donoComVinculoBloqueado($chatId);
            if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/D', $inscricaoId) !== 1) {
                return ['text' => 'Identificador de inscrição inválido.'];
            }

            $inscricao = Application::query()->with(['curso:id,titulo', 'courseModality:id,nome'])->find($inscricaoId);
            if ($inscricao === null) {
                return ['text' => 'Inscrição não encontrada.'];
            }
            if (in_array($inscricao->status, [Application::STATUS_EM_ANALISE, Application::STATUS_APROVADO], true) === false) {
                return ['text' => 'Pré-análise não disponível para o estado atual. Consulte o painel.'];
            }

            $codigo = bin2hex(random_bytes(16));
            $id = DB::table('telegram_confirmacoes')->insertGetId([
                'user_id' => $usuario->id,
                'chat_id' => $chatId,
                'codigo_hash' => hash('sha256', $codigo),
                'acao' => 'revisao_inscricao',
                'application_id' => $inscricao->id,
                'estado_hash' => $this->estadoHash($inscricao),
                'status' => 'pendente',
                'expira_em' => now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('telegram_auditoria')->insert([
                'user_id' => $usuario->id,
                'confirmacao_id' => $id,
                'update_id' => $updateId,
                'acao' => 'revisao_inscricao',
                'antes' => 'inexistente',
                'depois' => 'pendente',
                'created_at' => now(),
            ]);

            $nome = mb_strimwidth(preg_replace('/[\r\n]+/u', ' ', $inscricao->nome_completo) ?? '', 0, 38, '…');

            return [
                'text' => 'Pré-análise de inscrição #'.$inscricao->id."\n"
                    .'Candidato: '.$nome."\n"
                    .'Curso: '.mb_strimwidth($inscricao->curso?->titulo ?? 'Não informado', 0, 40, '…')."\n"
                    .'Modalidade: '.mb_strimwidth($inscricao->courseModality?->nome ?? 'Não informada', 0, 25, '…')."\n"
                    .'Estado: '.$inscricao->status."\n"
                    .'Documentos cadastrados: '.$inscricao->documents()->count()."\n"
                    .'Registrar revisão NÃO aprova, rejeita ou matricula. Validade: 10 minutos.',
                'reply_markup' => ['inline_keyboard' => [[
                    ['text' => 'Registrar revisão', 'callback_data' => 'confirmar:'.$codigo],
                    ['text' => 'Cancelar', 'callback_data' => 'cancelar:'.$codigo],
                ]]],
            ];
        });
    }

    private function estadoHash(Application $inscricao): string
    {
        // Hash apenas: o snapshot não persiste documentos ou dados pessoais.
        $inscricao->load(['curso:id,titulo', 'courseModality:id,nome']);

        $documentos = $inscricao->documents()->orderBy('id')->get([
            'id', 'updated_at', 'validado', 'validado_em', 'deleted_at',
        ])->map(fn ($documento) => [
            (string) $documento->id,
            $documento->updated_at?->format('Y-m-d H:i:s.u'),
            (bool) $documento->validado,
            $documento->validado_em?->format('Y-m-d H:i:s.u'),
        ])->all();

        return hash('sha256', json_encode([
            'inscricao' => (string) $inscricao->getKey(),
            'status' => $inscricao->status,
            'atualizada' => $inscricao->updated_at?->format('Y-m-d H:i:s.u'),
            'nome' => $inscricao->nome_completo,
            'curso_id' => $inscricao->curso_id,
            'curso_titulo' => $inscricao->curso?->titulo,
            'modalidade_id' => $inscricao->course_modality_id,
            'modalidade_nome' => $inscricao->courseModality?->nome,
            'documentos' => $documentos,
        ], JSON_THROW_ON_ERROR));
    }

    public function decidir(string $chatId, string $acao, string $codigo, int $updateId): array
    {
        if (in_array($acao, ['confirmar', 'cancelar'], true) === false || preg_match('/^[0-9a-f]{32}$/D', $codigo) !== 1) {
            return ['text' => 'Confirmação inválida.'];
        }

        $resultado = DB::transaction(function () use ($chatId, $acao, $codigo, $updateId) {
            $usuario = $this->donoComVinculoBloqueado($chatId);
            $linha = DB::table('telegram_confirmacoes')
                ->where('codigo_hash', hash('sha256', $codigo))
                ->lockForUpdate()->first();
            if ($linha === null || (int) $linha->user_id !== (int) $usuario->id || $linha->chat_id !== $chatId) {
                return 'invalido';
            }
            if ($linha->status !== 'pendente') {
                return $linha->status;
            }

            $status = now()->greaterThan($linha->expira_em) ? 'expirado'
                : ($acao === 'confirmar' ? 'confirmado' : 'cancelado');
            if ($status === 'confirmado' && $linha->acao === 'revisao_inscricao') {
                $inscricao = Application::query()->whereKey($linha->application_id)->lockForUpdate()->first();
                if ($inscricao === null || $this->estadoHash($inscricao) !== $linha->estado_hash
                    || in_array($inscricao->status, [Application::STATUS_EM_ANALISE, Application::STATUS_APROVADO], true) === false) {
                    $status = 'obsoleto';
                }
            }
            if (in_array($linha->acao, ['publicar_aviso', 'criar_evento', 'criar_chamada', 'registrar_presenca', 'criar_aula', 'liberar_aula', 'concluir_chamada'], true)) {
                if ($status === 'confirmado') {
                    $publicacao = DB::table('telegram_publicacoes')
                        ->where('confirmacao_id', $linha->id)->lockForUpdate()->first();
                    if ($publicacao === null) {
                        $status = 'obsoleto';
                    } else {
                        $dados = json_decode(Crypt::decryptString($publicacao->dados_criptografados), true, 512, JSON_THROW_ON_ERROR);
                        $recurso = $this->executarOperacao($linha->acao, $dados, $usuario);
                        $status = $recurso !== null ? 'confirmado' : 'obsoleto';
                        if ($recurso !== null) {
                            DB::table('telegram_confirmacoes')->where('id', $linha->id)->update([
                                'recurso_tipo' => $recurso->getTable(), 'recurso_id' => $recurso->getKey(),
                            ]);
                        }
                    }
                }

                // Descarta o texto em todos os estados finais, inclusive após expiração.
                DB::table('telegram_publicacoes')->where('confirmacao_id', $linha->id)->delete();
            }
            DB::table('telegram_confirmacoes')->where('id', $linha->id)->update([
                'status' => $status, 'consumido_em' => now(), 'updated_at' => now(),
            ]);
            DB::table('telegram_auditoria')->insert([
                'user_id' => $usuario->id, 'confirmacao_id' => $linha->id,
                'update_id' => $updateId, 'acao' => $linha->acao,
                'antes' => 'pendente', 'depois' => $status,
                'created_at' => now(),
            ]);

            return $status === 'confirmado' ? 'confirmado:'.$linha->acao : $status;
        });

        return ['text' => match ($resultado) {
            'confirmado:publicar_aviso' => 'Aviso publicado com sucesso.',
            'confirmado:criar_evento' => 'Evento publicado no calendário.',
            'confirmado:criar_chamada' => 'Chamada criada em rascunho. Registre a frequência dos alunos.',
            'confirmado:registrar_presenca' => 'Frequência registrada com sucesso.',
            'confirmado:criar_aula' => 'Aula cadastrada em rascunho. Use /aulaspendentes para consultar e /liberaraula ID para publicar.',
            'confirmado:liberar_aula' => 'Aula liberada para os alunos.',
            'confirmado:concluir_chamada' => 'Chamada concluída com a frequência consolidada.',
            'confirmado:revisao_inscricao', 'confirmado:simulacao' => 'Revisão ou simulação confirmada, sem alterar os dados acadêmicos.',
            'cancelado' => 'Operação cancelada. Nenhuma alteração foi realizada.',
            'expirado' => 'Confirmação expirada. Prepare uma nova revisão.',
            'obsoleto' => 'Os dados mudaram ou a operação já foi realizada. Consulte o painel e prepare uma nova confirmação.',
            default => 'Confirmação inválida ou sem autorização.',
        }];
    }

    private function executarOperacao(string $acao, array $dados, User $usuario): ?Model
    {
        if ($acao === 'criar_aula') {
            $disciplina = Disciplina::with('modulo')->whereKey($dados['disciplina_id'])->lockForUpdate()->first();
            if (! $disciplina || ! $disciplina->modulo
                || (int) $disciplina->modulo->curso_id !== (int) $disciplina->curso_id) {
                return null;
            }
            $ordem = (int) Aula::where('disciplina_id', $disciplina->id)->max('ordem') + 1;

            return Aula::create(['disciplina_id' => $disciplina->id, 'modulo_id' => $disciplina->modulo_id,
                'titulo' => $dados['titulo'], 'video_url' => $dados['video_url'],
                'pdf_url' => $dados['pdf_url'], 'tipo_conteudo' => $dados['video_url'] ? 'video' : 'texto',
                'ordem' => $ordem, 'liberada' => false]);

        }
        if ($acao === 'liberar_aula') {
            $aula = Aula::whereKey($dados['aula_id'])->lockForUpdate()->first();
            if (! $aula || $aula->liberada || ! $aula->disciplina_id
                || ! hash_equals($dados['estado_hash'] ?? '', $this->estadoRegistroHash($aula))) {
                return null;
            }
            $disciplina = Disciplina::with('modulo')->whereKey($aula->disciplina_id)->lockForUpdate()->first();
            if (! $disciplina || ! $disciplina->modulo
                || (int) $disciplina->curso_id !== (int) $disciplina->modulo->curso_id
                || (int) $aula->modulo_id !== (int) $disciplina->modulo_id) {
                return null;
            }
            $aula->update(['liberada' => true, 'tipo_liberacao' => 'imediata', 'data_liberacao' => null]);

            return $aula;
        }
        if ($acao === 'concluir_chamada') {
            $chamada = ChamadaFrequencia::whereKey($dados['chamada_id'])->lockForUpdate()->first();
            if ($chamada !== null) {
                // O serviço de vínculo também bloqueia a turma; serializa a lista de alunos.
                Turma::whereKey($chamada->turma_id)->lockForUpdate()->first();
                $chamada->frequencias()->orderBy('id')->lockForUpdate()->get();
            }
            if (! $chamada || $chamada->status !== ChamadaFrequencia::STATUS_RASCUNHO
                || ! $this->chamadaCompleta($chamada)
                || ! hash_equals($dados['estado_hash'] ?? '', $this->estadoChamadaHash($chamada))) {
                return null;
            }
            $chamada->update(['status' => ChamadaFrequencia::STATUS_CONCLUIDA,
                'concluida_por' => $usuario->id]);

            return $chamada;
        }
        if ($acao === 'publicar_aviso') {
            return Aviso::create(['titulo' => $dados['titulo'], 'mensagem' => $dados['mensagem'],
                'tipo' => 'info', 'ativo' => true, 'data_publicacao' => now()]);

        }
        if ($acao === 'criar_evento') {
            return EventoCalendario::create(['titulo' => $dados['titulo'], 'descricao' => $dados['descricao'],
                'data_inicio' => $dados['data_inicio'], 'tipo' => 'academico', 'ativo' => true]);

        }
        if ($acao === 'criar_chamada') {
            $turma = Turma::query()->whereKey($dados['turma_id'])->lockForUpdate()->first();
            $disciplina = Disciplina::find($dados['disciplina_id']);
            if (! $turma || ! $disciplina || (int) $turma->curso_id !== (int) $disciplina->curso_id
                || ChamadaFrequencia::where('turma_id', $turma->id)->where('disciplina_id', $disciplina->id)
                    ->whereDate('data_aula', $dados['data_aula'])->exists()) {
                return null;
            }

            return ChamadaFrequencia::create(['turma_id' => $turma->id, 'disciplina_id' => $disciplina->id,
                'data_aula' => $dados['data_aula'], 'status' => ChamadaFrequencia::STATUS_RASCUNHO,
                'criada_por' => $usuario->id]);

        }
        if ($acao === 'registrar_presenca') {
            $chamada = ChamadaFrequencia::query()->whereKey($dados['chamada_id'])->lockForUpdate()->first();
            if (! $chamada || $chamada->status !== ChamadaFrequencia::STATUS_RASCUNHO
                || ! $this->matriculaElegivel($chamada, (int) $dados['matricula_id'])
                || Frequencia::where('chamada_frequencia_id', $chamada->id)
                    ->where('matricula_id', $dados['matricula_id'])->exists()) {
                return null;
            }

            return Frequencia::create(['chamada_frequencia_id' => $chamada->id,
                'matricula_id' => $dados['matricula_id'], 'situacao' => $dados['situacao'],
                'registrado_por' => $usuario->id]);

        }

        return null;
    }

    private function donoComVinculoBloqueado(string $chatId): User
    {
        // Ordem de locks: primeiro vínculo, depois proposta. A revogação segue a mesma ordem.
        $vinculo = DB::table('telegram_vinculos')
            ->where('chat_id', $chatId)->lockForUpdate()->first();
        $usuario = $vinculo ? User::find($vinculo->user_id) : null;
        abort_unless(app(TelegramAdministrativo::class)->podeOperar($usuario), 403);

        return $usuario;
    }
}
