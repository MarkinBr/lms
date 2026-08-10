/**
 * PAINEL HOOK v6
 * Injetado no site quando ?admin=1
 * Comunica com o painel via postMessage
 * Escaneia DOM e cataloga campos editáveis
 */

(function () {
    // Configurações
    const HOOK_CONFIG = {
        editableSelectors: [
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'p', 'span', 'a', 'button',
            '[data-cms]', '.cms-text', '.cms-content'
        ],
        storageKey: 'cetsadp_admin_v6',
        parentOrigin: '*', // Será restringido ao chamar postMessage
    };

    let state = {
        fields: {},
        selectedElement: null,
        overrides: {},
    };

    // ========== CATALOG BUILDER ==========
    function buildCatalog() {
        state.fields = {};
        const editables = document.querySelectorAll(HOOK_CONFIG.editableSelectors.join(', '));

        editables.forEach((el, index) => {
            const selector = buildCssPath(el);
            const text = el.textContent?.trim().substring(0, 100) || '';

            if (selector && text) {
                state.fields[selector] = {
                    name: `${el.tagName.toLowerCase()} #${index}`,
                    selector,
                    value: text,
                };
            }
        });

        return state.fields;
    }

    function buildCssPath(el) {
        if (el.id)
            return '#' + el.id;

        const path = [];
        while (el && el !== document.body) {
            let selector = el.tagName.toLowerCase();

            if (el.id) {
                selector += '#' + el.id;
                path.unshift(selector);
                break;
            }

            // Se tem classe
            if (el.className) {
                const classes = el.className
                    .split(' ')
                    .filter(c => c && !c.startsWith('admin-') && c !== 'edited')
                    .slice(0, 2)
                    .join('.');

                if (classes) {
                    selector += '.' + classes;
                }
            }

            // Conte o índice entre irmãos
            const parent = el.parentElement;
            if (parent) {
                const siblings = Array.from(parent.children).filter(c => c.tagName === el.tagName);
                if (siblings.length > 1) {
                    const index = siblings.indexOf(el) + 1;
                    selector += `:nth-of-type(${index})`;
                }
            }

            path.unshift(selector);
            el = el.parentElement;
        }

        return path.join(' > ');
    }

    // ========== OVERLAY & INTERACTION ==========
    function createAdminUI() {
        // Injetar CSS para overlay
        const style = document.createElement('style');
        style.textContent = `
            .admin-overlay {
                position: absolute;
                background: rgba(59, 130, 246, 0.1);
                border: 2px solid #3b82f6;
                cursor: pointer;
                transition: all 0.2s;
                z-index: 9998;
            }
            
            .admin-overlay:hover {
                background: rgba(59, 130, 246, 0.2);
                border-color: #2563eb;
            }
            
            .admin-overlay.selected {
                background: rgba(16, 185, 129, 0.2);
                border-color: #10b981;
            }
            
            .admin-label {
                position: fixed;
                padding: 4px 8px;
                background: #3b82f6;
                color: white;
                font-size: 11px;
                border-radius: 3px;
                font-weight: 600;
                z-index: 9999;
                pointer-events: none;
            }
            
            body.admin-mode {
                outline: 2px dashed rgba(59, 130, 246, 0.3) inset;
            }
        `;
        document.head.appendChild(style);

        // Marcar body como admin
        document.body.classList.add('admin-mode');

        // Adicionar event listeners aos elementos
        document.addEventListener('click', handleElementClick);
        document.addEventListener('mouseover', handleElementHover);
        document.addEventListener('mouseleave', handleElementLeave);
    }

    function handleElementClick(e) {
        if (e.target.closest('[data-cms], .cms-text, h1, h2, h3, h4, h5, h6, p')) {
            e.preventDefault();
            e.stopPropagation();

            const el = e.target.closest('[data-cms], .cms-text, h1, h2, h3, h4, h5, h6, p');
            selectElement(el);
        }
    }

    function handleElementHover(e) {
        const el = e.target.closest('[data-cms], .cms-text, h1, h2, h3, h4, h5, h6, p');
        if (el && el !== state.selectedElement) {
            showOverlay(el);
        }
    }

    function handleElementLeave(e) {
        const el = e.target.closest('[data-cms], .cms-text, h1, h2, h3, h4, h5, h6, p');
        if (el) {
            hideOverlay(el);
        }
    }

    function selectElement(el) {
        state.selectedElement = el;
        const selector = buildCssPath(el);
        const value = el.textContent?.trim() || '';

        // Notificar painel
        window.parent.postMessage({
            type: 'FIELD_CLICKED',
            payload: {
                name: el.tagName.toLowerCase(),
                selector,
                value,
            },
        }, '*');

        // Destacar visualmente
        el.style.outline = '2px solid #10b981';
    }

    function showOverlay(el) {
        if (el.classList.contains('admin-overlay')) return;

        el.classList.add('admin-overlay');
    }

    function hideOverlay(el) {
        if (el === state.selectedElement) return;
        el.classList.remove('admin-overlay');
    }

    // ========== APPLY OVERRIDES ==========
    function applyOverrides(overrides) {
        Object.entries(overrides).forEach(([selector, override]) => {
            try {
                const el = document.querySelector(selector);
                if (el) {
                    el.textContent = override.newValue;
                    el.classList.add('admin-edited');
                    el.style.borderBottom = '2px solid #f59e0b';
                }
            } catch (e) {
                console.warn(`Erro ao aplicar override para ${selector}:`, e);
            }
        });
    }

    function loadAndApplyOverrides() {
        try {
            const stored = localStorage.getItem(HOOK_CONFIG.storageKey);
            state.overrides = stored ? JSON.parse(stored) : {};
            applyOverrides(state.overrides);
        } catch (e) {
            console.error('Erro ao carregar overrides:', e);
        }
    }

    // ========== MESSAGE HANDLER ==========
    window.addEventListener('message', (event) => {
        const { type, payload } = event.data;

        if (type === 'SYNC_OVERRIDES') {
            state.overrides = payload;
            loadAndApplyOverrides();
        }

        if (type === 'REQUEST_HTML') {
            const html = document.documentElement.outerHTML;
            event.source.postMessage({
                type: 'HTML_EXPORT',
                payload: html,
            }, event.origin);
        }

        if (type === 'REQUEST_CATALOG') {
            event.source.postMessage({
                type: 'FIELDS_CATALOG',
                payload: state.fields,
            }, event.origin);
        }
    });

    // ========== INITIALIZATION ==========
    function init() {
        console.log('🎨 CETSADP Admin Hook v6 carregado');

        createAdminUI();
        buildCatalog();
        loadAndApplyOverrides();

        // Notificar painel que o hook está pronto
        window.parent.postMessage({
            type: 'ADMIN_HOOK_READY',
            payload: {
                fieldsCount: Object.keys(state.fields).length,
            },
        }, '*');

        // Re-scan quando DOM muda (para sites com conteúdo dinâmico)
        const observer = new MutationObserver((mutations) => {
            // Throttle o rebuild
            clearTimeout(window._rebuildCatalogTimeout);
            window._rebuildCatalogTimeout = setTimeout(() => {
                buildCatalog();
                window.parent.postMessage({
                    type: 'FIELDS_CATALOG',
                    payload: state.fields,
                }, '*');
            }, 1000);
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    // Aguardar DOM estar pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
