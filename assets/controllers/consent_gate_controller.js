import { Controller } from '@hotwired/stimulus';
import { readCookie } from '../js/cookie.js';

export default class extends Controller {
    static values = { name: String };

    connect() {
        this.release = this.release.bind(this);

        window.addEventListener('cookie-consent:accepted', this.release);

        if (this.accepted()) {
            this.release();
        }
    }

    disconnect() {
        window.removeEventListener('cookie-consent:accepted', this.release);
    }

    release() {
        document.querySelectorAll('template[data-consent-gated]').forEach((template) => {
            const parent = template.parentNode;

            parent.insertBefore(template.content.cloneNode(true), template);
            template.remove();
        });

        document.querySelectorAll('script[type="text/plain"][data-cookie-consent]').forEach((placeholder) => {
            const script = document.createElement('script');

            Array.from(placeholder.attributes).forEach((attribute) => {
                if (attribute.name !== 'type' && attribute.name !== 'data-cookie-consent') {
                    script.setAttribute(attribute.name, attribute.value);
                }
            });

            script.textContent = placeholder.textContent;
            placeholder.parentNode.insertBefore(script, placeholder);
            placeholder.remove();
        });
    }

    accepted() {
        return readCookie(this.nameValue) === 'accepted';
    }
}
