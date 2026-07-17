import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        selector: String,
        condition: String,
        conditions: Array,
        container: String
    }

    connect() {
        const controller = document.querySelector(this.selectorValue);
        const condition = this.conditionValue;
        const conditions = this.conditionsValue;

        const showable = this.containerValue ? this.element.closest(this.containerValue) : this.element;

        const toggle = function () {
            var value = controller.matches('input[type="checkbox"]') ? (controller.checked ? controller.value : '') : controller.value;
            var optionValue = null;

            if (controller.matches('select')) {
                optionValue = controller.querySelector('option[value="' + value + '"]')?.getAttribute('data-value');
            }

            if (value == condition || optionValue == condition || conditions.includes(value) || conditions.includes(optionValue)) {
                showable.classList.remove('d-none');
            } else {
                showable.classList.add('d-none');
            }
        };

        controller.addEventListener('change', toggle);
        toggle();
    }
}
