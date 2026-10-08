import { Controller } from '@hotwired/stimulus';

import { Modal } from 'bootstrap';

export default class extends Controller {
    static values = {
        url: String,
    }

    static targets = ['input', 'results', 'result', 'clear']

    connect() {
        this.sequence = 0;
        this.timer = null;
        this.initial = this.resultsTarget.innerHTML;
        this.modal = Modal.getOrCreateInstance(this.element);
    }

    disconnect() {
        clearTimeout(this.timer);

        this.modal.dispose();
    }

    shortcut(event) {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();

            this.modal.show();
        }
    }

    focus() {
        this.inputTarget.focus();
    }

    reset() {
        clearTimeout(this.timer);

        this.inputTarget.value = '';
        ++this.sequence;
        this.resultsTarget.innerHTML = this.initial;
        this.#toggleClear();
    }

    clear() {
        this.reset();
        this.focus();
    }

    search() {
        clearTimeout(this.timer);
        this.#toggleClear();

        this.timer = setTimeout(() => this.#fetch(this.inputTarget.value), 300);
    }

    navigate(event) {
        const results = this.resultTargets;
        const active = results.findIndex(result => result.classList.contains('active'));

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();

            if (0 === results.length) {
                return;
            }

            const step = event.key === 'ArrowDown' ? 1 : -1;
            const next = -1 === active ? 0 : (active + step + results.length) % results.length;

            this.#activate(results[next]);

            return;
        }

        if (event.key === 'Enter' && -1 !== active) {
            event.preventDefault();

            window.location.assign(results[active].href);
        }
    }

    async #fetch(term) {
        const sequence = ++this.sequence;

        let html;

        try {
            const response = await fetch(`${this.urlValue}?term=${encodeURIComponent(term)}`, {
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

        this.resultsTarget.innerHTML = html;

        if (this.resultTargets.length > 0) {
            this.#activate(this.resultTargets[0]);
        }
    }

    #toggleClear() {
        this.clearTarget.classList.toggle('d-none', '' === this.inputTarget.value);
    }

    #activate(result) {
        this.resultTargets.forEach(candidate => candidate.classList.toggle('active', candidate === result));

        result.scrollIntoView({ block: 'nearest' });
    }
}
