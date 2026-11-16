import { Controller } from '@hotwired/stimulus';
import { Tooltip } from 'bootstrap';

import { subscribe } from 'live';
import { showToast } from 'toast';
import { doubleSubmitCsrfToken, generateCsrfToken } from './csrf_protection_controller.js';

const OPEN_KEY = 'assistant.open';
const CONVERSATION_KEY = 'assistant.conversation';
const DRAFT_KEY = 'assistant.draft';
const MINIMIZED_KEY = 'assistant.minimized';
const WIDE_KEY = 'assistant.wide';
const MAX_FILES = 5;
const RETRY_DELAY = 2000;
const STALE = Symbol('stale');
const READABLE_TYPES = ['application/pdf', 'application/json', 'text/plain', 'text/markdown', 'text/csv', 'text/html', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'];
const READABLE_EXTENSIONS = ['pdf', 'json', 'txt', 'md', 'csv', 'html', 'jpg', 'jpeg', 'png', 'webp', 'gif'];
const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

export default class extends Controller {
    static values = {
        panelUrl: String,
        conversationsUrl: String,
        statusUrlTemplate: String,
        threadUrlTemplate: String,
        interval: { type: Number, default: 3000 },
        error: String,
        maxBytes: { type: Number, default: 10485760 },
        imageMaxBytes: { type: Number, default: 5242880 },
        totalMaxBytes: { type: Number, default: 12582912 },
        copied: String,
        tooMany: String,
        notReadable: String,
        tooLarge: String,
        empty: String,
        imageTooLarge: String,
        totalTooLarge: String,
    };

    static targets = ['thread', 'form', 'input', 'conversationId', 'screenPath', 'screenTitle', 'spent', 'minimizeButton', 'wideButton', 'fileInput', 'attachments'];

    connect() {
        this.busy = false;
        this.fingerprint = null;
        this.timer = null;
        this.sequences = { panel: 0, thread: 0 };
        this.loadPromise = null;
        this.files = new DataTransfer();

        this.onToggleClick = (event) => {
            if (event.target instanceof Element && event.target.closest('[data-assistant-toggle]')) {
                event.preventDefault();
                this.toggle();
            }
        };
        this.onVisibility = () => this.#schedule();

        document.addEventListener('click', this.onToggleClick);
        document.addEventListener('visibilitychange', this.onVisibility);

        this.unsubscribe = subscribe('assistant', () => {
            if (!this.element.hidden) {
                this.#refreshThread();
            }
        });

        this.#setMinimized(localStorage.getItem(MINIMIZED_KEY) === '1');
        this.element.classList.toggle('assistant-panel--wide', localStorage.getItem(WIDE_KEY) === '1');

        if (localStorage.getItem(OPEN_KEY) === '1') {
            this.#show();
        }
    }

    disconnect() {
        this.#stopPolling();
        document.removeEventListener('click', this.onToggleClick);
        document.removeEventListener('visibilitychange', this.onVisibility);

        if (this.unsubscribe) {
            this.unsubscribe();
        }
    }

    toggle() {
        if (this.element.hidden) {
            this.#show();

            return;
        }

        if (this.minimized) {
            this.#restore();

            return;
        }

        this.close();
    }

    close() {
        this.element.hidden = true;
        localStorage.removeItem(OPEN_KEY);
        this.#setMinimized(false);
        this.#stopPolling();
    }

    toggleMinimized(event) {
        event?.stopPropagation();

        if (this.minimized) {
            this.#restore();

            return;
        }

        this.#setMinimized(true);
        this.#stopPolling();
    }

    restore(event) {
        if (!this.minimized || (event?.target instanceof Element && event.target.closest('button'))) {
            return;
        }

        this.#restore();
    }

    #restore() {
        this.#setMinimized(false);
        this.#scrollToEnd();
        this.#schedule();
        this.#focusInput();
    }

    suggest(event) {
        if (!this.hasInputTarget) {
            return;
        }

        this.inputTarget.value = event.params.text ?? '';
        this.saveDraft();
        this.#focusInput();
    }

    submitOnEnter(event) {
        if (event.key !== 'Enter' || event.shiftKey || event.ctrlKey || event.metaKey || event.isComposing) {
            return;
        }

        event.preventDefault();
        this.formTarget.requestSubmit();
    }

    toggleWide() {
        const wide = this.element.classList.toggle('assistant-panel--wide');

        if (wide) {
            localStorage.setItem(WIDE_KEY, '1');
        } else {
            localStorage.removeItem(WIDE_KEY);
        }

        this.#updateWideButton();
    }

    newConversation() {
        localStorage.removeItem(CONVERSATION_KEY);
        this.#load(true);
    }

    async copy(event) {
        try {
            await navigator.clipboard.writeText(event.currentTarget.dataset.copyText ?? '');
            showToast(this.copiedValue, 'success');
        } catch {
            showToast(this.errorValue, 'danger');
        }
    }

    async showConversations(event) {
        const page = Number(event?.params?.page ?? 0);
        const url = new URL(this.conversationsUrlValue, window.location.origin);
        const current = localStorage.getItem(CONVERSATION_KEY);

        url.searchParams.set('page', String(page));

        if (current) {
            url.searchParams.set('current', current);
        }

        const html = await this.#fetchText(url.toString(), 'thread');

        if (typeof html !== 'string') {
            return;
        }

        if (page > 0 && event?.currentTarget instanceof Element) {
            event.currentTarget.outerHTML = html;

            return;
        }

        this.#stopPolling();
        this.threadTarget.innerHTML = html;
        this.threadTarget.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => Tooltip.getOrCreateInstance(element));
    }

    open(event) {
        const conversationId = event?.params?.conversationId;

        if (conversationId) {
            localStorage.setItem(CONVERSATION_KEY, conversationId);
        }

        this.#load(true);
    }

    async rename(event) {
        const row = event.currentTarget.closest('[data-assistant-conversation]');
        const title = row?.querySelector('[data-assistant-conversation-title]');

        if (!title || row.querySelector('input')) {
            return;
        }

        const { url, token, conversationId } = event.params;
        const input = document.createElement('input');

        input.type = 'text';
        input.className = 'form-control form-control-sm';
        input.maxLength = 120;
        input.value = event.currentTarget.dataset.title ?? title.textContent.trim();

        let finished = false;
        const restore = () => {
            if (!finished) {
                finished = true;
                input.replaceWith(title);
            }
        };

        input.addEventListener('keydown', async (keyEvent) => {
            if (keyEvent.key === 'Escape') {
                keyEvent.preventDefault();
                restore();

                return;
            }

            if (keyEvent.key !== 'Enter' || keyEvent.isComposing) {
                return;
            }

            keyEvent.preventDefault();
            finished = true;
            input.disabled = true;

            const newTitle = input.value.trim();
            const response = await this.#postAction(url, token, { title: newTitle });

            if (response === null || !response.ok) {
                finished = false;
                input.disabled = false;
                input.focus();

                return;
            }

            if (conversationId === localStorage.getItem(CONVERSATION_KEY)) {
                const header = this.element.querySelector('.assistant-panel__title');

                if (header) {
                    header.textContent = newTitle;
                }
            }

            await this.showConversations();
        });
        input.addEventListener('blur', restore);
        input.addEventListener('click', (clickEvent) => {
            clickEvent.stopPropagation();
            clickEvent.preventDefault();
        });
        input.addEventListener('mousedown', (mouseEvent) => mouseEvent.stopPropagation());

        title.replaceWith(input);
        input.focus();
        input.select();
    }

    async remove(event) {
        const { url, token, conversationId } = event.params;

        if (!window.confirm(event.params.confirm ?? '')) {
            return;
        }

        const response = await this.#postAction(url, token);

        if (response === null || !response.ok) {
            return;
        }

        if (conversationId === localStorage.getItem(CONVERSATION_KEY)) {
            this.newConversation();

            return;
        }

        await this.showConversations();
    }

    pickFiles() {
        if (this.hasFileInputTarget) {
            this.fileInputTarget.click();
        }
    }

    filesChanged() {
        this.#addFiles([...this.fileInputTarget.files]);
    }

    removeFile(event) {
        const index = Number(event.params.index);
        const remaining = new DataTransfer();

        [...this.files.files].forEach((file, position) => {
            if (position !== index) {
                remaining.items.add(file);
            }
        });

        this.files = remaining;
        this.#renderAttachments();
    }

    dragover(event) {
        if (!event.dataTransfer?.types?.includes('Files')) {
            return;
        }

        event.preventDefault();
        this.element.classList.add('assistant-panel--dragging');
    }

    dragleave(event) {
        if (!(event.relatedTarget instanceof Node) || !this.element.contains(event.relatedTarget)) {
            this.element.classList.remove('assistant-panel--dragging');
        }
    }

    drop(event) {
        this.element.classList.remove('assistant-panel--dragging');

        if (!event.dataTransfer?.files?.length) {
            return;
        }

        event.preventDefault();
        this.#addFiles([...event.dataTransfer.files]);
    }

    paste(event) {
        const files = [...(event.clipboardData?.files ?? [])];

        if (files.length === 0 || event.clipboardData.getData('text/plain') !== '') {
            return;
        }

        event.preventDefault();
        this.#addFiles(files);
    }

    #addFiles(files) {
        for (const file of files) {
            const current = [...this.files.files];

            if (current.some((existing) => existing.name === file.name && existing.size === file.size && existing.lastModified === file.lastModified)) {
                continue;
            }

            if (current.length >= MAX_FILES) {
                showToast(this.tooManyValue, 'warning');

                break;
            }

            if (!this.#isReadable(file)) {
                showToast(this.notReadableValue.replace('{name}', file.name), 'warning');

                continue;
            }

            if (file.size < 1) {
                showToast(this.emptyValue.replace('{name}', file.name), 'warning');

                continue;
            }

            if (file.size > this.maxBytesValue) {
                showToast(this.tooLargeValue.replace('{name}', file.name), 'warning');

                continue;
            }

            if (this.#isImage(file) && file.size > this.imageMaxBytesValue) {
                showToast(this.imageTooLargeValue.replace('{name}', file.name), 'warning');

                continue;
            }

            if (current.reduce((total, existing) => total + existing.size, file.size) > this.totalMaxBytesValue) {
                showToast(this.totalTooLargeValue, 'warning');

                continue;
            }

            this.files.items.add(file);
        }

        this.#renderAttachments();
    }

    #isImage(file) {
        if (IMAGE_TYPES.includes(file.type)) {
            return true;
        }

        return IMAGE_EXTENSIONS.includes(file.name.split('.').pop().toLowerCase());
    }

    #isReadable(file) {
        if (READABLE_TYPES.includes(file.type)) {
            return true;
        }

        return READABLE_EXTENSIONS.includes(file.name.split('.').pop().toLowerCase());
    }

    #renderAttachments() {
        if (this.hasFileInputTarget) {
            this.fileInputTarget.files = this.files.files;
        }

        if (!this.hasAttachmentsTarget) {
            return;
        }

        const container = this.attachmentsTarget;

        container.replaceChildren();

        [...this.files.files].forEach((file, index) => {
            const chip = document.createElement('span');
            const name = document.createElement('span');
            const size = document.createElement('span');
            const remove = document.createElement('button');

            chip.className = 'assistant-attachment';
            name.className = 'assistant-attachment__name';
            name.textContent = file.name;
            size.className = 'assistant-attachment__size';
            size.textContent = this.#formatSize(file.size);
            remove.type = 'button';
            remove.className = 'assistant-attachment__remove';
            remove.textContent = '×';
            remove.setAttribute('aria-label', container.dataset.removeLabel ?? '');
            remove.setAttribute('title', container.dataset.removeLabel ?? '');
            remove.dataset.action = 'assistant-panel#removeFile';
            remove.dataset.assistantPanelIndexParam = String(index);

            chip.append(name, size, remove);
            container.append(chip);
        });
    }

    #formatSize(bytes) {
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        return Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    saveDraft() {
        if (this.hasInputTarget) {
            localStorage.setItem(DRAFT_KEY, this.inputTarget.value);
        }
    }

    async send(event) {
        event.preventDefault();

        const form = this.formTarget;

        this.screenPathTarget.value = window.location.pathname + window.location.search;
        this.screenTitleTarget.value = this.#screenTitle();

        generateCsrfToken(form);

        const response = await this.#post(form, event.submitter);

        if (response === null) {
            return;
        }

        if (response.status === 422) {
            showToast(await response.text(), 'warning');

            return;
        }

        if (!response.ok) {
            showToast(this.errorValue, 'danger');

            return;
        }

        const data = await response.json();

        localStorage.setItem(CONVERSATION_KEY, data.conversationId);
        localStorage.removeItem(DRAFT_KEY);
        this.files = new DataTransfer();
        await this.#load(true);
    }

    async execute(event) {
        await this.#submitPlanForm(event);
    }

    async discard(event) {
        await this.#submitPlanForm(event);
    }

    async stop(event) {
        await this.#submitPlanForm(event);
    }

    async #submitPlanForm(event) {
        event.preventDefault();

        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        form.querySelectorAll('button[type="submit"]').forEach((button) => button.disabled = true);
        generateCsrfToken(form);

        const response = await this.#post(form, event.submitter);

        if (response === null) {
            return;
        }

        if (response.status === 422) {
            showToast(await response.text(), 'warning');
        } else if (!response.ok) {
            showToast(this.errorValue, 'danger');
        }

        await this.#refreshThread();
    }

    #show() {
        this.element.hidden = false;
        localStorage.setItem(OPEN_KEY, '1');
        this.#setMinimized(false);
        this.#load();
    }

    #setMinimized(minimized) {
        this.minimized = minimized;
        this.element.classList.toggle('assistant-panel--minimized', minimized);

        if (minimized) {
            localStorage.setItem(MINIMIZED_KEY, '1');
        } else {
            localStorage.removeItem(MINIMIZED_KEY);
        }

        this.#updateMinimizeButton();
    }

    #updateMinimizeButton() {
        if (!this.hasMinimizeButtonTarget) {
            return;
        }

        const button = this.minimizeButtonTarget;
        const label = this.minimized ? button.dataset.restoreLabel : button.dataset.minimizeLabel;

        button.setAttribute('aria-label', label);
        button.setAttribute('aria-expanded', this.minimized ? 'false' : 'true');

        const tooltip = Tooltip.getInstance(button);

        if (tooltip) {
            tooltip.hide();
            tooltip.setContent({ '.tooltip-inner': label });
        } else {
            button.setAttribute('title', label);
        }
    }

    #updateWideButton() {
        if (!this.hasWideButtonTarget) {
            return;
        }

        const button = this.wideButtonTarget;
        const wide = this.element.classList.contains('assistant-panel--wide');
        const label = wide ? button.dataset.narrowLabel : button.dataset.wideLabel;

        button.setAttribute('aria-label', label);
        button.setAttribute('aria-pressed', wide ? 'true' : 'false');

        const tooltip = Tooltip.getInstance(button);

        if (tooltip) {
            tooltip.hide();
            tooltip.setContent({ '.tooltip-inner': label });
        } else {
            button.setAttribute('title', label);
        }
    }

    #isRenaming() {
        return this.hasThreadTarget && this.threadTarget.querySelector('[data-assistant-conversation] input') !== null;
    }

    #scrollToEnd() {
        if (this.hasThreadTarget) {
            this.threadTarget.scrollTop = this.threadTarget.scrollHeight;
        }
    }

    #focusInput() {
        if (this.hasInputTarget && !this.minimized) {
            this.inputTarget.focus();
        }
    }

    #load(force = false) {
        if (this.loadPromise !== null && !force) {
            return this.loadPromise;
        }

        const promise = this.#loadPanel().finally(() => {
            if (this.loadPromise === promise) {
                this.loadPromise = null;
            }
        });

        this.loadPromise = promise;

        return promise;
    }

    #panelUrl() {
        const url = new URL(this.panelUrlValue, window.location.origin);
        const conversationId = localStorage.getItem(CONVERSATION_KEY);

        if (conversationId) {
            url.searchParams.set('conversationId', conversationId);
        }

        return url.toString();
    }

    async #loadPanel() {
        ++this.sequences.thread;

        let html = await this.#fetchText(this.#panelUrl(), 'panel');

        if (html === null && this.element.childElementCount === 0) {
            await new Promise((resolve) => setTimeout(resolve, RETRY_DELAY));
            html = await this.#fetchText(this.#panelUrl(), 'panel');

            if (html === null) {
                showToast(this.errorValue, 'danger');
                this.close();

                return;
            }
        }

        if (typeof html !== 'string') {
            return;
        }

        this.element.innerHTML = html;

        if (this.hasInputTarget) {
            this.inputTarget.value = localStorage.getItem(DRAFT_KEY) ?? '';
        }

        if (this.hasConversationIdTarget && !this.conversationIdTarget.value) {
            localStorage.removeItem(CONVERSATION_KEY);
        }

        this.#updateMinimizeButton();
        this.#updateWideButton();
        this.#renderAttachments();
        this.#afterThreadRendered();
    }

    async #refreshThread() {
        const conversationId = localStorage.getItem(CONVERSATION_KEY);

        if (!conversationId) {
            return;
        }

        if (!this.hasThreadTarget || this.loadPromise !== null) {
            return this.#load();
        }

        if (this.#isRenaming()) {
            return;
        }

        const html = await this.#fetchText(this.threadUrlTemplateValue.replace('__ID__', conversationId), 'thread');

        if (typeof html !== 'string' || !this.hasThreadTarget || conversationId !== localStorage.getItem(CONVERSATION_KEY) || this.#isRenaming()) {
            return;
        }

        this.threadTarget.innerHTML = html;
        this.#afterThreadRendered();
    }

    #afterThreadRendered() {
        const thread = this.element.querySelector('[data-assistant-thread]');

        this.busy = thread !== null && thread.dataset.busy === '1';
        this.fingerprint = thread !== null && thread.dataset.fingerprint ? thread.dataset.fingerprint : null;

        if (thread !== null && this.hasSpentTarget && thread.dataset.spent !== undefined) {
            this.spentTarget.textContent = thread.dataset.spent;
        }

        this.element.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => Tooltip.getOrCreateInstance(element));

        this.#scrollToEnd();
        this.#schedule();
    }

    async #checkStatus() {
        const conversationId = localStorage.getItem(CONVERSATION_KEY);

        if (!conversationId || this.element.hidden) {
            return;
        }

        let status;

        try {
            const response = await fetch(this.statusUrlTemplateValue.replace('__ID__', conversationId), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (this.#sessionLost(response) || !response.ok) {
                return;
            }

            status = await response.json();
        } catch {
            return;
        }

        if (this.fingerprint !== null && status.fingerprint !== this.fingerprint) {
            await this.#refreshThread();

            return;
        }

        this.fingerprint = status.fingerprint;
        this.busy = status.busy;
        this.#schedule();
    }

    #schedule() {
        this.#stopPolling();

        if (!this.busy || this.element.hidden || this.minimized || document.visibilityState !== 'visible') {
            return;
        }

        this.timer = setTimeout(() => this.#checkStatus(), this.intervalValue);
    }

    #stopPolling() {
        clearTimeout(this.timer);
        this.timer = null;
    }

    #screenTitle() {
        const title = document.querySelector('.body-wrapper .card-title');
        const text = title ? title.textContent.trim() : '';

        return (text !== '' ? text : document.title).slice(0, 255);
    }

    async #post(form, submitter) {
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form, submitter ?? null),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            return this.#sessionLost(response) ? null : response;
        } catch {
            showToast(this.errorValue, 'danger');

            return null;
        }
    }

    async #postAction(url, token, fields = {}) {
        const target = new URL(url, window.location.origin);

        target.searchParams.set('_token', doubleSubmitCsrfToken(token));

        const body = new FormData();

        Object.entries(fields).forEach(([name, value]) => body.append(name, value));

        try {
            const response = await fetch(target.toString(), {
                method: 'POST',
                body,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (this.#sessionLost(response)) {
                return null;
            }

            if (response.status === 422) {
                showToast(await response.text(), 'warning');
            } else if (!response.ok) {
                showToast(this.errorValue, 'danger');
            }

            return response;
        } catch {
            showToast(this.errorValue, 'danger');

            return null;
        }
    }

    async #fetchText(url, channel) {
        const sequence = ++this.sequences[channel];

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (this.#sessionLost(response) || !response.ok) {
                return null;
            }

            const html = await response.text();

            return sequence === this.sequences[channel] ? html : STALE;
        } catch {
            showToast(this.errorValue, 'danger');

            return null;
        }
    }

    #sessionLost(response) {
        if (!response.redirected && response.status !== 401 && response.status !== 403) {
            return false;
        }

        this.#stopPolling();
        this.saveDraft();
        window.location.reload();

        return true;
    }
}
