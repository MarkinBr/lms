(() => {
	// Os links e formulários continuam funcionais sem JavaScript.
	for (const container of document.querySelectorAll("[data-tabs]")) {
		const list = container.querySelector("[data-tab-list]");
		const links = [...container.querySelectorAll("[data-tab-link]")];
		const panels = [...container.querySelectorAll("[data-tab-panel]")];
		if (!list || links.length !== panels.length || !links.length) continue;

		const panelForHash = (hash) => {
			const id = hash.replace(/^#/, "");
			const target = document.getElementById(id);
			return panels.find((panel) => panel === target || panel.contains(target));
		};

		const activate = (panel, updateHash = false) => {
			if (!panel) return;
			for (const item of panels) item.hidden = item !== panel;
			for (const link of links) {
				const active = link.getAttribute("aria-controls") === panel.id;
				link.setAttribute("aria-selected", String(active));
				link.tabIndex = active ? 0 : -1;
			}
			if (updateHash) history.replaceState(null, "", `#${panel.id}`);
		};

		list.setAttribute("role", "tablist");
		for (const panel of panels) {
			panel.setAttribute("role", "tabpanel");
			panel.tabIndex = 0;
		}
		for (const [index, link] of links.entries()) {
			link.setAttribute("role", "tab");
			link.setAttribute("aria-controls", link.hash.substring(1));
			link.addEventListener("click", (event) => {
				if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey)
					return;
				event.preventDefault();
				activate(panelForHash(link.hash), true);
			});
			link.addEventListener("keydown", (event) => {
				let next;
				if (event.key === "ArrowRight") next = (index + 1) % links.length;
				if (event.key === "ArrowLeft")
					next = (index - 1 + links.length) % links.length;
				if (event.key === "Home") next = 0;
				if (event.key === "End") next = links.length - 1;
				if (next === undefined) return;
				event.preventDefault();
				activate(panelForHash(links[next].hash), true);
				links[next].focus();
			});
		}
		activate(
			panelForHash(location.hash) ||
				panels.find((panel) => panel.id === container.dataset.initialTab) ||
				panels[0],
		);
		window.addEventListener("hashchange", () =>
			activate(panelForHash(location.hash)),
		);
	}

	const templates = {
		acesso:
			"Olá! Aqui é a Secretaria do CETSADP. Podemos orientar você no acesso ao Portal do Aluno. Responda a esta mensagem para combinarmos o atendimento. Nunca solicitamos sua senha permanente.",
		assistido:
			"Olá! Aqui é a Secretaria do CETSADP. Vamos ajudar você a acessar o Portal do Aluno e escolher sua senha. Entre em contato com a equipe para realizar o atendimento assistido com segurança.",
		documentacao:
			"Olá! Aqui é a Secretaria do CETSADP. Entre em contato com a equipe para conferir a documentação da sua inscrição. Envie documentos somente pelos canais institucionais indicados no atendimento.",
	};
	for (const form of document.querySelectorAll("[data-message-form]")) {
		const select = form.querySelector("[data-message-template]");
		const text = form.querySelector("[data-message-text]");
		const field = form.querySelector("[data-template-field]");
		if (!select || !text || !field) continue;
		field.hidden = false;
		select.addEventListener("change", () => {
			if (templates[select.value]) {
				text.value = templates[select.value];
				text.dispatchEvent(new Event("input", { bubbles: true }));
				text.focus();
			}
		});
	}

	// Voltar pelo histórico mantém o rascunho do formulário no navegador.
	for (const link of document.querySelectorAll("[data-review-back]")) {
		link.addEventListener("click", (event) => {
			if (
				event.ctrlKey ||
				event.metaKey ||
				event.shiftKey ||
				event.altKey ||
				!document.referrer
			)
				return;
			const previous = new URL(document.referrer);
			const expected = new URL(link.href);
			if (
				history.length > 1 &&
				previous.origin === expected.origin &&
				previous.pathname === expected.pathname
			) {
				event.preventDefault();
				history.back();
			}
		});
	}

	// Não guardar conteúdo de entrega no cache de navegação da aba.
	const preview = document.querySelector(".sc-message-preview");
	if (preview) {
		window.addEventListener("pagehide", () => {
			preview.value = "";
			preview.textContent = "";
		});
		window.addEventListener("pageshow", (event) => {
			if (!event.persisted) return;
			preview.value =
				"Esta exibição única foi encerrada. Volte à ficha do aluno para continuar o atendimento.";
			const form = preview.closest("section")?.querySelector("form");
			if (form) form.hidden = true;
		});
	}

	const errors = document.querySelector("[data-error-summary]");
	if (errors) errors.focus();
})();
