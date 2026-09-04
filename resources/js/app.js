import Chart from 'chart.js/auto';
import L from 'leaflet';
import {
	Activity, BadgeCheck, Bell, Bot, BriefcaseBusiness, CalendarClock, CalendarX, ChartNoAxesCombined,
	ChevronDown, CircleDollarSign, ClockAlert, createIcons, Eye, EyeOff, Files, Gauge, Landmark,
	Layers3, LayoutDashboard, MapPinned, Route, Search, ShieldAlert, ShieldCheck, TriangleAlert, UsersRound,
} from 'lucide';

import './assistant';
import './bootstrap';

import 'leaflet/dist/leaflet.css';

createIcons({
	icons: {
		Activity, BadgeCheck, Bell, Bot, BriefcaseBusiness, CalendarClock, CalendarX, ChartNoAxesCombined,
		ChevronDown, CircleDollarSign, ClockAlert, Eye, EyeOff, Files, Gauge, Landmark,
		Layers3, LayoutDashboard, MapPinned, Route, Search, ShieldAlert, ShieldCheck, TriangleAlert, UsersRound,
	},
});

const chartPalette = ['#087a50', '#e0aa16', '#2474a6', '#d4543f', '#5d6b66', '#7b5aa6', '#2b9b8b', '#b86a26'];

const mapElement = document.querySelector('#investment-map');
const mapPointsElement = document.querySelector('[data-map-points]');

if (mapElement && mapPointsElement) {
	const map = L.map(mapElement, { scrollWheelZoom: false, minZoom: 6, maxBounds: [[3.8, -4.2], [12.1, 2.1]] }).setView([7.95, -1.02], 7);
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		maxZoom: 18,
		attribution: '&copy; OpenStreetMap contributors',
	}).addTo(map);

	fetch(mapElement.dataset.boundaryUrl)
		.then((response) => response.ok ? response.json() : Promise.reject(new Error('Boundary unavailable')))
		.then((boundary) => L.geoJSON(boundary, { style: { color: '#087a50', weight: 2, fillColor: '#d9ede4', fillOpacity: 0.16 } }).addTo(map))
		.catch(() => {});

	JSON.parse(mapPointsElement.textContent).forEach((point) => {
		const popup = document.createElement('div');
		const category = document.createElement('small');
		const title = document.createElement('strong');
		const link = document.createElement('a');
		category.textContent = `${point.sector} / ${point.district}`;
		title.textContent = point.title;
		link.textContent = 'View opportunity';
		link.href = point.url;
		popup.className = 'map-popup';
		popup.append(category, title, link);
		L.circleMarker([point.latitude, point.longitude], { radius: 7, color: '#fff', weight: 2, fillColor: '#c8940d', fillOpacity: 1 })
			.bindPopup(popup)
			.addTo(map);
	});

	document.querySelector('[data-find-location]')?.addEventListener('click', (event) => {
		const button = event.currentTarget;
		if (!navigator.geolocation) {
			button.textContent = 'Location unavailable';
			return;
		}
		button.disabled = true;
		navigator.geolocation.getCurrentPosition(({ coords }) => {
			const location = [coords.latitude, coords.longitude];
			L.circleMarker(location, { radius: 8, color: '#075b3b', fillColor: '#fff', fillOpacity: 1, weight: 3 }).bindPopup('Your approximate location').addTo(map);
			map.flyTo(location, 10);
			button.textContent = 'Location found';
			button.disabled = false;
		}, () => {
			button.textContent = 'Location not available';
			button.disabled = false;
		}, { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 });
	});
}

document.querySelectorAll('[data-chart-panel]').forEach((panel) => {
	const canvas = panel.querySelector('[data-dashboard-chart]');
	const configElement = panel.querySelector('[data-chart-config]');
	if (!canvas || !configElement) return;

	const config = JSON.parse(configElement.textContent);
	const isCircular = ['doughnut', 'pie'].includes(config.type);
	const isLine = config.type === 'line';
	const datasets = config.datasets.map((dataset, index) => ({
		...dataset,
		backgroundColor: isCircular ? chartPalette : `${chartPalette[index]}cc`,
		borderColor: isCircular ? '#ffffff' : chartPalette[index],
		borderWidth: isCircular ? 3 : 2,
		borderRadius: config.type === 'bar' ? 5 : 0,
		pointBackgroundColor: chartPalette[index],
		pointRadius: isLine ? 4 : 0,
		pointHoverRadius: isLine ? 6 : 0,
		fill: isLine,
		tension: isLine ? 0.32 : 0,
	}));

	new Chart(canvas, {
		type: config.type,
		data: { labels: config.labels, datasets },
		options: {
			responsive: true,
			maintainAspectRatio: false,
			animation: { duration: 650, easing: 'easeOutQuart' },
			interaction: { intersect: false, mode: 'index' },
			plugins: {
				legend: {
					position: isCircular ? 'right' : 'bottom',
					labels: { boxWidth: 10, boxHeight: 10, padding: 16, usePointStyle: true, color: '#65736d', font: { family: 'DM Sans', size: 11 } },
				},
				tooltip: { padding: 10, cornerRadius: 4, titleFont: { family: 'Manrope' }, bodyFont: { family: 'DM Sans' } },
			},
			cutout: config.type === 'doughnut' ? '64%' : undefined,
			scales: isCircular ? undefined : {
				x: { grid: { display: false }, ticks: { color: '#65736d', maxRotation: 35, minRotation: 0, font: { family: 'DM Sans', size: 10 } } },
				y: { beginAtZero: true, grid: { color: 'rgba(101,115,109,.14)' }, ticks: { color: '#65736d', precision: 0, font: { family: 'DM Sans', size: 10 } } },
				...(datasets.some((dataset) => dataset.yAxisID === 'y1') ? {
					y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { color: '#65736d', font: { family: 'DM Sans', size: 10 } } },
				} : {}),
			},
		},
	});
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

document.querySelectorAll('[data-nav-group]').forEach((group) => {
	const storageKey = `iomp-nav-${group.dataset.navGroup}`;
	if (!group.querySelector('.is-current')) group.open = sessionStorage.getItem(storageKey) === 'open';
	group.addEventListener('toggle', () => sessionStorage.setItem(storageKey, group.open ? 'open' : 'closed'));
});

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

// CSP-safe replacements for former inline handlers. Auto-submit a filter form
// when its control changes, and confirm destructive form submissions.
document.addEventListener('change', (event) => {
	const control = event.target.closest('[data-auto-submit]');
	if (control) control.form?.requestSubmit();
});

document.addEventListener('submit', (event) => {
	const form = event.target.closest('form[data-confirm]');
	if (form && !window.confirm(form.dataset.confirm)) event.preventDefault();
});