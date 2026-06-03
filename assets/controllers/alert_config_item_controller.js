import { Controller } from '@hotwired/stimulus';

// Shows only the parameters group matching the alert type selected in this row.
export default class extends Controller {
    static targets = ['type', 'parameters'];

    connect() {
        this.toggle();
    }

    toggle() {
        const selected = this.hasTypeTarget ? this.typeTarget.value : null;

        this.parametersTargets.forEach((el) => {
            el.classList.toggle('d-none', el.dataset.alertType !== selected);
        });
    }
}
