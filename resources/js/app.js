import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

const isDark = document.documentElement.classList.contains('dark');
Chart.defaults.font.family = "'IBM Plex Sans Arabic', system-ui, sans-serif";
Chart.defaults.color = isDark ? '#98a6bd' : '#64748b';
Chart.defaults.borderColor = isDark ? 'rgba(148,163,184,0.12)' : 'rgba(15,23,42,0.06)';
Chart.defaults.plugins.legend.labels.usePointStyle = true;

/**
 * Charts are declared in Blade as <canvas data-chart='{"type":..., "data":..., "options":...}'>.
 * The wrapper shows a skeleton until the chart has drawn.
 */
function renderCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        const config = JSON.parse(canvas.dataset.chart);
        config.options = Object.assign({ responsive: true, maintainAspectRatio: false }, config.options || {});
        if (document.documentElement.dir === 'rtl' && config.type === 'bar') {
            config.options.scales = config.options.scales || {};
            config.options.scales.x = Object.assign({ reverse: true }, config.options.scales.x || {});
        }
        config.options.animation = Object.assign({}, config.options.animation, {
            onComplete: () => canvas.parentElement.classList.remove('skeleton'),
        });
        new Chart(canvas, config);
    });
}

/* ---------- Loading states ---------- */

const bar = document.createElement('div');
bar.id = 'page-progress';
document.body.prepend(bar);
let barTimer;

function startProgress() {
    clearTimeout(barTimer);
    bar.className = 'active';
    // Safety net: downloads and blocked navigations never unload the page
    barTimer = setTimeout(stopProgress, 10000);
}

function stopProgress() {
    clearTimeout(barTimer);
    bar.className = 'done';
    setTimeout(() => (bar.className = ''), 300);
}

function setButtonLoading(button) {
    if (!button || button.dataset.loading) {
        return;
    }
    button.dataset.loading = '1';
    const spinner = document.createElement('span');
    spinner.className = 'spinner';
    button.prepend(spinner);
    // Disable after the browser has captured the form data (keeps the submitter's name/value)
    setTimeout(() => (button.disabled = true), 0);
}

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey) {
        return;
    }
    const url = new URL(link.href, location.href);
    const samePage = url.pathname === location.pathname && url.search === location.search && url.hash;
    if (link.target === '_blank' || link.hasAttribute('download') || link.dataset.noLoading !== undefined || url.origin !== location.origin || samePage) {
        return;
    }
    startProgress();
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented || form.target === '_blank' || form.dataset.noLoading !== undefined) {
        return;
    }
    if (form.dataset.submitting) {
        event.preventDefault(); // block double submits
        return;
    }
    form.dataset.submitting = '1';
    startProgress();
    setButtonLoading(event.submitter || form.querySelector('button:not([type=button]), [type=submit]'));
});

// Restore state when the page comes back from the back/forward cache
window.addEventListener('pageshow', () => {
    stopProgress();
    document.querySelectorAll('form[data-submitting]').forEach((form) => delete form.dataset.submitting);
    document.querySelectorAll('[data-loading]').forEach((button) => {
        button.disabled = false;
        delete button.dataset.loading;
        button.querySelector('.spinner')?.remove();
    });
});

document.addEventListener('DOMContentLoaded', renderCharts);

Alpine.start();
