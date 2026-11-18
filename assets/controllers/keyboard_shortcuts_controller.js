import { Controller } from '@hotwired/stimulus';

import { Modal } from 'bootstrap';

export default class extends Controller {
    static targets = ['shortcut']

    connect() {
        this.bindings = new Map(this.shortcutTargets.map(shortcut => [shortcut.dataset.combination, {
            url: shortcut.dataset.url ?? null,
            event: shortcut.dataset.event ?? null,
            global: '1' === shortcut.dataset.global,
            submit: '1' === shortcut.dataset.submit,
            withinModal: '1' === shortcut.dataset.withinModal,
        }]));
        this.modal = Modal.getOrCreateInstance(this.element);
    }

    disconnect() {
        this.modal.dispose();
    }

    handle(event) {
        if (event.repeat || event.isComposing || undefined === event.key) {
            return;
        }

        const binding = this.bindings.get(this.#combinationOf(event));

        if (undefined === binding) {
            return;
        }

        if (!binding.global && (this.#isTyping(event.target) || (!binding.withinModal && null !== document.querySelector('.modal.show')))) {
            return;
        }

        if (binding.submit) {
            const button = this.#submitButtonOf(event);

            if (null === button) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            button.click();

            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (null !== binding.url) {
            window.location.assign(binding.url);

            return;
        }

        if (!binding.withinModal) {
            this.#hideVisibleModal();
        }

        window.dispatchEvent(new CustomEvent(`keyboard-shortcuts:${binding.event}`));
    }

    open() {
        this.modal.show();
    }

    #combinationOf(event) {
        const parts = [];

        if (event.ctrlKey || event.metaKey) {
            parts.push('ctrl');
        }

        if (event.altKey) {
            parts.push('alt');
        }

        if (event.shiftKey) {
            parts.push('shift');
        }

        parts.push(event.key.toLowerCase());

        return parts.join('+');
    }

    #submitButtonOf(event) {
        if (event.defaultPrevented || !(event.target instanceof Element)) {
            return null;
        }

        const form = event.target.closest('form');

        if (null === form) {
            return null;
        }

        const button = form.querySelector('button[type="submit"][data-submit-shortcut="1"]');

        return button instanceof HTMLButtonElement && !button.disabled ? button : null;
    }

    #isTyping(target) {
        if (!(target instanceof Element)) {
            return false;
        }

        return target.isContentEditable || null !== target.closest('input, textarea, select, [contenteditable]');
    }

    #hideVisibleModal() {
        const visible = document.querySelector('.modal.show');

        if (null !== visible) {
            Modal.getInstance(visible)?.hide();
        }
    }
}
