import './bootstrap';

const root = document.documentElement;
const themeToggle = document.querySelector('[data-theme-toggle]');
const menuToggle = document.querySelector('[data-menu-toggle]');
const mobileNavigation = document.querySelector('[data-mobile-nav]');
const connectionStatus = document.querySelector('[data-connection-status]');
const installButton = document.querySelector('[data-install-app]');

const storedTheme = localStorage.getItem('iomp-theme');
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
	root.classList.add('dark');
}

const updateThemeLabel = () => {
	if (!themeToggle) return;

	const isDark = root.classList.contains('dark');
	themeToggle.setAttribute('aria-label', `Switch to ${isDark ? 'light' : 'dark'} mode`);
};

updateThemeLabel();

themeToggle?.addEventListener('click', () => {
	root.classList.toggle('dark');
	localStorage.setItem('iomp-theme', root.classList.contains('dark') ? 'dark' : 'light');
	updateThemeLabel();
});

menuToggle?.addEventListener('click', () => {
	const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
	menuToggle.setAttribute('aria-expanded', String(!isOpen));
	menuToggle.setAttribute('aria-label', isOpen ? 'Open navigation' : 'Close navigation');
	mobileNavigation.hidden = isOpen;
});

mobileNavigation?.querySelectorAll('a').forEach((link) => {
	link.addEventListener('click', () => {
		mobileNavigation.hidden = true;
		menuToggle.setAttribute('aria-expanded', 'false');
		menuToggle.setAttribute('aria-label', 'Open navigation');
	});
});

const showConnectionStatus = (message) => {
	if (!connectionStatus) return;

	connectionStatus.textContent = message;
	connectionStatus.hidden = false;
	window.setTimeout(() => { connectionStatus.hidden = true; }, 4000);
};

window.addEventListener('offline', () => showConnectionStatus('You are offline. Saved pages remain available.'));
window.addEventListener('online', () => showConnectionStatus('Connection restored.'));

if ('serviceWorker' in navigator) {
	window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
}

let installPrompt;

window.addEventListener('beforeinstallprompt', (event) => {
	event.preventDefault();
	installPrompt = event;
	if (installButton) installButton.hidden = false;
});

installButton?.addEventListener('click', async () => {
	if (!installPrompt) return;

	await installPrompt.prompt();
	installPrompt = null;
	installButton.hidden = true;
});