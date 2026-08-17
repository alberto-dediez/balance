import { Controller } from '@hotwired/stimulus';

import TomSelect from 'tom-select';

import 'tom-select/dist/css/tom-select.bootstrap5.min.css';

export default class extends Controller {
    static values = {
        path: String,
        exitPath: String,
        token: String,
        current: String
    }

    static targets = ['input']

    connect() {
        const placeholder = this.inputTarget.querySelector('option')?.textContent ?? '';

        this.tomselect = new TomSelect(this.inputTarget, {
            create: false,
            placeholder: placeholder,
            onChange: (value) => {
                if (!value) {
                    return;
                }
                this.impersonate(value);
            },
        });

        if (this.currentValue) {
            this.tomselect.setValue(this.currentValue, true);
        }
    }

    impersonate(accountId) {
        fetch(this.pathValue, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams({ accountId: accountId, _token: this.tokenValue }),
        }).then((response) => {
            if (response.ok) {
                window.location.reload();
            }
        });
    }

    exit() {
        fetch(this.exitPathValue, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams({ _token: this.tokenValue }),
        }).then((response) => {
            if (response.ok) {
                window.location.reload();
            }
        });
    }

    disconnect() {
        this.tomselect?.destroy();
    }
}