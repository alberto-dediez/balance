import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['checkbox', 'details', 'sections'];

    connect() {
        this.toggle();
    }

    toggle() {
        if (!this.hasCheckboxTarget) {
            return;
        }

        const excluded = this.checkboxTarget.checked;

        if (this.hasDetailsTarget) {
            this.detailsTarget.classList.toggle('d-none', !excluded);
        }

        if (this.hasSectionsTarget) {
            this.sectionsTarget.disabled = excluded;
            this.sectionsTarget.classList.toggle('opacity-50', excluded);
        }
    }
}
