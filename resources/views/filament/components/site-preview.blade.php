<div class="w-full h-full sticky top-4 space-y-4" x-data="{ width: '100%', device: 'desktop' }">
    
    <div class="flex flex-wrap justify-between items-center bg-white p-2 rounded-xl border border-gray-200 shadow-sm">
        <span class="text-xs font-bold text-gray-500 uppercase ml-2 hidden sm:block">Preview</span>
        
        <div class="flex space-x-1 bg-gray-100 p-1 rounded-lg">
            <button @click="width = '375px'; device = 'mobile'" 
                    :class="device === 'mobile' ? 'bg-white text-primary shadow' : 'text-gray-500 hover:text-gray-900'"
                    type="button"
                    class="p-2 rounded transition" title="Modo Celular">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            </button>

            <button @click="width = '768px'; device = 'tablet'" 
                    :class="device === 'tablet' ? 'bg-white text-primary shadow' : 'text-gray-500 hover:text-gray-900'"
                    type="button"
                    class="p-2 rounded transition" title="Modo Tablet">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            </button>

            <button @click="width = '100%'; device = 'desktop'" 
                    :class="device === 'desktop' ? 'bg-white text-primary shadow' : 'text-gray-500 hover:text-gray-900'"
                    type="button"
                    class="p-2 rounded transition" title="Modo Responsivo">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </button>
        </div>

        <div class="flex space-x-2">
            <button onclick="document.getElementById('site-frame').src = document.getElementById('site-frame').src" 
                    type="button"
                    class="p-2 text-gray-500 hover:text-primary hover:bg-gray-100 rounded transition" title="Recarregar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
            
            <a href="{{ url('/') }}" target="_blank"
               class="p-2 text-gray-500 hover:text-primary hover:bg-gray-100 rounded transition" title="Abrir em Nova Aba">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        </div>
    </div>

    <div class="bg-gray-200 rounded-xl shadow-inner overflow-hidden flex justify-center items-start pt-4 transition-all duration-300" 
         :class="device === 'mobile' ? 'p-8 bg-gray-800' : (device === 'tablet' ? 'p-8 bg-gray-700' : 'p-0')"
         style="height: 800px;">
        
        <div class="transition-all duration-500 ease-in-out bg-white shadow-2xl overflow-hidden relative"
             :style="'width: ' + width + '; height: ' + (device === 'desktop' ? '100%' : '95%') + '; border-radius: ' + (device === 'desktop' ? '0' : '20px')">
            
            <div x-show="device !== 'desktop'" class="absolute top-0 left-0 w-full h-full pointer-events-none border-[8px] border-gray-900/10 z-50" 
                 :style="'border-radius: 20px'"></div>

            <iframe id="site-frame" 
                    src="{{ url('/') }}" 
                    class="w-full h-full bg-white" 
                    frameborder="0">
            </iframe>
        </div>
    </div>
    
    <p class="text-xs text-gray-500 text-center">
        💡 Clique em "Salvar" para ver as alterações. Use os botões acima para testar a responsividade.
    </p>
</div>