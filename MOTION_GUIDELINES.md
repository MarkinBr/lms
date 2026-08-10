# Motion, skeleton e loading — CETSADP

Baseado na skill pública **`kylezantos/design-motion-principles`** (não `design-principles` — nome correto confirmado; é uma skill de agente de IA voltada a motion design, compatível com Claude Code/Cursor, com dois modos: *Create* e *Audit*, ponderando três referências: **Emil Kowalski** — restrição e velocidade, **Jakub Krehel** — polimento de produção, **Jhey Tompkins** — experimentação lúdica).

## Antes de tudo: um ajuste importante ao pedido original

O pedido foi "toda interface tem skeleton, lazy loading, animação suave de entrada/saída/carregamento/progresso **em todos os elementos**". A skill que estamos seguindo tem um princípio central que contradiz a parte de "em todos os elementos igualmente":

> **Frequency Gate** — quanto mais frequente a interação, menos motion ela deve ter. Uso raro (mensal): motion expressivo é bem-vindo. Uso diário: motion sutil e rápido. Uso de centenas de vezes/dia: sem animação ou transição instantânea. Ações via teclado: nunca animar.

Aplicado ao CETSADP isso significa: um visitante que vê a página de Teologia uma vez pode ganhar uma entrada mais elaborada nos cards; a secretária que abre `sec-alunos` cem vezes por dia **não** deve levar uma animação de 400ms toda vez que uma linha da tabela atualiza — isso cansa e atrasa o trabalho dela. Segui a skill à risca aqui em vez de literalmente animar tudo igual, porque é isso que a própria referência recomenda para não errar o objetivo (uma interface que "parece polida" na demo e é irritante no uso real).

**O que continua valendo para 100% dos elementos, sem exceção:** skeleton em qualquer carregamento assíncrono, lazy loading fora do viewport inicial, e um estado de loading/progresso visível em qualquer ação que dispare uma requisição. Isso não é "decoração", é comunicar estado — nunca deve ser cortado.

## As três zonas do CETSADP e o peso de cada referência

| Zona | Exemplo de telas | Peso primário | Peso secundário | Duração alvo |
|---|---|---|---|---|
| **Site público** (marketing/institucional) | `site/pages/home`, `teologia`, `sobre`, `eventos`, `noticias`, `professores` | Jakub (polimento) | Jhey (delícia pontual — carrossel de cursos) | 200–450ms |
| **Formulários públicos** | `inscricao`, `contato`, newsletter, logins | Emil (restrição — usuário quer terminar a tarefa) | Jakub (feedback de sucesso/erro polido) | <300ms |
| **Painel secretaria / portal aluno** (uso diário, alta frequência) | `sec-*`, `dash-secretaria`, `dash-aluno`, `aluno/*` | Emil (velocidade) | Jakub (só nos estados vazios/onboarding) | <180ms ou instantâneo |

## Regra de ouro (da própria skill)

> "A melhor animação é aquela que passa despercebida." Se alguém comenta "que animação legal" a cada clique, está chamando atenção demais para produção — motion expressivo fica para o site público, não para o painel de trabalho.

## `prefers-reduced-motion` não é opcional

Toda animação — sem exceção — precisa respeitar a preferência do usuário. Já está implementado em `public/css/site/motion.css` (bloco no topo do arquivo). Isso desliga a animação mas preserva o estado final, para o layout não quebrar.

## Receita de entrada/saída usada aqui

Adaptada da skill (originalmente pensada para Framer Motion, portada para CSS puro já que o site não usa React):

- **Entrada**: opacidade 0→1, `translateY` ~8px→0, leve `blur` 4px→0px ("materializa" em vez de só aparecer)
- **Saída**: mais sutil que a entrada — só opacidade + um `translateY` pequeno e fixo, sem o mesmo deslocamento. Exceção: quando a saída é o próprio ponto (ex.: exclusão de um item) — aí ela pode ser mais explícita.

## O que já foi implementado como piloto

Para não prometer 23 páginas reescritas de uma vez (isso vira uma issue própria, ver `docs/BACKLOG.md#6`), implementei o utilitário completo + um piloto real e funcional:

- `public/css/site/motion.css` — skeleton (shimmer), classes de entrada/saída, `prefers-reduced-motion`, tudo via CSS custom properties (fácil de re-tunar por zona)
- `public/js/site/motion.js` — `IntersectionObserver` para entrada ao rolar a página + lazy loading de imagens com `loading="lazy"` como base e fallback via observer para navegadores antigos
- Aplicado nos cards de curso da Home (`hero-cursos`) como exemplo funcional — é a mesma área que já tinha o bug de `overflow` no badge circular, então serve como prova de conceito num lugar que você já conhece

O resto das 23 páginas + painéis fica nas sub-issues do item 6 do backlog, para não misturar um trabalho de semanas com a configuração inicial.

## Como estender para uma página nova

```html
<div class="cs-skeleton" data-cs-skeleton-for="algum-card">
  <!-- placeholder do mesmo tamanho do conteúdo real -->
</div>
<div class="cs-enter" data-cs-zone="site" hidden>
  <!-- conteúdo real; data-cs-zone controla a duração (site/formulario/painel) -->
</div>
```
O `motion.js` troca o skeleton pelo conteúdo e aplica a classe de entrada assim que o dado estiver pronto (ou assim que entrar no viewport, para itens abaixo da dobra).
