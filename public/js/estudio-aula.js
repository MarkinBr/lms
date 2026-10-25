/* Estúdio CETSADP. Sem dependência de framework ou dos scripts antigos. */
const cfg = window.CETSADP_STUDIO;
if (!cfg) throw new Error("Dados da sala não encontrados.");
const $ = (id) => document.getElementById(id);
const make = (tag, cls, text) => {
	const el = document.createElement(tag);
	if (cls) el.className = cls;
	if (text !== undefined) el.textContent = text;
	return el;
};
const storeKey = `cetsadp:estudio:${cfg.userId}:${cfg.lesson.id}`;
const prefix = "CETSADP_ESTUDIO_V1\n";
const storage = {
	get(k) {
		try {
			return JSON.parse(localStorage.getItem(k));
		} catch {
			return null;
		}
	},
	set(k, v) {
		try {
			localStorage.setItem(k, JSON.stringify(v));
			return true;
		} catch {
			return false;
		}
	},
	remove(k) {
		try {
			localStorage.removeItem(k);
		} catch {}
	},
};
function safeUrl(value) {
	try {
		const u = new URL(String(value || ""), location.href);
		return ["http:", "https:"].includes(u.protocol) ||
			(cfg.demo && ["blob:", "file:"].includes(u.protocol))
			? u.href
			: null;
	} catch {
		return null;
	}
}
function mediaUrl(value) {
	if (!value) return null;
	const str = String(value);
	return safeUrl(
		/^(https?:|\/)/i.test(str) || (cfg.demo && /^(blob:|file:)/i.test(str))
			? str
			: "/storage/" + str.replace(/^storage\//, ""),
	);
}
function sanitize(html) {
	if (!window.DOMPurify)
		return String(html || "").replace(
			/[<>&]/g,
			(s) => ({ "<": "&lt;", ">": "&gt;", "&": "&amp;" })[s],
		);
	return window.DOMPurify.sanitize(String(html || ""), {
		USE_PROFILES: { html: true },
		FORBID_TAGS: [
			"style",
			"form",
			"input",
			"button",
			"textarea",
			"select",
			"iframe",
			"video",
			"audio",
		],
		FORBID_ATTR: ["style", "id", "name"],
	});
}
function rich(el, html) {
	el.innerHTML = sanitize(html);
	el.querySelectorAll("a").forEach((a) => {
		a.rel = "noopener noreferrer";
		if (!safeUrl(a.getAttribute("href"))) a.removeAttribute("href");
	});
	el.querySelectorAll("img").forEach((i) => {
		i.loading = "lazy";
		i.decoding = "async";
	});
}
let toastTimer;
function toast(text) {
	$("toast").textContent = text;
	$("toast").hidden = false;
	clearTimeout(toastTimer);
	toastTimer = setTimeout(() => ($("toast").hidden = true), 4500);
}
function stateStatus(text, error = false) {
	$("notes-save-status").textContent = text;
	$("notes-save-status").dataset.error = String(error);
}
async function api(type, data, keepalive = false) {
	if (cfg.demo) {
		await new Promise((r) => setTimeout(r, 140));
		return { success: true };
	}
	const url = cfg.endpoints?.[type];
	if (!url) throw new Error("Integração não disponível.");
	const response = await fetch(url, {
		method: "POST",
		credentials: "same-origin",
		keepalive,
		headers: {
			"Content-Type": "application/json",
			Accept: "application/json",
			"X-CSRF-TOKEN":
				document.querySelector("meta[name=csrf-token]")?.content || "",
		},
		body: JSON.stringify(data),
	});
	let body;
	try {
		body = await response.json();
	} catch {
		throw new Error(
			"Resposta inesperada. Recarregue após exportar seu caderno.",
		);
	}
	if (!response.ok || body.success !== true) {
		const failure = new Error(
			response.status === 419
				? "Sua sessão expirou. Exporte o caderno e entre novamente."
				: body.message || `Não foi possível salvar (${response.status}).`,
		);
		failure.status = response.status;
		throw failure;
	}
	return body;
}
function parseNotebook(raw) {
	if (typeof raw === "string" && raw.startsWith(prefix)) {
		try {
			const d = JSON.parse(raw.slice(prefix.length));
			if (d.version === 1 && typeof d.text === "string")
				return {
					version: 1,
					text: d.text,
					highlights: Array.isArray(d.highlights) ? d.highlights : [],
					anchors: Array.isArray(d.anchors) ? d.anchors : [],
				};
		} catch {}
	}
	return {
		version: 1,
		text: typeof raw === "string" ? raw : "",
		highlights: [],
		anchors: [],
	};
}
const rawServer = cfg.notebook
	? prefix + JSON.stringify(cfg.notebook)
	: cfg.lesson.notes || "";
let serverRevision = Number(cfg.revision) || 0;
let serverNotes = cfg.lesson.notes || "";
let notebook = parseNotebook(rawServer),
	dirty = false,
	saving = false,
	saveTimer,
	conflictPending = false,
	draftConflict = null,
	baseServer = rawServer;
const initialDraft = storage.get(storeKey + ":draft");
if (initialDraft?.pending && initialDraft.state?.version === 1) {
	if (initialDraft.base === rawServer) {
		notebook = parseNotebook(prefix + JSON.stringify(initialDraft.state));
		dirty = true;
	} else {
		draftConflict = initialDraft;
		conflictPending = true;
	}
}
function packed() {
	return prefix + JSON.stringify(notebook);
}
function cacheDraft() {
	if (!dirty) {
		storage.remove(storeKey + ":draft");
		return true;
	}
	return storage.set(storeKey + ":draft", {
		base: baseServer,
		state: notebook,
		pending: dirty,
		updated: Date.now(),
	});
}
function capacity() {
	const n = packed().length;
	$("notes-capacity").textContent =
		`${notebook.text.length.toLocaleString("pt-BR")} / 10.000 caracteres · ${notebook.highlights.length} grifos`;
	return n;
}
function changed() {
	dirty = true;
	const local = cacheDraft();
	stateStatus(
		local
			? "Rascunho neste navegador"
			: "Não salvo: armazenamento local indisponível",
		!local,
	);
	capacity();
	clearTimeout(saveTimer);
	if (conflictPending) {
		stateStatus(
			"Conflito de versões · exporte e recarregue para comparar.",
			true,
		);
		return;
	}
	saveTimer = setTimeout(saveNotebook, 1100);
}
async function saveNotebook(keepalive = false) {
	clearTimeout(saveTimer);
	if (!dirty || saving || conflictPending) return;
	const snapshot = packed();
	if (
		notebook.text.length > 10000 ||
		new TextEncoder().encode(snapshot).length > 450000
	) {
		stateStatus("Limite atingido. Exporte ou reduza o caderno.", true);
		return;
	}
	if (!navigator.onLine && !cfg.demo) {
		stateStatus("Sem conexão · rascunho local", true);
		return;
	}
	saving = true;
	$("save-notes").disabled = true;
	stateStatus(
		cfg.demo ? "Salvando na demonstração…" : "Salvando na sua conta…",
	);
	try {
		const result = await api(
			"notebook",
			{
				revision: serverRevision,
				baseNotes: serverNotes,
				notebook: JSON.parse(snapshot.slice(prefix.length)),
			},
			keepalive,
		);
		serverRevision = result.revision;
		serverNotes = JSON.parse(snapshot.slice(prefix.length)).text;
		if (cfg.demo) storage.set(storeKey + ":demo-notes", snapshot);
		baseServer = snapshot;
		dirty = packed() !== snapshot;
		cacheDraft();
		stateStatus(
			dirty
				? "Há alterações novas"
				: cfg.demo
					? "Salvo neste navegador · demonstração"
					: "Salvo na sua conta",
		);
	} catch (error) {
		stateStatus(error.message, true);
		if (error.status === 409) {
			conflictPending = true;
			toast(error.message);
		}
		cacheDraft();
	} finally {
		saving = false;
		$("save-notes").disabled = false;
		if (dirty && packed() !== snapshot)
			saveTimer = setTimeout(saveNotebook, 700);
	}
}
function downloadNotebook(value = notebook) {
	const lines = [
		cfg.course.title,
		cfg.lesson.title,
		"",
		"ANOTAÇÕES",
		value.text,
		"",
		"GRIFOS",
		...value.highlights.map(
			(h) =>
				`${h.source === "pdf" ? "PDF · página " + h.page : "Leitura"}\n${h.text}\n`,
		),
		"",
		"REFERÊNCIAS",
		...value.anchors.map((a) => a.label),
	];
	const blob = new Blob(["\ufeff" + lines.join("\n")], {
		type: "text/plain;charset=utf-8",
	});
	const url = URL.createObjectURL(blob);
	const a = make("a");
	a.href = url;
	a.download = `caderno-aula-${cfg.lesson.id}.txt`;
	a.click();
	setTimeout(() => URL.revokeObjectURL(url), 3000);
}
if (cfg.demo) {
	notebook.highlights.forEach((h) => {
		if (h.source === "pdf") h.url = cfg.lesson.pdf;
	});
	notebook.anchors.forEach((a) => {
		if (a.kind === "pdf") a.url = cfg.lesson.pdf;
	});
}
$("notes-editor").value = notebook.text;
$("notes-editor").addEventListener("input", (e) => {
	notebook.text = e.target.value;
	changed();
});
$("save-notes").addEventListener("click", () => {
	if (!dirty) {
		toast("Nenhuma alteração pendente.");
		return;
	}
	saveNotebook();
});
$("export-notes").addEventListener("click", () => downloadNotebook());
window.addEventListener("online", () => {
	if (dirty) saveNotebook();
});
window.addEventListener("offline", () => {
	if (dirty) stateStatus("Sem conexão · rascunho local", true);
});
window.addEventListener("beforeunload", (e) => {
	if (dirty) {
		cacheDraft();
		e.preventDefault();
		e.returnValue = "";
	}
});
window.addEventListener("pagehide", () => {
	cacheDraft();
	if (dirty && !saving) saveNotebook(true);
});
if (draftConflict) {
	$("draft-conflict").showModal();
	$("draft-conflict").addEventListener("cancel", (e) => e.preventDefault());
	$("draft-export").onclick = () => downloadNotebook(draftConflict.state);
	$("draft-server").onclick = () => {
		conflictPending = false;
		storage.remove(storeKey + ":draft");
		$("draft-conflict").close();
	};
	$("draft-restore").onclick = () => {
		notebook = parseNotebook(prefix + JSON.stringify(draftConflict.state));
		$("notes-editor").value = notebook.text;
		conflictPending = false;
		$("draft-conflict").close();
		refreshNotebook();
		changed();
	};
}

// Cabeçalho, navegação por teclado e painéis móveis.
$("back-course").href = safeUrl(cfg.course.url) || "#";
$("course-title").textContent = cfg.course.title;
$("module-title").textContent = cfg.lesson.module;
$("lesson-title").textContent = cfg.lesson.title;
$("lesson-description").textContent =
	cfg.lesson.description ||
	"Acompanhe os materiais desta aula e registre suas reflexões no caderno.";
$("lesson-duration").textContent = cfg.lesson.duration
	? `◷ ${cfg.lesson.duration} min`
	: "Estude no seu ritmo";
$("lesson-format").textContent = "Caderno pessoal disponível";
$("demo-banner").hidden = !cfg.demo;
for (const objective of cfg.lesson.objectives || [])
	$("lesson-objectives").append(
		make(
			"li",
			"",
			typeof objective === "string" ? objective : String(objective.texto || ""),
		),
	);
rich(
	$("lesson-text"),
	cfg.lesson.html ||
		"<p>Esta aula não possui texto complementar. Confira a videoaula e os materiais disponíveis.</p>",
);
const originalReadingText = $("lesson-text").textContent;
let totalDone = Number(cfg.course.done) || 0,
	completed = !!cfg.lesson.done;
function courseProgress() {
	const percent = cfg.course.total
		? Math.round((totalDone / cfg.course.total) * 100)
		: 0;
	$("course-count").textContent =
		`${totalDone} de ${cfg.course.total} aulas concluídas`;
	$("course-percent").textContent = percent + "%";
	$("course-progress").value = percent;
}
courseProgress();
const allLessons = (cfg.modules || []).flatMap((m) => m.lessons);
const lessonIndex = allLessons.findIndex(
	(a) => String(a.id) === String(cfg.lesson.id),
);
$("lesson-number").textContent =
	lessonIndex >= 0
		? `Aula ${String(lessonIndex + 1).padStart(2, "0")}`
		: "Sala de aula";
function renderModules(term = "") {
	$("module-list").replaceChildren();
	let matches = 0;
	(cfg.modules || []).forEach((m, index) => {
		const details = make("details", "module");
		const current = m.lessons.some(
			(a) => String(a.id) === String(cfg.lesson.id),
		);
		details.open = current || !!term;
		const summary = make("summary");
		const summaryLabel = make("span");
		summaryLabel.append(
			make(
				"span",
				"module-label",
				`MÓDULO ${String(index + 1).padStart(2, "0")}`,
			),
			document.createTextNode(m.title),
		);
		summary.append(summaryLabel);
		details.append(summary);
		m.lessons.forEach((a) => {
			if (
				term &&
				!a.title
					.toLocaleLowerCase("pt-BR")
					.includes(term.toLocaleLowerCase("pt-BR"))
			)
				return;
			matches++;
			const active = String(a.id) === String(cfg.lesson.id);
			const row = make(
				a.available ? "a" : "div",
				"lesson-item" +
					(active ? " current" : "") +
					(a.done ? " done" : "") +
					(!a.available ? " unavailable" : ""),
			);
			if (a.available) row.href = safeUrl(a.url) || "#";
			else row.setAttribute("aria-label", a.title + " — aula indisponível");
			if (active) row.setAttribute("aria-current", "page");
			row.dataset.lessonId = String(a.id);
			row.append(
				make(
					"span",
					"lesson-bullet",
					!a.available ? "⊘" : a.done ? "✓" : active ? "▷" : "·",
				),
			);
			const text = make("span");
			text.append(
				make("span", "lesson-item-title", a.title),
				make(
					"small",
					"",
					!a.available
						? "Ainda não liberada"
						: active
							? "Você está aqui"
							: a.duration
								? `${a.duration} min`
								: "Disponível",
				),
			);
			row.append(text);
			details.append(row);
		});
		if (details.children.length > 1) $("module-list").append(details);
	});
	$("search-empty").hidden = matches > 0;
}
renderModules();
$("lesson-search").oninput = (e) => renderModules(e.target.value.trim());
// Painéis independentes: o caderno começa recolhido para priorizar a aula.
const panelPreference = storage.get("cetsadp:estudio:panels") || {};
document.body.classList.toggle(
	"notebook-closed",
	panelPreference.notes !== true,
);
document.body.classList.toggle(
	"course-closed",
	panelPreference.course === false,
);
function syncPanelControls() {
	const mobile = innerWidth <= 1180;
	const focus = document.body.classList.contains("focus-mode");
	for (const [name, id, closed] of [
		["course", "course-toggle", "course-closed"],
		["notes", "notebook-toggle", "notebook-closed"],
	]) {
		const open = mobile
			? document.body.classList.contains("mobile-" + name)
			: !focus && !document.body.classList.contains(closed);
		$(id).setAttribute("aria-pressed", String(open));
		$(id).setAttribute("aria-expanded", String(open));
	}
	$("focus-toggle").setAttribute("aria-pressed", String(focus));
	$("focus-toggle").lastElementChild.textContent = focus
		? "Sair do foco"
		: "Modo foco";
}
function mobilePanel(name) {
	document.body.classList.toggle("mobile-course", name === "course");
	document.body.classList.toggle("mobile-notes", name === "notes");
	document.querySelectorAll("[data-mobile]").forEach((b) => {
		if (b.dataset.mobile === name) b.setAttribute("aria-current", "page");
		else b.removeAttribute("aria-current");
		if (b.hasAttribute("aria-expanded"))
			b.setAttribute("aria-expanded", String(b.dataset.mobile === name));
	});
	syncPanelControls();
}
function togglePanel(name) {
	if (innerWidth <= 1180) {
		mobilePanel(
			document.body.classList.contains("mobile-" + name) ? "lesson" : name,
		);
	} else {
		const wasFocused = document.body.classList.contains("focus-mode");
		document.body.classList.remove("focus-mode");
		const closed = name === "notes" ? "notebook-closed" : "course-closed";
		if (wasFocused) document.body.classList.remove(closed);
		else document.body.classList.toggle(closed);
		storage.set("cetsadp:estudio:panels", {
			course: !document.body.classList.contains("course-closed"),
			notes: !document.body.classList.contains("notebook-closed"),
		});
		syncPanelControls();
		if (pdfDoc) requestPdfRender();
	}
}
document.querySelectorAll("[data-mobile]").forEach((b) => {
	b.onclick = () => {
		const name = b.dataset.mobile;
		mobilePanel(
			name !== "lesson" && document.body.classList.contains("mobile-" + name)
				? "lesson"
				: name,
		);
		window.scrollTo(0, 0);
	};
});
$("course-toggle").onclick = () => togglePanel("course");
$("notebook-toggle").onclick = () => togglePanel("notes");
$("course-close").onclick = () => {
	togglePanel("course");
	(innerWidth <= 600
		? document.querySelector('[data-mobile="course"]')
		: $("course-toggle")
	).focus();
};
$("notes-close").onclick = () => {
	togglePanel("notes");
	$("notebook-toggle").focus();
};
$("focus-toggle").onclick = () => {
	document.body.classList.toggle("focus-mode");
	syncPanelControls();
	if (pdfDoc) requestPdfRender();
};
syncPanelControls();
function theme(value) {
	document.documentElement.dataset.theme = value;
	$("theme-toggle").setAttribute("aria-pressed", String(value === "dark"));
	$("theme-toggle").setAttribute(
		"aria-label",
		value === "dark" ? "Ativar tema claro" : "Ativar tema escuro",
	);
}
theme(storage.get("cetsadp:estudio:theme") || "light");
$("theme-toggle").onclick = () => {
	const next =
		document.documentElement.dataset.theme === "dark" ? "light" : "dark";
	theme(next);
	storage.set("cetsadp:estudio:theme", next);
};
function bindTabs(selector, attribute, callback) {
	const tabs = [...document.querySelectorAll(selector)];
	const activate = (button) => {
		tabs.forEach((t) => {
			const selected = t === button;
			t.setAttribute("aria-selected", String(selected));
			t.tabIndex = selected ? 0 : -1;
			$(t.getAttribute("aria-controls")).hidden = !selected;
		});
		callback?.(button.dataset[attribute]);
	};
	tabs.forEach((t) => {
		t.onclick = () => activate(t);
		t.addEventListener("keydown", (e) => {
			const available = tabs.filter((b) => !b.hidden);
			let i = available.indexOf(t);
			if (e.key === "ArrowRight") i = (i + 1) % available.length;
			else if (e.key === "ArrowLeft")
				i = (i - 1 + available.length) % available.length;
			else if (e.key === "Home") i = 0;
			else if (e.key === "End") i = available.length - 1;
			else return;
			e.preventDefault();
			available[i].focus();
			activate(available[i]);
		});
	});
	return (name) => {
		const t = tabs.find((t) => t.dataset[attribute] === name);
		if (t && !t.hidden) activate(t);
	};
}
const showDetail = bindTabs("[data-detail]", "detail");
const showNotebook = bindTabs("[data-notebook]", "notebook");
let activeFormat = "video";
let studyPinned = false;
let floatingVideo = false;
let nativePip = false;
let lastMaterial = "text";
function syncStudyLayout() {
	const simultaneous =
		activeFormat !== "video" && (studyPinned || floatingVideo || nativePip);
	$("panel-video").hidden = activeFormat !== "video" && !simultaneous;
	$("panel-video").setAttribute("role", simultaneous ? "region" : "tabpanel");
	document.body.classList.toggle(
		"study-pinned",
		studyPinned && activeFormat !== "video",
	);
	document.body.classList.toggle("floating-video", floatingVideo);
	$("video-return").hidden = !floatingVideo;
	$("video-unpin").hidden = !studyPinned;
	$("video-pin").setAttribute("aria-pressed", String(studyPinned));
	$("video-pin").textContent = studyPinned
		? "✓ Vídeo + material"
		: "▣ Vídeo + material";
	$("video-pip").setAttribute(
		"aria-pressed",
		String(floatingVideo || nativePip),
	);
	$("video-pip").textContent =
		floatingVideo || nativePip ? "Fechar flutuante" : "▧ Vídeo flutuante";
}
const showFormat = bindTabs("[data-tab]", "tab", (name) => {
	activeFormat = name;
	if (name !== "video") lastMaterial = name;
	syncStudyLayout();
	$("selection-tools").hidden = true;
	if (name === "pdf") initPdf();
});
for (const [id, url] of [
	["previous-lesson", cfg.previous],
	["next-lesson", cfg.next],
]) {
	$(id).hidden = !url;
	if (url) $(id).href = safeUrl(url) || "#";
}
function markCompleted() {
	completed = true;
	$("lesson-status").textContent = "✓ Aula concluída";
	$("lesson-status").classList.add("is-done");
	$("complete-lesson").hidden = true;
	$("next-lesson").hidden = !cfg.next;
}
if (completed) markCompleted();
else $("next-lesson").hidden = true;
$("complete-lesson").onclick = async () => {
	const button = $("complete-lesson");
	button.disabled = true;
	button.textContent = "Registrando…";
	try {
		await api("complete", {});
		markCompleted();
		totalDone = Math.min(cfg.course.total, totalDone + 1);
		courseProgress();
		allLessons.forEach((a) => {
			if (String(a.id) === String(cfg.lesson.id)) a.done = true;
		});
		renderModules($("lesson-search").value);
		toast(
			cfg.demo
				? "Aula concluída nesta demonstração."
				: "Aula concluída. Parabéns por mais um passo!",
		);
	} catch (e) {
		toast(e.message);
		button.disabled = false;
		button.textContent = "✓ Concluir aula";
	}
};

// Vídeos: controles nativos, sem overlays que atrapalhem o toque.
let video = null,
	providerPlaying = false,
	provider = null,
	videoReady = false,
	lastProgress = 0,
	pendingResume = 0,
	hasOfferedResume = false;
function stamp(seconds) {
	seconds = Math.max(0, Math.floor(Number(seconds) || 0));
	const hours = Math.floor(seconds / 3600);
	const minutes = Math.floor(seconds / 60);
	const tail = String(seconds % 60).padStart(2, "0");
	return hours
		? `${hours}:${String(minutes % 60).padStart(2, "0")}:${tail}`
		: `${minutes}:${tail}`;
}
function script(src) {
	return new Promise((resolve, reject) => {
		const s = make("script");
		s.src = src;
		s.onload = resolve;
		s.onerror = reject;
		document.head.append(s);
	});
}
function offerResume(duration) {
	if (hasOfferedResume) return;
	hasOfferedResume = true;
	const previous = storage.get(storeKey + ":video");
	if (
		previous?.url === cfg.lesson.video &&
		previous.time > 10 &&
		previous.time < duration - 10
	) {
		pendingResume = previous.time;
		$("resume-text").textContent = `Você parou em ${stamp(pendingResume)}.`;
		$("resume-banner").hidden = false;
	}
}
function trackVideo(seconds, duration) {
	if (!Number.isFinite(duration) || duration <= 0) return;
	offerResume(duration);
	storage.set(storeKey + ":video", { url: cfg.lesson.video, time: seconds });
	const percent = Math.min(99, Math.floor((seconds / duration) * 100));
	if (percent >= lastProgress + 10 && !completed) {
		lastProgress = percent;
		api("progress", { progresso: percent }).catch(() => {});
	}
}
function activateVideoFeatures() {
	videoReady = true;
	$("video-host").setAttribute("aria-busy", "false");
	$("video-note").disabled = false;
	document.querySelectorAll(".transcript-cue").forEach((b) => {
		b.disabled = false;
	});
	$("video-hint").textContent =
		"Seu ponto de parada é lembrado neste navegador.";
}
async function setupVideo() {
	const url = mediaUrl(cfg.lesson.video);
	$("video-host").setAttribute("aria-busy", String(!!url));
	if (!url) {
		const empty = make("div", "empty-player");
		empty.append(
			make("div", "player-emblem", "▷"),
			make(
				"h2",
				"",
				cfg.demo ? "Seu espaço para aprender" : "Sem vídeo nesta aula",
			),
			make(
				"p",
				"",
				cfg.demo
					? "Vídeo, leitura e suas ideias. Tudo no mesmo lugar."
					: "Explore os materiais e a leitura disponíveis.",
			),
		);
		$("video-host").append(empty);
		return;
	}
	const host = new URL(url).hostname;
	if (/(^|\.)(youtube\.com|youtu\.be)$/.test(host)) {
		const parsed = new URL(url);
		const id = host.endsWith("youtu.be")
			? parsed.pathname.slice(1).split("/")[0]
			: parsed.searchParams.get("v") ||
				parsed.pathname.split("/").filter(Boolean).at(-1);
		if (!/^[\w-]{11}$/.test(id || "")) {
			videoFallback(url);
			return;
		}
		const slot = make("div");
		slot.id = "estudio-youtube";
		$("video-host").append(slot);
		const frame = make("iframe");
		frame.src = `https://www.youtube.com/embed/${id}?enablejsapi=1&origin=${encodeURIComponent(location.origin)}&rel=0&playsinline=1&cc_lang_pref=pt&cc_load_policy=1`;
		frame.title = "Videoaula: " + cfg.lesson.title;
		frame.allow =
			"accelerometer; autoplay; encrypted-media; picture-in-picture; fullscreen";
		frame.allowFullscreen = true;
		slot.append(frame);
		try {
			if (!window.YT?.Player) {
				await script("https://www.youtube.com/iframe_api");
				await new Promise((resolve, reject) => {
					let tries = 0;
					const t = setInterval(() => {
						if (window.YT?.Player) {
							clearInterval(t);
							resolve();
						} else if (++tries > 150) {
							clearInterval(t);
							reject(new Error());
						}
					}, 100);
				});
			}
			provider = new window.YT.Player(frame, {
				events: {
					onReady() {
						activateVideoFeatures();
						offerResume(provider.getDuration());
					},
					onStateChange(event) {
						providerPlaying = event.data === 1;
					},
					onError() {
						videoFallbackNotice(url);
					},
				},
			});
			setInterval(() => {
				if (videoReady && provider?.getPlayerState?.() === 1)
					trackVideo(provider.getCurrentTime(), provider.getDuration());
			}, 5000);
		} catch {
			videoFallbackNotice(url);
		}
	} else if (/(^|\.)vimeo\.com$/.test(host)) {
		const parsed = new URL(url);
		const parts = parsed.pathname.split("/").filter(Boolean);
		const id = parts.find((p) => /^\d+$/.test(p));
		if (!id) {
			videoFallback(url);
			return;
		}
		const iframe = make("iframe");
		const hash = parsed.searchParams.get("h") || parts[parts.indexOf(id) + 1];
		iframe.src = `https://player.vimeo.com/video/${id}${hash ? "?h=" + encodeURIComponent(hash) : ""}`;
		iframe.allow = "autoplay; fullscreen; picture-in-picture";
		iframe.allowFullscreen = true;
		iframe.title = "Videoaula: " + cfg.lesson.title;
		$("video-host").append(iframe);
		try {
			if (!window.Vimeo?.Player)
				await script("https://player.vimeo.com/api/player.js");
			provider = new window.Vimeo.Player(iframe);
			await provider.ready();
			activateVideoFeatures();
			provider.on("timeupdate", (d) => trackVideo(d.seconds, d.duration));
			provider.on("play", () => {
				providerPlaying = true;
			});
			provider.on("pause", () => {
				providerPlaying = false;
			});
			provider.on("ended", () => {
				providerPlaying = false;
			});
			offerResume(await provider.getDuration());
		} catch {
			videoFallbackNotice(url);
		}
	} else {
		video = make("video");
		video.controls = true;
		video.playsInline = true;
		video.preload = "metadata";
		video.src = url;
		video.setAttribute("aria-label", "Videoaula: " + cfg.lesson.title);
		$("video-host").append(video);
		video.addEventListener("loadedmetadata", () => {
			activateVideoFeatures();
			offerResume(video.duration);
		});
		let lastTick = 0;
		video.addEventListener("timeupdate", () => {
			if (Date.now() - lastTick > 5000) {
				lastTick = Date.now();
				trackVideo(video.currentTime, video.duration);
			}
		});
		video.addEventListener("error", () => videoFallbackNotice(url));
		configureCaptions();
		video.addEventListener("enterpictureinpicture", () => {
			nativePip = true;
			floatingVideo = false;
			syncStudyLayout();
			toast("Vídeo aberto em janela flutuante.");
		});
		video.addEventListener("leavepictureinpicture", () => {
			nativePip = false;
			syncStudyLayout();
		});
	}
}
function videoFallback(url) {
	const a = make("a", "button", "Abrir vídeo em outra página ↗");
	a.href = url;
	a.target = "_blank";
	a.rel = "noopener noreferrer";
	$("video-host").append(a);
}
function videoFallbackNotice(url) {
	$("video-host").setAttribute("aria-busy", "false");
	$("video-error").replaceChildren(
		document.createTextNode(
			"Não foi possível conectar os recursos avançados do vídeo. ",
		),
	);
	const a = make("a", "", "Abrir vídeo em outra página ↗");
	a.href = url;
	a.target = "_blank";
	a.rel = "noopener noreferrer";
	$("video-error").append(a);
	$("video-error").hidden = false;
}
async function videoTime() {
	if (video) return video.currentTime;
	if (provider?.getCurrentTime) return await provider.getCurrentTime();
	return 0;
}
async function seek(seconds) {
	try {
		if (video) video.currentTime = seconds;
		else if (provider?.seekTo) provider.seekTo(seconds, true);
		else if (provider?.setCurrentTime) await provider.setCurrentTime(seconds);
	} catch {
		toast("Não foi possível voltar a esse momento.");
	}
}
$("resume-video").onclick = () => {
	seek(pendingResume);
	$("resume-banner").hidden = true;
};
$("video-note").onclick = async () => {
	const time = await videoTime();
	addAnchor({
		kind: "video",
		time: Math.floor(time),
		label: "Vídeo · " + stamp(time),
	});
};
// Mantém o mesmo elemento de vídeo ao alternar leitura, PDF ou janela flutuante.
const transcript = cfg.lesson.transcript || { text: "", cues: [] };
let captionTrack = null;
function configureCaptions() {
	if (!video || !transcript.cues?.length || typeof VTTCue === "undefined")
		return;
	captionTrack = video.addTextTrack("captions", "Português", "pt-BR");
	for (const cue of transcript.cues)
		captionTrack.addCue(new VTTCue(cue.start, cue.end, cue.text));
	captionTrack.mode = "hidden";
	$("video-captions").hidden = false;
	$("video-captions").onclick = () => {
		const on = captionTrack.mode !== "showing";
		captionTrack.mode = on ? "showing" : "hidden";
		$("video-captions").setAttribute("aria-pressed", String(on));
	};
	video.textTracks.addEventListener("change", () => {
		$("video-captions").setAttribute(
			"aria-pressed",
			String(captionTrack.mode === "showing"),
		);
	});
}
function openMiniPlayer() {
	studyPinned = false;
	floatingVideo = true;
	syncStudyLayout();
	toast("Miniplayer ativo nesta página. Use “Voltar ao vídeo” para ampliar.");
}
$("video-pin").hidden = !cfg.lesson.video;
$("video-pip").hidden = !cfg.lesson.video;
$("video-pin").onclick = async () => {
	if (nativePip) {
		try {
			await document.exitPictureInPicture();
		} catch {}
	}
	floatingVideo = false;
	studyPinned = !studyPinned;
	if (studyPinned && activeFormat === "video") showFormat(lastMaterial);
	syncStudyLayout();
	if (studyPinned) $("panel-video").scrollIntoView({ block: "start" });
};
$("video-pip").onclick = async () => {
	if (nativePip) {
		try {
			await document.exitPictureInPicture();
		} catch {
			toast("Feche a janela flutuante pelo controle do navegador.");
		}
		return;
	}
	if (floatingVideo) {
		floatingVideo = false;
		syncStudyLayout();
		return;
	}
	studyPinned = false;
	// A solicitação precisa ocorrer diretamente no clique, sem aguardar outra API.
	if (
		video &&
		document.pictureInPictureEnabled &&
		video.readyState > 0 &&
		typeof video.requestPictureInPicture === "function"
	) {
		try {
			await video.requestPictureInPicture();
			nativePip = true;
			syncStudyLayout();
			return;
		} catch {}
	}
	openMiniPlayer();
};
$("video-unpin").onclick = () => {
	studyPinned = false;
	syncStudyLayout();
	$("video-pin").focus();
};
$("video-return").onclick = () => {
	floatingVideo = false;
	syncStudyLayout();
	showFormat("video");
	$("panel-video").scrollIntoView({ block: "start" });
};
// Saída acessível por teclado sem interferir no texto que o aluno está digitando.
document.addEventListener("keydown", (e) => {
	if (e.key === "Escape" && floatingVideo) {
		floatingVideo = false;
		syncStudyLayout();
		$("video-pip").focus();
	}
});
const cueButtons = [];
let currentCueButton = null;
if (transcript.warning) {
	$("transcript-status").textContent = transcript.warning;
} else if (transcript.cues?.length) {
	$("transcript-status").textContent =
		"Toque em um horário para ir ao trecho do vídeo. Você pode manter o vídeo acima da transcrição.";
	$("transcript-search-label").hidden = false;
	for (const cue of transcript.cues) {
		const button = make("button", "transcript-cue");
		button.type = "button";
		button.disabled = !videoReady;
		button.append(
			make("span", "cue-time", stamp(cue.start)),
			make("span", "cue-text", cue.text),
		);
		button.onclick = () => {
			if (cfg.lesson.video && !floatingVideo && !nativePip) {
				studyPinned = true;
				syncStudyLayout();
			}
			seek(cue.start);
		};
		$("transcript-content").append(button);
		cueButtons.push({ button, cue });
	}
	$("transcript-search").oninput = (e) => {
		const query = e.target.value.toLocaleLowerCase("pt-BR");
		let found = 0;
		for (const { button, cue } of cueButtons) {
			button.hidden = !cue.text.toLocaleLowerCase("pt-BR").includes(query);
			if (!button.hidden) found++;
		}
		$("transcript-status").textContent = found
			? `${found} ${found === 1 ? "trecho encontrado" : "trechos encontrados"}. Toque no horário para ir ao vídeo.`
			: "Nenhum trecho encontrado.";
	};
	setInterval(async () => {
		if (activeFormat !== "transcript" || document.hidden || !videoReady) return;
		try {
			const time = await videoTime();
			const current =
				cueButtons.find(({ cue }) => time >= cue.start && time < cue.end)
					?.button || null;
			if (current !== currentCueButton) {
				currentCueButton?.removeAttribute("aria-current");
				current?.setAttribute("aria-current", "true");
				currentCueButton = current;
			}
		} catch {}
	}, 500);
} else if (transcript.text) {
	$("transcript-status").textContent =
		"Transcrição disponibilizada sem marcação de tempo.";
	$("transcript-content").append(
		make("p", "transcript-plain", transcript.text),
	);
} else {
	$("transcript-status").textContent =
		"A transcrição ainda não foi disponibilizada para esta aula. Se o vídeo tiver legendas na plataforma de origem, ative CC no player.";
}
setupVideo().catch((e) => console.warn("Player indisponível", e));

// Biblioteca e leitor PDF carregados apenas ao abrir o material.
const pdfs = [];
function addPdf(url, title) {
	url = mediaUrl(url);
	if (url && !pdfs.some((p) => p.url === url))
		pdfs.push({ url, title: title || "Material em PDF" });
}
if (cfg.lesson.pdf) addPdf(cfg.lesson.pdf, cfg.lesson.pdfTitle);
(cfg.lesson.materials || []).forEach((m) => {
	if (/\.pdf(?:[?#]|$)/i.test(m.url) || String(m.tipo).toLowerCase() === "pdf")
		addPdf(m.url, m.titulo);
});
(cfg.lesson.blocks || []).forEach((b) => {
	if (b.tipo === "pdf") addPdf(b.url || b.pdf_url || b.arquivo, b.titulo);
});
pdfs.forEach((p, i) => {
	const o = make("option", "", p.title);
	o.value = String(i);
	$("pdf-select").append(o);
});
let pdfLib = null,
	pdfDoc = null,
	pdfLoadingTask = null,
	pdfPage = 1,
	pdfIndex = 0,
	pdfZoom = 1,
	pdfRequest = 0,
	renderTask = null,
	renderQueue = Promise.resolve(),
	pdfOpened = false;
function pdfNotice(text) {
	$("pdf-message").textContent = text;
	$("pdf-message").hidden = false;
}
async function initPdf() {
	if (!pdfs.length) {
		pdfNotice("Nenhum PDF cadastrado para esta aula.");
		$("pdf-sheet").hidden = true;
		$("pdf-select").disabled = true;
		return;
	}
	if (!pdfOpened) {
		pdfOpened = true;
		await loadPdf(0);
	} else if (pdfDoc) requestPdfRender();
}
async function loadPdf(index, page = null) {
	pdfIndex = index;
	const item = pdfs[index];
	if (!item) return;
	const request = ++pdfRequest;
	$("pdf-sheet").hidden = true;
	$("pdf-message").hidden = true;
	$("pdf-loading").hidden = false;
	$("pdf-stage").setAttribute("aria-busy", "true");
	$("pdf-external").href = item.url;
	$("pdf-select").value = String(index);
	try {
		renderTask?.cancel();
		if (pdfLoadingTask) await pdfLoadingTask.destroy();
		pdfDoc = null;
		if (!pdfLib) {
			pdfLib = await import(cfg.pdfLibrary);
			pdfLib.GlobalWorkerOptions.workerSrc = cfg.pdfWorker;
		}
		if (request !== pdfRequest) return;
		const remembered = storage.get(storeKey + ":pdf");
		pdfPage = page || (remembered?.url === item.url ? remembered.page : 1);
		pdfZoom = 1;
		const libBase = cfg.demo ? null : new URL(".", cfg.pdfLibrary).href;
		pdfLoadingTask = pdfLib.getDocument({
			url: item.url,
			isEvalSupported: false,
			...(libBase
				? {
						cMapUrl: libBase + "cmaps/",
						cMapPacked: true,
						standardFontDataUrl: libBase + "standard_fonts/",
					}
				: {}),
		});
		const doc = await pdfLoadingTask.promise;
		if (request !== pdfRequest) {
			await doc.destroy();
			return;
		}
		pdfDoc = doc;
		pdfPage = Math.max(1, Math.min(doc.numPages, pdfPage));
		$("pdf-total").textContent = "/ " + doc.numPages;
		$("pdf-page-input").max = String(doc.numPages);
		await requestPdfRender();
	} catch (error) {
		if (request === pdfRequest) {
			pdfNotice(
				"Não foi possível carregar este PDF. Use “Abrir PDF”. Arquivos externos precisam permitir acesso pelo navegador (CORS); PDFs protegidos devem ser abertos na página original.",
			);
			$("pdf-loading").hidden = true;
			$("pdf-stage").setAttribute("aria-busy", "false");
		}
	} finally {
		if (request === pdfRequest) {
			$("pdf-loading").hidden = true;
			$("pdf-stage").setAttribute("aria-busy", "false");
		}
	}
}
function requestPdfRender() {
	renderTask?.cancel();
	renderQueue = renderQueue.catch(() => {}).then(renderPdf);
	return renderQueue;
}
async function renderPdf() {
	if (!pdfDoc || $("panel-pdf").hidden) return;
	$("pdf-stage").setAttribute("aria-busy", "true");
	$("pdf-loading").hidden = false;
	const doc = pdfDoc,
		pageNumber = pdfPage,
		documentIndex = pdfIndex;
	try {
		const page = await doc.getPage(pageNumber);
		if (doc !== pdfDoc) return;
		const base = page.getViewport({ scale: 1 });
		const stage = $("pdf-stage");
		const stageStyle = getComputedStyle(stage);
		const width = Math.max(
			200,
			Math.floor(
				stage.clientWidth -
					parseFloat(stageStyle.paddingLeft) -
					parseFloat(stageStyle.paddingRight),
			),
		);
		const scale = (width / base.width) * pdfZoom;
		const viewport = page.getViewport({ scale });
		const canvas = $("pdf-canvas"),
			sheet = $("pdf-sheet");
		const ratio = Math.min(devicePixelRatio || 1, 2);
		canvas.width = Math.floor(viewport.width * ratio);
		canvas.height = Math.floor(viewport.height * ratio);
		canvas.style.width = viewport.width + "px";
		canvas.style.height = viewport.height + "px";
		sheet.style.width = viewport.width + "px";
		sheet.style.height = viewport.height + "px";
		sheet.style.setProperty("--scale-factor", String(scale));
		sheet.hidden = false;
		renderTask = page.render({
			canvasContext: canvas.getContext("2d"),
			viewport,
			transform: ratio !== 1 ? [ratio, 0, 0, ratio, 0, 0] : null,
		});
		await renderTask.promise;
		const layer = $("pdf-text-layer");
		layer.replaceChildren();
		const textLayer = new pdfLib.TextLayer({
			textContentSource: await page.getTextContent(),
			container: layer,
			viewport,
		});
		await textLayer.render();
		$("pdf-page-input").value = String(pageNumber);
		$("pdf-position").textContent = `Página ${pageNumber} de ${doc.numPages}`;
		$("pdf-prev").disabled = pageNumber <= 1;
		$("pdf-next").disabled = pageNumber >= doc.numPages;
		$("pdf-fit").textContent =
			pdfZoom === 1 ? "Ajustar" : Math.round(pdfZoom * 100) + "%";
		storage.set(storeKey + ":pdf", {
			url: pdfs[documentIndex].url,
			page: pageNumber,
		});
		drawPdfHighlights();
	} catch (error) {
		if (error.name !== "RenderingCancelledException" && doc === pdfDoc)
			pdfNotice(
				"Esta página não pôde ser exibida. Tente outra página ou abra o PDF original.",
			);
	} finally {
		$("pdf-loading").hidden = true;
		$("pdf-stage").setAttribute("aria-busy", "false");
	}
}
function goPage(n) {
	if (!pdfDoc) return;
	pdfPage = Math.max(1, Math.min(pdfDoc.numPages, Math.trunc(n) || 1));
	$("selection-tools").hidden = true;
	requestPdfRender();
	$("pdf-stage").scrollTo(0, 0);
}
$("pdf-prev").onclick = () => goPage(pdfPage - 1);
$("pdf-next").onclick = () => goPage(pdfPage + 1);
$("pdf-page-input").onchange = (e) => goPage(Number(e.target.value));
$("pdf-select").onchange = (e) => loadPdf(Number(e.target.value));
$("pdf-zoom-out").onclick = () => {
	pdfZoom = Math.max(0.7, pdfZoom - 0.2);
	requestPdfRender();
};
$("pdf-zoom-in").onclick = () => {
	pdfZoom = Math.min(2.6, pdfZoom + 0.2);
	requestPdfRender();
};
$("pdf-fit").onclick = () => {
	pdfZoom = 1;
	requestPdfRender();
};
let resizeTimer;
window.addEventListener("resize", () => {
	clearTimeout(resizeTimer);
	resizeTimer = setTimeout(() => {
		syncPanelControls();
		if (pdfDoc && activeFormat === "pdf") requestPdfRender();
	}, 250);
});
$("pdf-page-note").onclick = () => {
	if (!pdfDoc) {
		toast("Abra um PDF para anotar uma página.");
		return;
	}
	addAnchor({
		kind: "pdf",
		url: pdfs[pdfIndex].url,
		page: pdfPage,
		label: `PDF · página ${pdfPage}`,
	});
};

// Grifos: texto e retângulos normalizados persistem no caderno do aluno.
let pendingSelection = null;
function captureSelection() {
	const selection = getSelection();
	if (
		!selection?.rangeCount ||
		selection.isCollapsed ||
		!selection.toString().trim()
	)
		return;
	const range = selection.getRangeAt(0);
	const root =
		activeFormat === "pdf"
			? $("pdf-text-layer")
			: activeFormat === "text"
				? $("lesson-text")
				: null;
	if (
		!root ||
		!root.contains(range.startContainer) ||
		!root.contains(range.endContainer)
	)
		return;
	const text = selection.toString().trim();
	if (text.length > 1400) {
		toast("Selecione um trecho menor para grifar (até 1.400 caracteres).");
		return;
	}
	if (activeFormat === "pdf") {
		const bounds = $("pdf-sheet").getBoundingClientRect();
		const rects = [...range.getClientRects()]
			.filter((r) => r.width > 1 && r.height > 1)
			.map((r) => [
				Math.max(
					0,
					Math.min(
						1,
						Number(((r.left - bounds.left) / bounds.width).toFixed(5)),
					),
				),
				Math.max(
					0,
					Math.min(
						1,
						Number(((r.top - bounds.top) / bounds.height).toFixed(5)),
					),
				),
				Math.max(0, Math.min(1, Number((r.width / bounds.width).toFixed(5)))),
				Math.max(0, Math.min(1, Number((r.height / bounds.height).toFixed(5)))),
			]);
		if (!rects.length) return;
		pendingSelection = {
			source: "pdf",
			url: pdfs[pdfIndex].url,
			page: pdfPage,
			text,
			rects: rects.slice(0, 100),
		};
	} else {
		const before = range.cloneRange();
		before.selectNodeContents(root);
		before.setEnd(range.startContainer, range.startOffset);
		pendingSelection = {
			source: "text",
			text: range.toString(),
			start: before.toString().length,
			end: before.toString().length + range.toString().length,
		};
	}
	$("selection-tools").hidden = false;
}
let selectionTimer;
document.addEventListener("selectionchange", () => {
	clearTimeout(selectionTimer);
	selectionTimer = setTimeout(captureSelection, 220);
});
document.addEventListener("pointerup", () => setTimeout(captureSelection, 20));
document.querySelectorAll("[data-highlight]").forEach((b) => {
	b.addEventListener("pointerdown", (e) => e.preventDefault());
	b.onclick = () => {
		if (!pendingSelection) return;
		const h = {
			...pendingSelection,
			id: globalThis.crypto?.randomUUID?.() || "h" + Date.now(),
			color: b.dataset.highlight,
		};
		notebook.highlights.push(h);
		if (
			notebook.highlights.length > 150 ||
			new TextEncoder().encode(packed()).length > 450000
		) {
			notebook.highlights.pop();
			toast("O caderno atingiu o limite. Exporte ou remova alguns trechos.");
			return;
		}
		$("selection-tools").hidden = true;
		getSelection()?.removeAllRanges();
		pendingSelection = null;
		refreshNotebook();
		changed();
		toast("Grifo adicionado ao seu caderno.");
	};
});
$("selection-cancel").onclick = () => {
	$("selection-tools").hidden = true;
	pendingSelection = null;
	getSelection()?.removeAllRanges();
};
function drawPdfHighlights() {
	const target = $("pdf-highlight-layer");
	target.replaceChildren();
	notebook.highlights
		.filter(
			(h) =>
				h.source === "pdf" &&
				h.url === pdfs[pdfIndex]?.url &&
				h.page === pdfPage,
		)
		.forEach((h) => {
			(h.rects || []).forEach((r) => {
				if (!Array.isArray(r) || r.length !== 4) return;
				const mark = make("span", "pdf-highlight " + h.color);
				mark.style.left = r[0] * 100 + "%";
				mark.style.top = r[1] * 100 + "%";
				mark.style.width = r[2] * 100 + "%";
				mark.style.height = r[3] * 100 + "%";
				target.append(mark);
			});
		});
}
function drawTextHighlights() {
	const root = $("lesson-text");
	rich(
		root,
		cfg.lesson.html ||
			"<p>Esta aula não possui texto complementar. Confira a videoaula e os materiais disponíveis.</p>",
	);
	const marks = notebook.highlights
		.filter(
			(h) =>
				h.source === "text" &&
				Number.isInteger(h.start) &&
				h.start >= 0 &&
				h.end > h.start &&
				originalReadingText.slice(h.start, h.end) === h.text,
		)
		.sort((a, b) => b.start - a.start);
	let boundary = Infinity;
	marks.forEach((h) => {
		if (h.end > boundary) return;
		const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
		let position = 0;
		const pieces = [];
		for (let node = walker.nextNode(); node; node = walker.nextNode()) {
			const end = position + node.textContent.length;
			if (end > h.start && position < h.end)
				pieces.push({
					node,
					start: Math.max(0, h.start - position),
					end: Math.min(node.textContent.length, h.end - position),
				});
			position = end;
		}
		pieces.reverse().forEach((p) => {
			const range = document.createRange();
			range.setStart(p.node, p.start);
			range.setEnd(p.node, p.end);
			const mark = make("mark", h.color);
			mark.dataset.highlightId = h.id;
			range.surroundContents(mark);
		});
		boundary = h.start;
	});
}
async function openHighlight(h) {
	mobilePanel("lesson");
	if (h.source === "pdf") {
		showFormat("pdf");
		const index = pdfs.findIndex((p) => p.url === h.url);
		if (index < 0) {
			toast("Este PDF não está mais vinculado à aula.");
			return;
		}
		if (pdfIndex !== index || !pdfDoc) await loadPdf(index, h.page);
		else goPage(h.page);
	} else {
		showFormat("text");
		const mark = [...$("lesson-text").querySelectorAll("mark")].find(
			(m) => m.dataset.highlightId === h.id,
		);
		if (mark) mark.scrollIntoView({ block: "center" });
		else
			toast("O texto da aula mudou. O trecho continua preservado no caderno.");
	}
}
function refreshNotebook() {
	$("highlight-count").textContent = notebook.highlights.length;
	$("highlights-list").replaceChildren();
	if (!notebook.highlights.length)
		$("highlights-list").append(
			make(
				"p",
				"empty-state",
				"Seus trechos importantes aparecerão aqui. Selecione um texto no PDF ou na leitura e escolha uma cor.",
			),
		);
	notebook.highlights.forEach((h) => {
		const card = make("article", "highlight-card " + h.color);
		const quote = make("button", "quote-link", h.text);
		quote.onclick = () => openHighlight(h);
		const footer = make("footer");
		footer.append(
			make(
				"span",
				"",
				h.source === "pdf" ? `PDF · página ${h.page}` : "Leitura da aula",
			),
		);
		const remove = make("button", "", "Remover");
		remove.setAttribute(
			"aria-label",
			"Remover grifo: " + String(h.text).slice(0, 45),
		);
		remove.onclick = () => {
			notebook.highlights = notebook.highlights.filter((a) => a.id !== h.id);
			refreshNotebook();
			changed();
		};
		footer.append(remove);
		card.append(quote, footer);
		$("highlights-list").append(card);
	});
	$("note-links").replaceChildren();
	notebook.anchors.forEach((a, index) => {
		const button = make("button", "", a.label);
		button.onclick = () => {
			mobilePanel("lesson");
			if (a.kind === "video") {
				showFormat("video");
				seek(a.time);
			} else openHighlight({ source: "pdf", url: a.url, page: a.page });
		};
		const remove = make("button", "", "×");
		remove.setAttribute("aria-label", "Remover referência " + a.label);
		remove.onclick = () => {
			notebook.anchors.splice(index, 1);
			refreshNotebook();
			changed();
		};
		const wrap = make("span");
		wrap.append(button, remove);
		$("note-links").append(wrap);
	});
	drawPdfHighlights();
	drawTextHighlights();
	capacity();
}
function addAnchor(anchor) {
	if (notebook.anchors.length >= 150) {
		toast(
			"Você já tem 150 referências nesta aula. Exporte uma cópia antes de continuar.",
		);
		return;
	}
	anchor.id = crypto.randomUUID();
	notebook.anchors.push(anchor);
	const line = `[${anchor.label}] `;
	notebook.text += (notebook.text ? "\n" : "") + line;
	$("notes-editor").value = notebook.text;
	refreshNotebook();
	changed();
	showNotebook("notes");
	if (innerWidth <= 1180) mobilePanel("notes");
	$("notes-editor").focus();
	$("notes-editor").setSelectionRange(
		notebook.text.length,
		notebook.text.length,
	);
}
refreshNotebook();
if (dirty && !conflictPending) {
	stateStatus("Rascunho recuperado deste navegador");
	saveTimer = setTimeout(saveNotebook, 1500);
}
document.addEventListener("keydown", (e) => {
	if (e.key === "Escape") {
		$("selection-tools").hidden = true;
		mobilePanel("lesson");
	}
});
let fontSize = 18;
$("font-minus").onclick = () => {
	fontSize = Math.max(16, fontSize - 1);
	document.documentElement.style.setProperty("--reader-size", fontSize + "px");
};
$("font-plus").onclick = () => {
	fontSize = Math.min(26, fontSize + 1);
	document.documentElement.style.setProperty("--reader-size", fontSize + "px");
};

// Materiais e blocos usam os campos reais do modelo Aula.
const materials = cfg.lesson.materials || [];
if (!materials.length && !pdfs.length)
	$("materials-list").append(
		make("p", "empty-state", "Não há materiais complementares cadastrados."),
	);
const pdfMaterials = pdfs.map((p) => ({
	url: p.url,
	titulo: p.title,
	tipo: "PDF",
}));
const seenMaterials = new Set();
[...pdfMaterials, ...materials].forEach((m) => {
	const url = mediaUrl(m.url);
	if (!url || seenMaterials.has(url)) return;
	seenMaterials.add(url);
	const item = make("div", "material-item");
	const label = make("div");
	label.append(
		make("strong", "", m.titulo || "Material de apoio"),
		make("small", "", m.tipo || "Material"),
	);
	const link = make("a", "button subtle", "Abrir ↗");
	link.href = url;
	link.target = "_blank";
	link.rel = "noopener noreferrer";
	item.append(label, link);
	$("materials-list").append(item);
});
if (cfg.lesson.audio) {
	const url = mediaUrl(cfg.lesson.audio);
	if (url) {
		$("audio-host").append(
			make("h3", "", cfg.lesson.audioTitle || "Áudio da aula"),
		);
		const audio = make("audio");
		audio.controls = true;
		audio.preload = "none";
		audio.src = url;
		$("audio-host").append(audio);
	}
}
(cfg.lesson.blocks || []).forEach((b) => {
	const container = make("section", "block-content");
	if (b.titulo) container.append(make("h3", "", b.titulo));
	const type = b.tipo || "texto";
	if (["texto", "html", "citacao", "citação"].includes(type)) {
		const content = make(type.startsWith("cita") ? "blockquote" : "div");
		rich(content, b.conteudo || b.texto || b.html || "");
		container.append(content);
	} else if (
		["pdf", "video", "audio", "imagem", "arquivo", "link"].includes(type)
	) {
		const url = mediaUrl(b.url || b[`${type}_url`] || b.arquivo || b.conteudo);
		if (url) {
			if (type === "imagem") {
				const img = make("img");
				img.src = url;
				img.alt = b.alt || b.titulo || "Imagem de apoio da aula";
				img.loading = "lazy";
				container.append(img);
			} else if (type === "audio") {
				const audio = make("audio");
				audio.src = url;
				audio.controls = true;
				audio.preload = "none";
				container.append(audio);
			} else {
				const a = make(
					"a",
					"button",
					type === "pdf" ? "Ler PDF" : "Abrir conteúdo ↗",
				);
				a.href = url;
				a.target = "_blank";
				a.rel = "noopener noreferrer";
				if (type === "pdf") {
					a.onclick = (e) => {
						e.preventDefault();
						mobilePanel("lesson");
						showFormat("pdf");
						const i = pdfs.findIndex((p) => p.url === url);
						if (i >= 0) loadPdf(i);
					};
				}
				container.append(a);
			}
		}
	} else {
		container.append(
			make(
				"p",
				"muted",
				"Este bloco precisa de revisão de compatibilidade: " + type,
			),
		);
		if (b.conteudo || b.texto) {
			const content = make("div");
			rich(content, b.conteudo || b.texto);
			container.append(content);
		}
	}
	$("content-blocks").append(container);
});

// Exercícios compatíveis com questões do modelo e com a estrutura legada.
const questions = (
	cfg.lesson.questions?.length
		? cfg.lesson.questions
		: cfg.lesson.legacyQuiz || []
).map((q) => {
	const options =
		q.alternativas?.map((a, i) => ({
			label:
				typeof a === "string"
					? a
					: a.texto || a.conteudo || a.letra || String(i + 1),
			value: typeof a === "string" ? String(i) : String(a.letra ?? i),
			correct: !!a.correta,
		})) ||
		q.opcoes?.map((text, i) => ({
			label: text,
			value: String(i),
			correct: i === Number(q.resposta_correta),
		}));
	if (options?.length)
		return {
			prompt: q.enunciado || q.pergunta || q.titulo || "Questão",
			options,
			points: Number(q.pontos) || 1,
			explanation: q.explicacao || q.comentario || "",
		};
	if (["verdadeiro_falso", "certo_errado"].includes(q.tipo)) {
		const labels =
			q.tipo === "certo_errado" ? ["Certo", "Errado"] : ["Verdadeiro", "Falso"];
		return {
			prompt: q.enunciado || q.pergunta || "Questão",
			options: [
				{ label: labels[0], value: true, correct: q.resposta_correta === true },
				{
					label: labels[1],
					value: false,
					correct: q.resposta_correta === false,
				},
			],
			points: Number(q.pontos) || 1,
			explanation: q.explicacao || "",
		};
	}
	return { prompt: q.enunciado || q.pergunta || "Questão", unsupported: true };
});
if (!questions.length)
	$("quiz-form").append(
		make("p", "empty-state", "Nenhum exercício cadastrado para esta aula."),
	);
questions.forEach((q, i) => {
	const field = make("fieldset");
	field.append(make("legend", "", `${i + 1}. ${q.prompt}`));
	if (q.unsupported)
		field.append(
			make("p", "muted", "Este tipo de questão requer adaptação da avaliação."),
		);
	else
		q.options.forEach((o, j) => {
			const label = make("label", "quiz-choice");
			const input = make("input");
			input.type = "radio";
			input.name = "question-" + i;
			input.value = String(j);
			input.required = true;
			label.append(input, document.createTextNode(String(o.label)));
			field.append(label);
		});
	const feedback = make("div", "feedback");
	feedback.id = "quiz-feedback-" + i;
	feedback.hidden = true;
	field.append(feedback);
	$("quiz-form").append(field);
});
if (questions.length && !questions.some((q) => q.unsupported)) {
	const button = make("button", "button primary", "Enviar respostas");
	button.type = "submit";
	$("quiz-form").append(button);
}
$("quiz-form").onsubmit = async (e) => {
	e.preventDefault();
	const button = e.submitter;
	if (!button) return;
	button.disabled = true;
	button.textContent = "Enviando…";
	const answers = {};
	questions.forEach((q, i) => {
		const selected = $("quiz-form").querySelector(
			`input[name="question-${i}"]:checked`,
		);
		const option = q.options[Number(selected.value)];
		answers[i] = option.value;
	});
	try {
		const result = await api("quiz", { answers });
		$("quiz-result").textContent =
			`Resultado: ${result.score}%. Nota mínima: ${cfg.lesson.minScore}%.`;

		if (result.passed && !completed) {
			markCompleted();
			totalDone = Math.min(cfg.course.total, totalDone + 1);
			courseProgress();
		}
	} catch (error) {
		$("quiz-result").textContent = error.message;
	} finally {
		button.disabled = false;
		button.textContent = "Enviar respostas";
	}
};

function commentCard(item, reply = false) {
	const card = make(
		"article",
		"comment-card" + (reply ? " comment-reply" : ""),
	);
	card.append(
		make("strong", "", item.name || "Aluno"),
		make("small", "", item.date || ""),
		make("p", "", item.text || ""),
	);
	(item.replies || []).forEach((r) => {
		card.append(commentCard(r, true));
	});
	return card;
}
(cfg.comments || []).forEach((c) => {
	$("comments-list").append(commentCard(c));
});
if (!(cfg.comments || []).length)
	$("comments-list").append(
		make("p", "muted", "Ainda não há comentários nesta aula."),
	);
if (!cfg.lesson.commentsAllowed) {
	$("comment-form").hidden = true;
	$("comments-list").prepend(
		make("p", "muted", "Comentários desativados para esta aula."),
	);
}
$("comment-form").onsubmit = async (e) => {
	e.preventDefault();
	const input = $("comment-input"),
		text = input.value.trim();
	if (!text) return;
	const button = e.submitter;
	button.disabled = true;
	button.textContent = "Publicando…";
	try {
		await api("comment", { comentario: text });
		$("comment-input").value = "";
		$("comment-feedback").textContent = cfg.demo
			? "Comentário apenas nesta demonstração."
			: "Comentário publicado.";
		if ($("comments-list").firstElementChild?.tagName === "P")
			$("comments-list").replaceChildren();
		$("comments-list").prepend(
			commentCard({
				name: cfg.demo ? "Você · demonstração" : "Você",
				text,
				date: "Agora",
			}),
		);
	} catch (error) {
		$("comment-feedback").textContent = error.message;
	} finally {
		button.disabled = false;
		button.textContent = "Publicar comentário";
	}
};

// Tempo de estudo: apenas aba visível e interação recente, sem concluir pelo relógio.
let lastActivity = Date.now(),
	activeSeconds = 0,
	timeSending = false;
let lastTimeAttempt = 0;
["pointerdown", "keydown", "touchstart", "scroll"].forEach((name) => {
	window.addEventListener(
		name,
		() => {
			lastActivity = Date.now();
		},
		{ passive: true },
	);
});
setInterval(async () => {
	const playing = (video && !video.paused && !video.ended) || providerPlaying;
	if (
		!document.hidden &&
		navigator.onLine &&
		(playing || Date.now() - lastActivity < 90000)
	)
		activeSeconds++;
	if (
		activeSeconds >= 60 &&
		!timeSending &&
		navigator.onLine &&
		!document.hidden &&
		Date.now() - lastTimeAttempt >= 60000
	) {
		lastTimeAttempt = Date.now();
		timeSending = true;
		const minutes = Math.floor(activeSeconds / 60);
		try {
			await api("time", { tempo: minutes });
			activeSeconds -= minutes * 60;
		} catch {
		} finally {
			timeSending = false;
		}
	}
}, 1000);
// Formato inicial respeita o conteúdo, sem esconder abas disponíveis.
if (!cfg.lesson.video) {
	if (pdfs.length) {
		showFormat("pdf");
	} else if (cfg.lesson.html || cfg.lesson.audio || cfg.lesson.blocks?.length) {
		showFormat("text");
	}
}
