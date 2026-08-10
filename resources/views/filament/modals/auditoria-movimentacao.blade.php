{{-- resources/views/filament/modals/auditoria-movimentacao.blade.php --}}

<div class="space-y-4">
    @if($auditorias->isEmpty())
        <div class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="mt-2">Nenhuma alteração registrada</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($auditorias as $auditoria)
                <div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($auditoria->acao === 'criacao') bg-green-100 text-green-800
                                @elseif($auditoria->acao === 'edicao') bg-yellow-100 text-yellow-800
                                @elseif($auditoria->acao === 'cancelamento') bg-red-100 text-red-800
                                @else bg-blue-100 text-blue-800
                                @endif">
                                @if($auditoria->acao === 'criacao') ✨ Criação
                                @elseif($auditoria->acao === 'edicao') ✏️ Edição
                                @elseif($auditoria->acao === 'cancelamento') ❌ Cancelamento
                                @else 🔄 Restauração
                                @endif
                            </span>
                        </div>
                        <span class="text-xs text-gray-500">
                            {{ $auditoria->created_at->format('d/m/Y H:i') }}
                        </span>
                    </div>

                    <div class="text-sm">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">
                            👤 {{ $auditoria->usuario->name }}
                        </p>

                        @if($auditoria->motivo)
                            <p class="mt-2 text-gray-600 dark:text-gray-400">
                                <strong>Motivo:</strong> {{ $auditoria->motivo }}
                            </p>
                        @endif

                        @if($auditoria->acao === 'edicao' && $auditoria->dados_anteriores)
                            <div class="mt-3 space-y-2">
                                <p class="font-medium text-gray-700 dark:text-gray-300">Alterações:</p>
                                <div class="bg-white dark:bg-gray-700 rounded p-3 space-y-1 text-xs">
                                    @foreach($auditoria->dados_novos as $campo => $novoValor)
                                        @if(isset($auditoria->dados_anteriores[$campo]) && $auditoria->dados_anteriores[$campo] != $novoValor)
                                            <div class="grid grid-cols-3 gap-2">
                                                <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $campo)) }}:</span>
                                                <span class="text-red-600 line-through">{{ $auditoria->dados_anteriores[$campo] }}</span>
                                                <span class="text-green-600">→ {{ $novoValor }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>