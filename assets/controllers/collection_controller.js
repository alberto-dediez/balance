import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { prototype: String, index: Number };

    connect() {
        const collection = this.element.querySelector('.collection');
        this.indexValue = collection ? collection.querySelectorAll('.collection-element').length : 0;
    }

    add(event) {
        event.preventDefault();

        const prototype = this.prototypeValue.replace(/__name__/g, this.indexValue);
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
        }
    }

    remove(event) {
        event.preventDefault();
        event.target.closest('.collection-element').remove();
    }
}
