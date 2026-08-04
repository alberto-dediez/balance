import { Controller } from '@hotwired/stimulus';

/*
 * Marks every unread notification as read without leaving the current page.
 *
 * It is attached to the "mark all as read" forms (topbar widget and notification
 * list). The response is applied to every notification hook present in the page,
 * so both the widget and the list stay in sync wherever the action is triggered.
 */
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
            });
        } catch {
            // Without connectivity fall back to the plain form submission
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
        if (!message) {
            return;
        }

        let container = document.querySelector('.toast-container');

        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.append(container);
        }

        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-bg-success border-0';
        toast.setAttribute('data-controller', 'toast');
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = `
            <div class="toast-body hstack align-items-start gap-6">
                <i class="ti ti-alert-circle fs-6"></i>
                <div><h6 class="text-white fs-4 mb-0"></h6></div>
                <button type="button" class="btn-close btn-close-white fs-2 m-0 ms-auto shadow-none" data-bs-dismiss="toast"></button>
            </div>
        `;
        toast.querySelector('h6').textContent = message;

        container.append(toast);
    }
}
