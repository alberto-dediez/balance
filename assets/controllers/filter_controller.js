import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        const inputs = this.element.querySelectorAll('select, input:not([name*="page"])');

        for (const input of inputs) {
            input.addEventListener('change', function () {
                document.querySelector('input[name*="filter[page]"]').value = 0;
            });
        }
    }

    resetField(event) {
        let alert = event.target.closest('.filter-show');
        alert.remove();

        let field = document.querySelector(event.params.field);

        field.value = '';
        field.dispatchEvent(new Event("change"));

        field.closest('form').submit();
    }
}
