import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['text', 'swatch'];
    static values = { fallback: String };

    connect() {
        this.syncSwatch();
    }

    syncSwatch() {
        const value = this.textTarget.value.trim();

        this.swatchTarget.value = /^#[0-9A-Fa-f]{6}$/.test(value)
            ? value
            : (this.fallbackValue || '#FFFFFF');
    }

    syncText() {
        this.textTarget.value = this.swatchTarget.value.toUpperCase();
        this.textTarget.dispatchEvent(new Event('change', { bubbles: true }));
    }

    reset() {
        this.textTarget.value = '';
        this.syncSwatch();
        this.textTarget.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
