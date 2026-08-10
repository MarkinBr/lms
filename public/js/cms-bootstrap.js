/**
 * CMS BOOTSTRAP v6
 * Injetado em todas as páginas do site
 * Lê overrides do localStorage e aplica automaticamente
 * Compatível com v3, v4, v5, v6 do painel
 */

(function () {
    // Suportar múltiplas versões de storage
    const STORAGE_KEYS = [
        'cetsadp_admin_v6', // Latest
        'cetsadp_admin_v5',
        'cetsadp_admin_v4',
        'cetsadp_admin_v3',
    ];

    const state = {
        overrides: {},
        appliedCount: 0,
    };

    /**
     * Carrega overrides do localStorage
     * Tenta várias chaves por compatibilidade
     */
    function loadOverrides() {
        for (const key of STORAGE_KEYS) {
            const stored = localStorage.getItem(key);
            if (stored) {
                try {
                    state.overrides = JSON.parse(stored);
                    console.log(`✅ CMS Bootstrap: Carregado de "${key}" (${Object.keys(state.overrides).length} overrides)`);
                    return true;
                } catch (e) {
                    console.warn(`⚠️ CMS Bootstrap: Erro ao parsear "${key}":`, e);
                }
            }
        }

        console.log('ℹ️ CMS Bootstrap: Nenhum override encontrado no localStorage');
        return false;
    }

    /**
     * Aplica os overrides ao DOM
     */
    function applyOverrides() {
        Object.entries(state.overrides).forEach(([selector, override]) => {
            try {
                const elements = document.querySelectorAll(selector);

                if (elements.length === 0) {
                    console.warn(`⚠️ Selector não encontrado: ${selector}`);
                    return;
                }

                elements.forEach((el) => {
                    // Aplicar novo valor
                    if (override.newValue !== undefined) {
                        el.textContent = override.newValue;
                    }

                    // Marcar como editado
                    el.classList.add('cms-edited');
                    el.dataset.cmsOverride = 'true';
                    el.dataset.cmsOriginal = override.originalValue;

                    // Estilo visual opcional
                    if (localStorage.getItem('cetsadp_admin_visual') === '1') {
                        el.style.borderBottom = '2px dashed #f59e0b';
                    }

                    state.appliedCount++;
                });
            } catch (e) {
                console.error(`❌ Erro ao aplicar override "${selector}":`, e);
            }
        });

        if (state.appliedCount > 0) {
            console.log(`✅ CMS Bootstrap: ${state.appliedCount} mudanças aplicadas`);
        }
    }

    /**
     * Injeta o hook do painel se estiver em modo admin
     */
    function injectAdminHook() {
        // Verificar parâmetro ?admin=1
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('admin') !== '1') {
            return;
        }

        console.log('🎨 CMS Bootstrap: Modo admin detectado, injetando hook...');

        // Injetar script do hook
        // Nota: Este script será servido pelo Laravel em /public/js/admin/painel-hook.js
        const hookScript = document.createElement('script');
        hookScript.src = '/js/admin/painel-hook.js?v=' + Date.now();
        hookScript.async = true;
        hookScript.onerror = () => {
            console.error('❌ Erro ao carregar painel-hook.js');
        };

        document.head.appendChild(hookScript);
    }

    /**
     * Monitora mudanças no localStorage
     * Útil quando o painel modifica e o site está aberto em outra aba
     */
    function monitorStorageChanges() {
        window.addEventListener('storage', (event) => {
            if (!STORAGE_KEYS.includes(event.key)) return;

            console.log('🔄 CMS Bootstrap: Detectada mudança no localStorage, recarregando overrides...');

            try {
                state.overrides = JSON.parse(event.newValue || '{}');
                applyOverrides();
            } catch (e) {
                console.error('❌ Erro ao processar mudança de storage:', e);
            }
        });
    }

    /**
     * Inicializar quando DOM está pronto
     */
    function init() {
        console.log('🚀 CMS Bootstrap v6 iniciando...');

        loadOverrides();
        applyOverrides();
        injectAdminHook();
        monitorStorageChanges();

        // Expor API global para debugging
        window.CETSADP = {
            version: 'v6',
            state,
            loadOverrides,
            applyOverrides,
            clearOverrides: () => {
                state.overrides = {};
                STORAGE_KEYS.forEach(key => localStorage.removeItem(key));
                location.reload();
            },
        };

        console.log('✨ CMS Bootstrap pronto. Use window.CETSADP para acessar a API.');
    }

    // Executar quando documento estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
