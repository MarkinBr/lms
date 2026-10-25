<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PerfilController extends Controller
{
    /**
     * Exibe o perfil do aluno com estatísticas completas
     */
    public function index()
    {
        $aluno = auth()->user()->load([
            'matriculas.curso',
            'certificados',
            'avaliacoes',
        ]);

        // Estatísticas completas e detalhadas
        $estatisticas = $this->getEstatisticasCompletas($aluno);

        // Atividade recente
        $atividadeRecente = $this->getAtividadeRecente($aluno);

        // Progresso geral
        $progressoGeral = $this->calcularProgressoGeral($aluno);

        return view('aluno.perfil.index', compact(
            'aluno',
            'estatisticas',
            'atividadeRecente',
            'progressoGeral'
        ));
    }

    /**
     * Atualiza os dados do perfil com validação robusta
     */
    public function update(Request $request)
    {
        $aluno = auth()->user();

        // Validação completa e robusta
        $validated = $request->validate([
            // Dados Pessoais
            'name' => 'required|string|min:3|max:255',
            'telefone' => 'nullable|string|max:20',
            'data_nasc' => [
                'nullable',
                'date',
                'before:today',
                'after:'.now()->subYears(120)->format('Y-m-d'),
            ],
            'cpf' => [
                'nullable',
                'string',
                'max:14',
                function ($attribute, $value, $fail) use ($aluno) {
                    if ($value) {
                        $cpfLimpo = preg_replace('/[^0-9]/', '', $value);
                        if (strlen($cpfLimpo) !== 11 || ! $this->validarCPF($cpfLimpo)) {
                            $fail('O CPF informado é inválido.');
                        }
                        // Verifica se CPF já existe (exceto o próprio usuário)
                        $existe = DB::table('users')
                            ->where('cpf', $value)
                            ->where('id', '!=', $aluno->id)
                            ->exists();
                        if ($existe) {
                            $fail('Este CPF já está cadastrado.');
                        }
                    }
                },
            ],

            // Endereço
            'cep' => 'nullable|string|max:9',
            'endereco' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'estado' => 'nullable|string|size:2',

            // Informações Adicionais
            'genero' => 'nullable|in:masculino,feminino,outro,prefiro_nao_informar',
            'profissao' => 'nullable|string|max:100',
            'escolaridade' => 'nullable|in:fundamental_incompleto,fundamental_completo,medio_incompleto,medio_completo,superior_incompleto,superior_completo,pos_graduacao,mestrado,doutorado',
            'bio' => 'nullable|string|max:500',

            // Preferências
            'receber_newsletter' => 'boolean',
            'receber_notificacoes_email' => 'boolean',
            'perfil_publico' => 'boolean',

            // Foto
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
        ], [
            'name.required' => 'O nome é obrigatório.',
            'name.min' => 'O nome deve ter pelo menos 3 caracteres.',

            'data_nasc.before' => 'A data de nascimento deve ser anterior à data atual.',
            'data_nasc.after' => 'Data de nascimento inválida.',
            'foto.image' => 'O arquivo deve ser uma imagem.',
            'foto.mimes' => 'A foto deve ser nos formatos: jpeg, png, jpg ou webp.',
            'foto.max' => 'A foto não pode ser maior que 5MB.',
        ]);

        DB::beginTransaction();

        try {
            // Prepara os dados para atualização
            $dados = collect($validated)->except(['foto'])->toArray();

            // Limpa CPF (remove formatação)
            if (isset($dados['cpf'])) {
                $dados['cpf'] = preg_replace('/[^0-9]/', '', $dados['cpf']);
            }

            // Limpa CEP
            if (isset($dados['cep'])) {
                $dados['cep'] = preg_replace('/[^0-9]/', '', $dados['cep']);
            }

            // Limpa telefone
            if (isset($dados['telefone'])) {
                $dados['telefone'] = preg_replace('/[^0-9]/', '', $dados['telefone']);
            }

            // Upload da foto (Sistema Nativo do Laravel)
            if ($request->hasFile('foto')) {
                // Remove foto antiga se existir
                if ($aluno->foto && Storage::disk('public')->exists($aluno->foto)) {
                    Storage::disk('public')->delete($aluno->foto);
                }

                // Salva a nova foto na pasta fotos-alunos
                $dados['foto'] = $request->file('foto')->store('fotos-alunos', 'public');
            }

            // Registra data da última atualização
            $dados['perfil_atualizado_em'] = now();

            // Atualiza o perfil
            $aluno->update($dados);

            // Log da atualização
            Log::info('Perfil atualizado', [
                'user_id' => $aluno->id,
                'campos_atualizados' => array_keys($dados),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Perfil atualizado com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erro ao atualizar perfil', [
                'user_id' => $aluno->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Erro ao atualizar perfil. Tente novamente.')
                ->withInput();
        }
    }

    /**
     * Altera a senha com validação de segurança
     */
    public function alterarSenha(Request $request)
    {
        $validated = $request->validate([
            'senha_atual' => 'required',
            'nova_senha' => [
                'required',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', // Deve conter maiúscula, minúscula e número
            ],
        ], [
            'senha_atual.required' => 'Digite sua senha atual.',
            'nova_senha.required' => 'Digite a nova senha.',
            'nova_senha.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'nova_senha.confirmed' => 'As senhas não conferem.',
            'nova_senha.regex' => 'A senha deve conter letras maiúsculas, minúsculas e números.',
        ]);

        $aluno = auth()->user();

        // Verifica senha atual
        if (! Hash::check($validated['senha_atual'], $aluno->password)) {
            return redirect()->back()
                ->with('error', 'A senha atual está incorreta.')
                ->withErrors(['senha_atual' => 'Senha incorreta']);
        }

        // Verifica se a nova senha é diferente da atual
        if (Hash::check($validated['nova_senha'], $aluno->password)) {
            return redirect()->back()
                ->with('error', 'A nova senha deve ser diferente da senha atual.');
        }

        DB::beginTransaction();

        try {
            // Atualiza a senha
            $aluno->forceFill([
                'password' => Hash::make($validated['nova_senha']),
                'senha_alterada_em' => now(),
                'email_confirmacao_pendente' => null,
                'email_confirmacao_token' => null,
                'email_confirmacao_expira_em' => null,
            ])->save();

            // Log de segurança
            Log::info('Senha alterada', [
                'user_id' => $aluno->id,
                'ip' => request()->ip(),
            ]);

            DB::commit();

            // Opcional: Enviar email de notificação
            // Mail::to($aluno->email)->send(new SenhaAlteradaMail($aluno));

            return redirect()->back()->with('success', 'Senha alterada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erro ao alterar senha', [
                'user_id' => $aluno->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Erro ao alterar senha. Tente novamente.');
        }
    }

    /**
     * Deleta a foto do perfil
     */
    public function deletarFoto()
    {
        $aluno = auth()->user();

        if (! $aluno->foto) {
            return redirect()->back()->with('error', 'Você não possui foto de perfil.');
        }

        DB::beginTransaction();

        try {
            // Remove o arquivo
            if (Storage::disk('public')->exists($aluno->foto)) {
                Storage::disk('public')->delete($aluno->foto);
            }

            // Atualiza o banco
            $aluno->update(['foto' => null]);

            Log::info('Foto de perfil removida', ['user_id' => $aluno->id]);

            DB::commit();

            return redirect()->back()->with('success', 'Foto removida com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erro ao remover foto', [
                'user_id' => $aluno->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Erro ao remover foto. Tente novamente.');
        }
    }

    /**
     * Exporta os dados do perfil (LGPD/GDPR compliance)
     */
    public function exportarDados()
    {
        $aluno = auth()->user()->load([
            'matriculas.curso',
            'certificados',
            'avaliacoes',
            'progresso',
        ]);

        $dados = [
            'dados_pessoais' => [
                'nome' => $aluno->name,
                'email' => $aluno->email,
                'cpf' => $aluno->cpf,
                'telefone' => $aluno->telefone,
                'data_nascimento' => $aluno->data_nasc,
                'genero' => $aluno->genero,
                'profissao' => $aluno->profissao,
                'escolaridade' => $aluno->escolaridade,
            ],
            'endereco' => [
                'cep' => $aluno->cep,
                'endereco' => $aluno->endereco,
                'numero' => $aluno->numero,
                'complemento' => $aluno->complemento,
                'bairro' => $aluno->bairro,
                'cidade' => $aluno->cidade,
                'estado' => $aluno->estado,
            ],
            'matriculas' => $aluno->matriculas->map(function ($m) {
                return [
                    'curso' => $m->curso->nome,
                    'data_matricula' => $m->created_at,
                    'status' => $m->status,
                    'progresso' => $m->progresso.'%',
                ];
            }),
            'certificados' => $aluno->certificados->map(function ($c) {
                return [
                    'curso' => $c->curso->nome,
                    'data_emissao' => $c->data_emissao,
                    'codigo' => $c->codigo,
                ];
            }),
            'data_exportacao' => now()->format('d/m/Y H:i:s'),
        ];

        $nomeArquivo = 'meus_dados_'.now()->format('Y-m-d_His').'.json';

        return response()->json($dados)
            ->header('Content-Disposition', 'attachment; filename="'.$nomeArquivo.'"');
    }

    /**
     * Solicita exclusão da conta (soft delete)
     */
    public function solicitarExclusao(Request $request)
    {
        $request->validate([
            'senha_confirmacao' => 'required',
            'motivo' => 'nullable|string|max:500',
        ]);

        $aluno = auth()->user();

        if (! Hash::check($request->senha_confirmacao, $aluno->password)) {
            return redirect()->back()->with('error', 'Senha incorreta.');
        }

        DB::beginTransaction();

        try {
            // Marca conta para exclusão (30 dias)
            $aluno->update([
                'exclusao_solicitada_em' => now(),
                'motivo_exclusao' => $request->motivo,
                'status' => 'pendente_exclusao',
            ]);

            Log::warning('Exclusão de conta solicitada', [
                'user_id' => $aluno->id,
                'motivo' => $request->motivo,
            ]);

            DB::commit();

            // Opcional: Enviar email de confirmação
            // Mail::to($aluno->email)->send(new ExclusaoSolicitadaMail($aluno));

            auth()->logout();

            return redirect()->route('login')
                ->with('success', 'Solicitação de exclusão enviada. Sua conta será excluída em 30 dias.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erro ao solicitar exclusão', [
                'user_id' => $aluno->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Erro ao processar solicitação. Tente novamente.');
        }
    }

    // ============================================
    // MÉTODOS AUXILIARES PRIVADOS
    // ============================================

    /**
     * Valida CPF
     */
    private function validarCPF($cpf)
    {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        // Verifica se tem 11 dígitos
        if (strlen($cpf) != 11) {
            return false;
        }

        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Valida primeiro dígito verificador
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calcula estatísticas completas do aluno
     */
    private function getEstatisticasCompletas($aluno)
    {
        $matriculas = $aluno->matriculas;

        return [
            'total_cursos' => $matriculas->count(),
            'cursos_concluidos' => $matriculas->where('status', 'concluida')->count(),
            'cursos_em_andamento' => $matriculas->where('status', 'ativa')->count(),
            'cursos_trancados' => $matriculas->where('status', 'trancada')->count(),
            'certificados' => $aluno->certificados()->count(),
            'total_horas_estudo' => $matriculas->sum('tempo_estudo') ?? 0,
            'media_progresso' => round($matriculas->avg('progresso') ?? 0, 1),
            'avaliacoes_realizadas' => $aluno->avaliacoes()->count(),
            // CORRIGIDO: mudado de 'nota' para 'media_final'
            'media_notas' => round($aluno->avaliacoes()->avg('media_final') ?? 0, 1),
            'dias_desde_cadastro' => (int) $aluno->created_at->diffInDays(now()),
            'ultimo_acesso' => $aluno->last_login_at ?? $aluno->updated_at,
        ];
    }

    /**
     * Obtém atividade recente do aluno
     */
    private function getAtividadeRecente($aluno)
    {
        return [
            'ultimas_aulas' => method_exists($aluno, 'progresso') ? $aluno->progresso()
                ->latest()
                ->limit(5)
                ->get() : collect([]),
            'ultimos_certificados' => $aluno->certificados()
                ->with('curso')
                ->latest()
                ->limit(3)
                ->get(),
            'ultimas_avaliacoes' => $aluno->avaliacoes()
                ->latest()
                ->limit(3)
                ->get(),
        ];
    }

    /**
     * Calcula progresso geral do aluno
     */
    private function calcularProgressoGeral($aluno)
    {
        $matriculas = $aluno->matriculas;

        if ($matriculas->isEmpty()) {
            return [
                'percentual' => 0,
                'aulas_assistidas' => 0,
                'total_aulas' => 0,
                'tempo_total' => 0,
            ];
        }

        $totalAulas = 0;
        $aulasAssistidas = 0;
        $tempoTotal = 0;

        foreach ($matriculas as $matricula) {
            $curso = $matricula->curso;

            // Verifica se o relacionamento 'aulas' existe e é contável
            if (method_exists($curso, 'aulas')) {
                $totalAulasCurso = $curso->aulas()->count();
            } else {
                // Fallback seguro se não existir o relacionamento hasManyThrough
                $totalAulasCurso = 0;
            }

            // Simplificação para evitar queries complexas se não houver progresso
            $aulasCompletadas = 0;
            if (method_exists($aluno, 'progresso')) {
                // Logica simplificada de contagem
                // (Implementação real depende da estrutura exata de progresso)
            }

            $totalAulas += $totalAulasCurso;
            // $aulasAssistidas += $aulasCompletadas; // Reativar quando lógica estiver alinhada
            $tempoTotal += $matricula->tempo_estudo ?? 0;
        }

        // Fallback: usa o campo 'progresso' da matrícula que já é uma porcentagem
        $percentualGeral = round($matriculas->avg('progresso'), 1);

        return [
            'percentual' => $percentualGeral,
            'aulas_assistidas' => $aulasAssistidas,
            'total_aulas' => $totalAulas,
            'tempo_total' => $tempoTotal,
        ];
    }
}
