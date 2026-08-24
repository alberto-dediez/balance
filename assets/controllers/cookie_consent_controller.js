import { Controller } from '@hotwired/stimulus';
import { readCookie } from '../js/cookie.js';

export default class extends Controller {
    static values = { name: String, maxAge: Number };

    connect() {
        this.element.hidden = this.decision() !== null;
    }

    accept() {
        this.store('accepted');
        window.dispatchEvent(new CustomEvent('cookie-consent:accepted'));
    }

    reject() {
        this.store('rejected');
    }

    store(decision) {
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';

        document.cookie = `${this.nameValue}=${decision}; Max-Age=${this.maxAgeValue}; Path=/; SameSite=Lax${secure}`;
        this.element.hidden = true;
    }

    decision() {
        return readCookie(this.nameValue);
    }
}
