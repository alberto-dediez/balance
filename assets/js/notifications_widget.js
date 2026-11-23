export function applyNotificationsWidget(root, html) {
    if (!root) {
        return null;
    }

    const doc = new DOMParser().parseFromString(html, 'text/html');
    const freshBadge = doc.querySelector('[data-notifications-badge]');
    const freshMenu = doc.querySelector('[data-notifications-menu]');
    const currentBadge = root.querySelector('[data-notifications-badge]');
    const currentMenu = root.querySelector('[data-notifications-menu]');

    if (!freshBadge || !freshMenu || !currentBadge || !currentMenu) {
        return null;
    }

    const previous = Number(currentBadge.dataset.notificationsCount);
    const next = Number(freshBadge.dataset.notificationsCount);

    currentBadge.replaceWith(freshBadge);
    currentMenu.innerHTML = freshMenu.innerHTML;

    return { previous, next };
}
