import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['reported', 'activities'];

    connect() {
        this.change();
    }

    change() {
        const checked = this.element.querySelector('input[type="radio"][name^="mode["]:checked');
        const mode = checked ? checked.value : null;

        this.reportedTargets.forEach((target) => this.toggle(target, 'reported' === mode));
        this.activitiesTargets.forEach((target) => this.toggle(target, 'activities' === mode));
    }

    toggle(target, visible) {
        target.classList.toggle('d-none', !visible);
        target.querySelectorAll('input, select, textarea, button').forEach((field) => {
            field.disabled = !visible;
        });
    }
}
