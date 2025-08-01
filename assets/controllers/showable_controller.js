import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        selector: String,
        condition: String,
        container: String
    }

    connect() {
        const controller = document.querySelector(this.selectorValue);
        const condition = this.conditionValue;

        const showable = this.containerValue ? this.element.closest(this.containerValue) : this.element;

        controller.addEventListener('change', function (e) {
            var value = controller.value;
            var optionValue = null;

            if (controller.matches('select')) {
                var optionValue = controller.querySelector('option[value="' + value + '"]')?.getAttribute('data-value');
            }

            if (controller.value == condition || optionValue == condition) {
                showable.classList.remove('d-none');
            } else {
                showable.classList.add('d-none');
            }
        });

        controller.dispatchEvent(new Event("change"));
    }
}
