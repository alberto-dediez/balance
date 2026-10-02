import { Controller } from '@hotwired/stimulus';

import { Modal } from 'bootstrap';

export default class extends Controller {
    static targets = ['selection', 'inputs', 'file', 'modal', 'results', 'query', 'feedback', 'includeShared', 'dropzone', 'uploadToggle', 'clear', 'itemTemplate', 'resultTemplate', 'emptyResults', 'loadMore'];

    static values = {
        searchPath: String,
        uploadPath: String,
        multiple: String,
        types: String,
        accept: String,
        visibility: String,
        token: String,
    }

    connect() {
        this.modal = Modal.getOrCreateInstance(this.modalTarget);

        if (this.acceptValue.length > 0) {
            this.fileTarget.setAttribute('accept', this.acceptValue);
        }

        this.page = 0;
        this.limit = 24;

        const empty = this.selectionTarget.querySelector('.media-picker__empty');

        this.emptyMarkup = empty ? empty.outerHTML : null;
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

        this.page = 0;

        this.modal.show();

        this.search();
    }

    browseFile(event) {
        event.preventDefault();

        this.fileTarget.click();
    }

    toggleDropzone(event) {
        event.preventDefault();

        this.showDropzone(this.dropzoneTarget.classList.contains('d-none'));
    }

    showDropzone(visible) {
        this.dropzoneTarget.classList.toggle('d-none', !visible);
        this.uploadToggleTarget.setAttribute('aria-expanded', visible ? 'true' : 'false');
        this.uploadToggleTarget.classList.toggle('active', visible);
    }

    clear(event) {
        event.preventDefault();

        this.resetInputs();
        this.renderEmptySelection();
        this.syncClearButton();
    }

    dragOver(event) {
        event.preventDefault();

        this.dropzoneTarget.classList.add('is-dragover');
    }

    dragLeave() {
        this.dropzoneTarget.classList.remove('is-dragover');
    }

    drop(event) {
        event.preventDefault();

        this.dropzoneTarget.classList.remove('is-dragover');

        const files = Array.from(event.dataTransfer.files);

        if (0 === files.length) {
            return;
        }

        this.uploadNext(this.isMultiple ? files : files.slice(0, 1));
    }

    loadMore(event) {
        event.preventDefault();

        this.page++;

        this.search(true);
    }

    syncClearButton() {
        const hasValue = Array.from(this.inputsTarget.querySelectorAll('input')).some(input => '' !== input.value);

        this.clearTarget.classList.toggle('d-none', !hasValue);
    }

    renderEmptySelection() {
        if (this.emptyMarkup) {
            this.selectionTarget.innerHTML = this.emptyMarkup;

            return;
        }

        const empty = document.createElement('div');
        empty.className = 'media-picker__empty';
        empty.textContent = this.selectionTarget.dataset.emptyText || '';

        this.selectionTarget.replaceChildren(empty);
    }

    resetInputs() {
        if (this.isMultiple) {
            this.inputsTarget.innerHTML = '';

            return;
        }

        this.inputsTarget.querySelectorAll('input').forEach(input => input.value = '');
    }

    search(append = false) {
        if (true !== append) {
            this.page = 0;
        }

        const query = this.hasQueryTarget ? this.queryTarget.value : '';

        const includeShared = this.hasIncludeSharedTarget && this.includeSharedTarget.checked ? '1' : '';

        const url = `${this.searchPathValue}?query=${encodeURIComponent(query)}&types=${encodeURIComponent(this.typesValue)}&visibility=${encodeURIComponent(this.visibilityValue)}&includeShared=${includeShared}&page=${this.page}&limit=${this.limit}`;

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(response => response.json())
            .then(json => this.renderResults(json.elements || [], true === append, json.totalResults || 0))
            .catch(() => this.renderResults([], true === append, 0));
    }

    renderResults(elements, append, totalResults) {
        if (!append) {
            this.resultsTarget.innerHTML = '';
        }

        elements.forEach(element => {
            const column = this.resultTemplateTarget.content.firstElementChild.cloneNode(true);
            const card = column.querySelector('[data-media-id]');

            card.dataset.mediaId = element.id;
            card.classList.toggle('is-selected', this.alreadySelected(element.id));
            card.addEventListener('click', () => this.select(element));

            this.fillSlots(column, element);

            this.resultsTarget.appendChild(column);
        });

        this.emptyResultsTarget.classList.toggle('d-none', 0 !== this.resultsTarget.children.length);
        this.loadMoreTarget.classList.toggle('d-none', (this.page + 1) * this.limit >= totalResults);
    }

    fillSlots(node, element) {
        const name = node.querySelector('[data-slot="name"]');

        if (name) {
            name.textContent = element.name;
        }

        const badges = Array.from(node.querySelectorAll('[data-slot="type"]'));
        const badge = badges.find(candidate => candidate.dataset.type === element.type)
            || badges.find(candidate => 'Other' === candidate.dataset.type);

        if (badge) {
            badge.hidden = false;
        }

        const preview = node.querySelector('[data-slot="preview"]');

        if (preview && element.isImage && element.url) {
            preview.src = element.url;
            preview.alt = element.name;
            preview.hidden = false;

            return;
        }

        const icons = Array.from(node.querySelectorAll('[data-slot="icon"]'));
        const icon = icons.find(candidate => candidate.dataset.type === element.type)
            || icons.find(candidate => 'Other' === candidate.dataset.type);

        if (icon) {
            icon.hidden = false;
        }
    }

    select(element) {
        if (!this.isMultiple) {
            this.selectionTarget.innerHTML = '';
        } else if (this.alreadySelected(element.id)) {
            this.modal.hide();

            return;
        }

        this.selectionTarget.querySelectorAll('.media-picker__empty').forEach(empty => empty.remove());

        this.appendInput(element.id);
        this.appendPreview(element);

        this.modal.hide();

        this.syncClearButton();
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
        const item = this.itemTemplateTarget.content.firstElementChild.cloneNode(true);

        item.dataset.mediaId = element.id;

        this.fillSlots(item, element);

        this.selectionTarget.appendChild(item);
    }

    removeItem(event) {
        event.preventDefault();

        const item = event.target.closest('[data-media-id]');

        if (!item) {
            return;
        }

        this.removeInput(item.dataset.mediaId);
        item.remove();

        if (0 === this.selectionTarget.children.length) {
            this.renderEmptySelection();
        }

        this.syncClearButton();
    }

    removeInput(id) {
        if (!this.isMultiple) {
            this.inputsTarget.querySelector('input').value = '';

            return;
        }

        const input = this.inputsTarget.querySelector(`input[value="${id}"]`);

        if (input) {
            input.remove();
        }
    }

    upload() {
        const files = Array.from(this.fileTarget.files);

        if (0 === files.length) {
            return;
        }

        this.uploadNext(files);
    }

    uploadNext(files, failed = false) {
        const [file, ...rest] = files;

        if (!file) {
            if (!failed) {
                this.setFeedback('');
                this.showDropzone(false);
            }

            this.fileTarget.value = '';

            return;
        }

        const body = new FormData();
        body.append('file', file);
        body.append('types', this.typesValue);
        body.append('visibility', this.visibilityValue);
        body.append('_token', this.tokenValue);

        this.setFeedback(this.feedbackTarget.dataset.uploadingText, 'uploading');

        let error = failed;

        fetch(this.uploadPathValue, { method: 'POST', body: body })
            .then(response => response.json())
            .then(json => {
                if (json.error) {
                    error = true;

                    this.setFeedback(json.error, 'error');

                    return;
                }

                this.select(json.element);
            })
            .catch(() => {
                error = true;

                this.setFeedback(this.feedbackTarget.dataset.errorText, 'error');
            })
            .finally(() => this.uploadNext(rest, error));
    }

    setFeedback(message, state = '') {
        this.dropzoneTarget.classList.toggle('is-uploading', 'uploading' === state);
        this.feedbackTarget.classList.toggle('text-danger', 'error' === state);

        this.feedbackTarget.textContent = message;

        if ('uploading' === state) {
            const spinner = document.createElement('span');
            spinner.className = 'spinner-border spinner-border-sm me-1';
            spinner.setAttribute('role', 'status');

            this.feedbackTarget.prepend(spinner);
        }
    }
}
