import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        const inputs = this.element.querySelectorAll('input:not([name*="page"])');

        for (const input of inputs) {
            input.addEventListener('change', function () {
                document.querySelector('input[name*="filter[page]"]').value = 0;
            });
        }
    }
}
