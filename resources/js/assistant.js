
// GIPA Assistant chat widget — progressive enhancement over the Blade partial.
// Talks to POST /assistant/chat and renders grounded answers with citations.
const STORAGE_KEY = 'iomp-assistant-conversation';

function initAssistant() {
	const root = document.querySelector('[data-assistant]');
	if (!root) {
		return;
	}

	const endpoint = root.dataset.endpoint;
	const panel = root.querySelector('[data-assistant-panel]');
	const thread = root.querySelector('[data-assistant-thread]');
	const form = root.querySelector('[data-assistant-form]');
	const input = root.querySelector('[data-assistant-input]');
	const sendButton = root.querySelector('[data-assistant-send]');
	const toggles = root.querySelectorAll('[data-assistant-toggle]');
	const suggestions = root.querySelectorAll('[data-assistant-suggestion]');
	const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

	let conversation = safeGet(STORAGE_KEY);
	let busy = false;

	function setOpen(open) {
		panel.hidden = !open;
		root.classList.toggle('is-open', open);
		toggles.forEach((btn) => btn.setAttribute('aria-expanded', String(open)));
		if (open) {
			window.setTimeout(() => input.focus(), 50);
		} else if (panel.contains(document.activeElement)) {
			// Move focus out of the now-hidden subtree back to the launcher so
			// keyboard focus is never lost (e.g. when closing with Escape).
			root.querySelector('.assistant-launcher')?.focus();
		}
	}

	toggles.forEach((btn) => btn.addEventListener('click', () => setOpen(panel.hidden)));

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && !panel.hidden) {
			setOpen(false);
		}
	});

	suggestions.forEach((btn) => btn.addEventListener('click', () => {
		input.value = btn.textContent.trim();
		form.requestSubmit();
	}));

	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		const message = input.value.trim();
		if (!message || busy) {
			return;
		}

		appendMessage('user', message);
		input.value = '';
		setBusy(true);
		const typing = appendTyping();

		try {
			const response = await fetch(endpoint, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					Accept: 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
					'X-CSRF-TOKEN': csrf,
				},
				body: JSON.stringify({ message, conversation }),
			});

			typing.remove();

			if (response.status === 429) {
				appendMessage('bot', "You're sending messages a little too quickly. Please wait a moment and try again.");
				return;
			}

			if (!response.ok) {
				const payload = await response.json().catch(() => ({}));
				const detail = payload?.errors?.message?.[0] ?? 'Sorry, something went wrong. Please try again.';
				appendMessage('bot', detail);
				return;
			}

			const data = await response.json();
			conversation = data.conversation ?? conversation;
			safeSet(STORAGE_KEY, conversation);
			appendMessage('bot', data.reply?.content ?? '', data.reply?.citations ?? []);
		} catch (error) {
			typing.remove();
			appendMessage('bot', 'I could not reach the assistant service. Please check your connection and try again.');
		} finally {
			setBusy(false);
		}
	});

	function setBusy(state) {
		busy = state;
		if (sendButton) {
			sendButton.disabled = state;
		}
		input.disabled = state;
		if (!state) {
			input.focus();
		}
	}

	function appendMessage(role, text, citations = []) {
		const wrapper = document.createElement('div');
		wrapper.className = `assistant-message assistant-message--${role === 'user' ? 'user' : 'bot'}`;

		const body = document.createElement('div');
		body.className = 'assistant-message__body';
		body.innerHTML = formatText(text);
		wrapper.appendChild(body);

		if (citations.length > 0) {
			wrapper.appendChild(renderCitations(citations));
		}

		thread.appendChild(wrapper);
		thread.scrollTop = thread.scrollHeight;
		return wrapper;
	}

	function appendTyping() {
		const wrapper = document.createElement('div');
		wrapper.className = 'assistant-message assistant-message--bot assistant-message--typing';
		wrapper.innerHTML = '<span class="assistant-typing"><i></i><i></i><i></i></span>';
		thread.appendChild(wrapper);
		thread.scrollTop = thread.scrollHeight;
		return wrapper;
	}

	function renderCitations(citations) {
		const list = document.createElement('div');
		list.className = 'assistant-citations';
		const label = document.createElement('span');
		label.className = 'assistant-citations__label';
		label.textContent = 'Sources';
		list.appendChild(label);

		citations.forEach((citation) => {
			const text = citation.label ?? 'Source';
			let chip;
			if (citation.reference && /^https?:\/\//i.test(citation.reference)) {
				chip = document.createElement('a');
				chip.href = citation.reference;
				chip.target = '_self';
			} else {
				chip = document.createElement('span');
			}
			chip.className = 'assistant-citation';
			chip.textContent = text;
			list.appendChild(chip);
		});

		return list;
	}

	function formatText(text) {
		const escaped = escapeHtml(text);
		return escaped
			.split(/\n{2,}/)
			.map((block) => `<p>${block.replace(/\n/g, '<br>')}</p>`)
			.join('');
	}

	function escapeHtml(value) {
		const div = document.createElement('div');
		div.textContent = value ?? '';
		return div.innerHTML;
	}
}

function safeGet(key) {
	try {
		return window.localStorage.getItem(key) || null;
	} catch (error) {
		return null;
	}
}

function safeSet(key, value) {
	try {
		if (value) {
			window.localStorage.setItem(key, value);
		}
	} catch (error) {
		// Ignore storage failures (private mode, etc.).
	}
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initAssistant);
} else {
	initAssistant();
}