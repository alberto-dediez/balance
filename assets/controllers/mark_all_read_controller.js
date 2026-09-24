import { Controller } from '@hotwired/stimulus';

import { showToast } from 'toast';

export default class extends Controller {
    async submit(event) {
        event.preventDefault();

        let response;

        try {
            response = await fetch(this.element.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new FormData(this.element),
            });
        } catch {
            this.element.submit();

            return;
        }

        if (!response.ok) {
            this.element.submit();

            return;
        }

        const data = await response.json();

        this.#clearNotifications();
        this.#notify(data.message);
    }

    #clearNotifications() {
        document.querySelectorAll('[data-notifications-badge], [data-notifications-pill]')
            .forEach(element => element.classList.add('d-none'));

        document.querySelectorAll('[data-notifications-list]')
            .forEach(element => element.replaceChildren());

        document.querySelectorAll('[data-notifications-empty]')
            .forEach(element => element.classList.remove('d-none'));

        document.querySelectorAll('[data-notifications-mark-all]')
            .forEach(element => element.classList.add('d-none'));

        document.querySelectorAll('[data-notification-unread]')
            .forEach(element => element.remove());

        document.querySelectorAll('[data-notification-subject]')
            .forEach(element => element.classList.remove('fw-semibold'));

        document.querySelectorAll('[data-notification-icon]').forEach(element => {
            element.classList.remove('bg-primary-subtle', 'text-primary');
            element.classList.add('bg-body-secondary', 'text-body-secondary');
        });
    }

    #notify(message) {
        if (message) showToast(message);
    }
}
