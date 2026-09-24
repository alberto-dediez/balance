import { Controller } from '@hotwired/stimulus';

import { showToast } from 'toast';

export default class extends Controller {
    static values = {
        url: String,
        interval: Number,
        newMessage: String
    }

    static targets = ['toggle', 'menu']

    connect() {
        this.pending = false;
        this.sequence = 0;
        this.onVisibility = () => this.#onVisibilityChange();
        this.onHidden = () => {
            if (this.pending) this.#refresh();
        };

        document.addEventListener('visibilitychange', this.onVisibility);
        this.element.addEventListener('hidden.bs.dropdown', this.onHidden);

        this.#start();
    }

    disconnect() {
        this.#stop();

        document.removeEventListener('visibilitychange', this.onVisibility);
        this.element.removeEventListener('hidden.bs.dropdown', this.onHidden);
    }

    #start() {
        if (document.visibilityState !== 'visible') {
            return;
        }

        this.timer = setInterval(() => this.#refresh(), Math.max(5, this.intervalValue) * 1000);
    }

    #stop() {
        clearInterval(this.timer);
        this.timer = null;
    }

    #onVisibilityChange() {
        if (document.visibilityState === 'visible') {
            this.#refresh();
            this.#start();

            return;
        }

        this.#stop();
    }

    #isOpen() {
        return this.toggleTarget.getAttribute('aria-expanded') === 'true';
    }

    async #refresh() {
        if (this.#isOpen()) {
            this.pending = true;

            return;
        }

        this.pending = false;

        const sequence = ++this.sequence;

        let html;

        try {
            const response = await fetch(this.urlValue, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            html = await response.text();
        } catch {
            return;
        }

        if (sequence !== this.sequence) {
            return;
        }

        if (this.#isOpen()) {
            this.pending = true;

            return;
        }

        const doc = new DOMParser().parseFromString(html, 'text/html');
        const freshBadge = doc.querySelector('[data-notifications-badge]');
        const freshMenu = doc.querySelector('[data-notifications-menu]');

        if (!freshBadge || !freshMenu) {
            return;
        }

        const currentBadge = this.element.querySelector('[data-notifications-badge]');
        const previous = Number(currentBadge.dataset.notificationsCount);
        const next = Number(freshBadge.dataset.notificationsCount);

        currentBadge.replaceWith(freshBadge);
        this.menuTarget.innerHTML = freshMenu.innerHTML;

        if (next > previous) {
            showToast(this.newMessageValue, 'primary');
        }
    }
}
