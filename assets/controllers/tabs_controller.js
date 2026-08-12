import { Controller } from '@hotwired/stimulus';
import { Tab } from 'bootstrap';

export default class extends Controller {
    connect() {
        this.triggers = Array.from(this.element.querySelectorAll('[data-bs-toggle="tab"]'));

        this.triggers.forEach(trigger => {
            const tab = Tab.getOrCreateInstance(trigger);

            trigger.addEventListener('click', event => {
                event.preventDefault();
                tab.show();
            });

            trigger.addEventListener('shown.bs.tab', () => {
                history.replaceState(null, '', trigger.dataset.bsTarget);
            });
        });

        this.showFromHash();
    }

    showFromHash() {
        if (!window.location.hash) {
            return;
        }

        const trigger = this.triggers.find(trigger => trigger.dataset.bsTarget === window.location.hash);

        if (trigger) {
            Tab.getOrCreateInstance(trigger).show();
        }
    }
}
