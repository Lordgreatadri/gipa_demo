import { createIcons, Eye, EyeOff } from 'lucide';

import './bootstrap';

createIcons({
	icons: { Eye, EyeOff },
});

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

const filterToggle = document.querySelector('[data-filter-toggle]');
const filterPanel = document.querySelector('[data-filter-panel]');

filterToggle?.addEventListener('click', () => {
	const isOpen = filterToggle.getAttribute('aria-expanded') === 'true';
	filterToggle.setAttribute('aria-expanded', String(!isOpen));
	filterPanel?.classList.toggle('is-open', !isOpen);
});

const regionSelect = document.querySelector('[data-region-select]');
const districtSelect = document.querySelector('[data-district-select]');

const updateDistrictOptions = () => {
	if (!regionSelect || !districtSelect) return;

	const region = regionSelect.value;
	Array.from(districtSelect.options).forEach((option) => {
		if (!option.value) return;
		option.hidden = Boolean(region) && option.dataset.region !== region;
	});

	if (districtSelect.selectedOptions[0]?.hidden) districtSelect.value = '';
};

regionSelect?.addEventListener('change', updateDistrictOptions);
updateDistrictOptions();

const sectorSelect = document.querySelector('[data-sector-select]');
const subSectorSelect = document.querySelector('[data-sub-sector-select]');

const updateSubSectorOptions = () => {
	if (!sectorSelect || !subSectorSelect) return;

	Array.from(subSectorSelect.options).forEach((option) => {
		if (!option.value) return;
		option.hidden = Boolean(sectorSelect.value) && option.dataset.sector !== sectorSelect.value;
	});

	if (subSectorSelect.selectedOptions[0]?.hidden) subSectorSelect.value = '';
};

sectorSelect?.addEventListener('change', updateSubSectorOptions);
updateSubSectorOptions();

const adminMenu = document.querySelector('[data-admin-menu]');
const adminSidebar = document.querySelector('[data-admin-sidebar]');

adminMenu?.addEventListener('click', () => {
	const isOpen = adminMenu.getAttribute('aria-expanded') === 'true';
	adminMenu.setAttribute('aria-expanded', String(!isOpen));
	adminMenu.setAttribute('aria-label', isOpen ? 'Open staff navigation' : 'Close staff navigation');
	adminSidebar?.classList.toggle('is-open', !isOpen);
});

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
	button.addEventListener('click', () => {
		const input = document.getElementById(button.getAttribute('aria-controls'));
		if (!input) return;

		const isVisible = input.type === 'text';
		input.type = isVisible ? 'password' : 'text';
		button.setAttribute('aria-pressed', String(!isVisible));
		button.setAttribute('aria-label', `${isVisible ? 'Show' : 'Hide'} ${input.name === 'password_confirmation' ? 'confirm password' : 'password'}`);
		button.querySelector('[data-password-show]').hidden = !isVisible;
		button.querySelector('[data-password-hide]').hidden = isVisible;
	});
});