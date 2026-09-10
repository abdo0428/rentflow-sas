try { localStorage.setItem('rentflow.locale', document.documentElement.lang); } catch {}

const updateConnection = () => {
    document.querySelectorAll('[data-offline-banner]').forEach(banner => { banner.hidden = navigator.onLine; });
};
window.addEventListener('online', updateConnection);
window.addEventListener('offline', updateConnection);
updateConnection();

document.addEventListener('submit', event => {
    if (!navigator.onLine) {
        event.preventDefault();
        event.stopImmediatePropagation();
        updateConnection();
        document.querySelector('[data-offline-banner]')?.scrollIntoView({ block: 'center' });
    }
}, true);

window.addEventListener('pageshow', event => { if (event.persisted) window.location.reload(); });

let installPrompt;
window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    installPrompt = event;
    document.querySelectorAll('[data-pwa-install]').forEach(button => { button.hidden = false; });
});
document.querySelectorAll('[data-pwa-install]').forEach(button => button.addEventListener('click', async () => {
    if (!installPrompt) return;
    await installPrompt.prompt();
    await installPrompt.userChoice;
    installPrompt = null;
    document.querySelectorAll('[data-pwa-install]').forEach(item => { item.hidden = true; });
}));
window.addEventListener('appinstalled', () => {
    installPrompt = null;
    document.querySelectorAll('[data-pwa-install]').forEach(button => { button.hidden = true; });
});
if ('serviceWorker' in navigator && window.isSecureContext && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' }).catch(() => {});
    });
}
