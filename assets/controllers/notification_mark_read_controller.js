import { Controller } from '@hotwired/stimulus';

import { applyNotificationsWidget } from 'notifications-widget';

export default class extends Controller {
    static values = {
        id: String,
        widgetUrl: String
    }

    async mark(event) {
        event.preventDefault();
        event.stopPropagation();

        if (this.busy) {
            return;
        }

        this.busy = true;

        const root = this.element.closest('[data-notifications-widget]');

        let response;

        try {
            response = await fetch(this.element.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
        } catch {
            window.location.assign(this.element.href);

            return;
        }

        if (response.status !== 204) {
            window.location.assign(this.element.href);

            return;
        }

        this.#syncList();
        await this.#refreshWidget(root);
    }

    #syncList() {
        const link = document.querySelector(`[data-notification-mark-read][href*="/${this.idValue}/read"]`);
        const row = link?.closest('tr');

        if (!row) {
            return;
        }

        row.querySelectorAll('[data-notification-unread]')
            .forEach(element => element.remove());

        row.querySelectorAll('[data-notification-subject]')
            .forEach(element => element.classList.remove('fw-semibold'));

        row.querySelectorAll('[data-notification-icon]').forEach(element => {
            element.classList.remove('bg-primary-subtle', 'text-primary');
            element.classList.add('bg-body-secondary', 'text-body-secondary');
        });

        row.querySelectorAll('[data-notification-mark-read]')
            .forEach(element => element.classList.add('d-none'));

        row.querySelectorAll('[data-notification-mark-unread]')
            .forEach(element => element.classList.remove('d-none'));
    }

    async #refreshWidget(root) {
        const sequence = root ? Number(root.dataset.markSequence ?? 0) + 1 : 0;

        if (root) {
            root.dataset.markSequence = String(sequence);
        }

        let html;

        try {
            const response = await fetch(this.widgetUrlValue, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                this.#removeItem();

                return;
            }

            html = await response.text();
        } catch {
            this.#removeItem();

            return;
        }

        if (root && Number(root.dataset.markSequence) !== sequence) {
            return;
        }

        const counts = applyNotificationsWidget(root, html);

        if (!counts) {
            this.#removeItem();

            return;
        }

        if (counts.next === 0) {
            document.querySelectorAll('[data-notifications-mark-all]')
                .forEach(element => element.classList.add('d-none'));
        }
    }

    #removeItem() {
        this.element.parentElement.remove();
    }
}
