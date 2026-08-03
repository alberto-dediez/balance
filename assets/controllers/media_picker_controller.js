import { Controller } from '@hotwired/stimulus';

import { Modal } from 'bootstrap';

/**
 * Lets any form pick media already stored in the library or upload new ones
 * without leaving the page.
 */
export default class extends Controller {
    static targets = ['selection', 'inputs', 'file', 'modal', 'results', 'query', 'feedback'];

    static values = {
        searchPath: String,
        uploadPath: String,
        multiple: String,
        types: String,
        accept: String,
        token: String,
    }

    connect() {
        this.modal = Modal.getOrCreateInstance(this.modalTarget);

        if (this.acceptValue.length > 0) {
            this.fileTarget.setAttribute('accept', this.acceptValue);
        }
    }

    disconnect() {
        if (this.modal) {
            this.modal.dispose();
        }
    }

    get isMultiple() {
        return 'true' === this.multipleValue;
    }

    openLibrary(event) {
        event.preventDefault();

        this.modal.show();

        this.search();
    }

    browseFile(event) {
        event.preventDefault();

        this.fileTarget.click();
    }

    clear(event) {
        event.preventDefault();

        this.resetInputs();
        this.selectionTarget.innerHTML = '';
    }

    /**
     * In single mode the hidden input is always rendered, so that the field is
     * submitted even when nothing is selected.
     */
    resetInputs() {
        if (this.isMultiple) {
            this.inputsTarget.innerHTML = '';

            return;
        }

        this.inputsTarget.querySelectorAll('input').forEach(input => input.value = '');
    }

    search() {
        const query = this.hasQueryTarget ? this.queryTarget.value : '';

        const url = `${this.searchPathValue}?query=${encodeURIComponent(query)}&types=${encodeURIComponent(this.typesValue)}`;

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(response => response.json())
            .then(json => this.renderResults(json.elements || []))
            .catch(() => this.renderResults([]));
    }

    renderResults(elements) {
        this.resultsTarget.innerHTML = '';

        if (0 === elements.length) {
            this.resultsTarget.innerHTML = `<div class="col-12 text-muted">${this.resultsTarget.dataset.emptyText}</div>`;

            return;
        }

        elements.forEach(element => {
            const column = document.createElement('div');
            column.className = 'col-6 col-md-3';

            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'btn btn-outline-light border w-100 h-100 p-2 text-start';
            card.innerHTML = this.previewMarkup(element);
            card.addEventListener('click', () => this.select(element));

            column.appendChild(card);
            this.resultsTarget.appendChild(column);
        });
    }

    previewMarkup(element) {
        const preview = element.isImage
            ? `<img src="${element.url}" alt="" class="w-100 rounded mb-2" style="height: 96px; object-fit: cover;">`
            : `<div class="d-flex align-items-center justify-content-center bg-light rounded mb-2" style="height: 96px;">${element.type}</div>`;

        return `${preview}<div class="text-truncate fs-2">${this.escape(element.name)}</div>`;
    }

    select(element) {
        if (!this.isMultiple) {
            this.selectionTarget.innerHTML = '';
        } else if (this.alreadySelected(element.id)) {
            this.modal.hide();

            return;
        }

        this.appendInput(element.id);
        this.appendPreview(element);

        this.modal.hide();
    }

    alreadySelected(id) {
        return null !== this.inputsTarget.querySelector(`input[value="${id}"]`);
    }

    appendInput(id) {
        if (!this.isMultiple) {
            this.inputsTarget.querySelector('input').value = id;

            return;
        }

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = this.inputsTarget.dataset.fieldName;
        input.value = id;

        this.inputsTarget.appendChild(input);
    }

    appendPreview(element) {
        const item = document.createElement('div');
        item.className = 'col-6 col-md-3';
        item.dataset.mediaId = element.id;
        item.innerHTML = `<div class="border rounded p-2">${this.previewMarkup(element)}</div>`;

        this.selectionTarget.appendChild(item);
    }

    upload() {
        const file = this.fileTarget.files[0];

        if (!file) {
            return;
        }

        const body = new FormData();
        body.append('file', file);
        body.append('_token', this.tokenValue);

        this.setFeedback(this.feedbackTarget.dataset.uploadingText);

        fetch(this.uploadPathValue, { method: 'POST', body: body })
            .then(response => response.json())
            .then(json => {
                if (json.error) {
                    this.setFeedback(json.error);

                    return;
                }

                this.setFeedback('');
                this.select(json.element);
            })
            .catch(() => this.setFeedback(this.feedbackTarget.dataset.errorText))
            .finally(() => {
                this.fileTarget.value = '';
            });
    }

    setFeedback(message) {
        this.feedbackTarget.textContent = message;
    }

    escape(value) {
        const node = document.createElement('span');
        node.textContent = value;

        return node.innerHTML;
    }
}
