import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { prototype: String, index: Number, placeholder: { type: String, default: '__name__' } };

    connect() {
        const collection = this.element.querySelector('.collection');
        this.indexValue = collection ? collection.querySelectorAll(':scope > .collection-element').length : 0;

        this.renumberIndexes();
    }

    add(event) {
        event.preventDefault();

        const prototype = this.prototypeValue.split(this.placeholderValue).join(this.indexValue);
        const collection = this.element.querySelector('.collection');

        if (!collection) return;

        const temp = document.createElement('template');
        temp.innerHTML = prototype;

        const newEl = temp.content.firstElementChild;
        if (newEl) {
            const placeholder = collection.querySelector('.collection-empty');
            if (placeholder) placeholder.remove();

            collection.appendChild(newEl);
            this.indexValue++;
            this.renumber();

            newEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            newEl.querySelector('input:not([type=hidden]), select, textarea')?.focus();
        }
    }

    remove(event) {
        event.preventDefault();
        event.target.closest('.collection-element').remove();
        this.renumber();
    }

    moveUp(event) {
        event.preventDefault();

        const element = event.target.closest('.collection-element');
        const previous = element.previousElementSibling;

        if (previous && previous.classList.contains('collection-element')) {
            element.parentNode.insertBefore(element, previous);
            this.renumber();
        }
    }

    moveDown(event) {
        event.preventDefault();

        const element = event.target.closest('.collection-element');
        const next = element.nextElementSibling;

        if (next && next.classList.contains('collection-element')) {
            element.parentNode.insertBefore(next, element);
            this.renumber();
        }
    }

    renumber() {
        const collection = this.element.querySelector('.collection');

        if (!collection) return;

        collection.querySelectorAll(':scope > .collection-element').forEach((element, index) => {
            const position = element.querySelector('input[data-collection-position]');

            if (position && position.closest('[data-controller~="collection"]') === this.element) {
                position.value = index + 1;
            }
        });

        this.renumberIndexes();
    }

    renumberIndexes() {
        const collection = this.element.querySelector('.collection');

        if (!collection) return;

        collection.querySelectorAll(':scope > .collection-element').forEach((element, index) => {
            element.querySelectorAll('[data-collection-index]').forEach((label) => {
                if (label.closest('[data-controller~="collection"]') === this.element) {
                    label.textContent = index + 1;
                }
            });
        });
    }
}
