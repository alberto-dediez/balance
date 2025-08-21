import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        if (!this.element.matches('input[type="checkbox"]')) {
            return ;
        }

        this.element.addEventListener('change', function (event) {
            const value = event.target.matches(':checked');

            const checkboxes = event.target.closest('table').querySelectorAll('input[type="checkbox"]');

            checkboxes.forEach(checkbox => {
                checkbox.checked = value;
            });
        });
    }
}
