import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { url: String };

    connect() {
        const criticalPointSelect = this.element.querySelector('[data-role="critical-point-select"]');
        const alreadyPopulated = criticalPointSelect && criticalPointSelect.options.length > 1;

        if (!alreadyPopulated) {
            this.element.querySelectorAll('select').forEach(el => {
                el.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
    }

    propertyChanged(event) {
        const propertyId = event.target.value;
        const criticalPointSelect = this.element.querySelector('[data-role="critical-point-select"]');

        if (!criticalPointSelect) return;

        const tomSelectInstance = criticalPointSelect.tomselect;

        if (tomSelectInstance) {
            tomSelectInstance.clear(true);
            tomSelectInstance.clearOptions();
        } else {
            criticalPointSelect.innerHTML = '<option value=""></option>';
        }

        if (!propertyId) return;

        fetch(`${this.urlValue}?propertyId=${encodeURIComponent(propertyId)}`)
            .then(response => response.json())
            .then(criticalPoints => {
                if (tomSelectInstance) {
                    criticalPoints.forEach(cp => tomSelectInstance.addOption({ value: cp.id, text: cp.name }));
                    tomSelectInstance.refreshOptions(false);
                } else {
                    criticalPoints.forEach(cp => {
                        const option = document.createElement('option');
                        option.value = cp.id;
                        option.textContent = cp.name;
                        criticalPointSelect.appendChild(option);
                    });
                }
            })
            .catch(() => {});
    }
}
