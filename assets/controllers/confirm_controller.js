import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        
    }

    link(event) {
        const message = event.params.text;

        if (confirm(message)) {
            const link = event.target.tagName == 'a' ? event.target : event.target.closest('a');
            const target = link.getAttribute('href');

            document.location.assign(target);
        }
    }

    submit(event) {
        const message = event.params.text;

        if (!confirm(message)) {
            event.preventDefault();
        }
    }
}
