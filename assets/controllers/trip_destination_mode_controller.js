import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['field'];

    connect() {
        this.refresh();
    }

    refresh() {
        const checked = this.element.querySelector('input[type="radio"][name$="[mode]"]:checked');
        const mode = checked ? checked.value : 'free';

        this.fieldTargets.forEach((field) => {
            const modes = (field.dataset.modes || '').split(' ');

            field.classList.toggle('d-none', !modes.includes(mode));
        });
    }
}
