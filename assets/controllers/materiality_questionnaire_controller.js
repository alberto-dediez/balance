import { Controller } from '@hotwired/stimulus';
import { Collapse, Tooltip } from 'bootstrap';

import { showToast } from 'toast';

const ACCORDION = 'accordion';
const MATRIX = 'matrix';
const SAVE_DELAY = 400;
const TOPIC_HASH_PREFIX = '#topic-';
const TOPIC_SCROLL_MARGIN = 16;
const SCROLL_END_FALLBACK = 800;

const VIEW_CLASSES = {
    [ACCORDION]: {
        options: [],
        option: ['form-check', 'form-check-inline'],
        input: ['form-check-input'],
        label: ['form-check-label'],
    },
    [MATRIX]: {
        options: ['btn-group', 'btn-group-sm'],
        option: ['materiality-answer__option'],
        input: ['btn-check'],
        label: ['btn', 'btn-outline-secondary', 'btn-sm'],
    },
};

export default class extends Controller {
    static targets = ['accordion', 'matrix', 'form', 'topicBadge', 'groupBadge', 'globalBadge', 'answered', 'total', 'complete', 'viewButton', 'noJs'];

    static values = {
        storageKey: String,
        readOnly: Boolean,
        unsavedLabel: String,
        errorMessage: String,
    };

    connect() {
        this.timers = new Map();
        this.queues = new Map();
        this.view = ACCORDION;
        this.onChange = this.changed.bind(this);

        this.noJsTargets.forEach(element => element.classList.add('d-none'));
        this.#collapseAllExcept(this.#topicIdFromHash());

        if (!this.readOnlyValue) {
            this.element.addEventListener('change', this.onChange);
        }

        this.#show(localStorage.getItem(this.storageKeyValue) === MATRIX ? MATRIX : ACCORDION, false);
        this.openFromHash();
    }

    disconnect() {
        this.element.removeEventListener('change', this.onChange);
        this.timers.forEach(timer => clearTimeout(timer));
    }

    showAccordion() {
        this.#show(ACCORDION, true);
    }

    showMatrix() {
        this.#show(MATRIX, true);
    }

    openFromHash() {
        const topicId = this.#topicIdFromHash();

        if (!topicId) {
            return;
        }

        if (this.view === MATRIX) {
            this.element.querySelector(`#matrix-topic-${CSS.escape(topicId)}`)?.scrollIntoView({ block: 'start' });

            return;
        }

        const panel = this.element.querySelector(`#topic-${CSS.escape(topicId)}`);

        if (!panel) {
            return;
        }

        Collapse.getOrCreateInstance(panel, { toggle: false }).show();
        panel.closest('.accordion-item')?.scrollIntoView({ block: 'start' });
    }

    changed(event) {
        const input = event.target;

        if (!(input instanceof HTMLInputElement) || input.type !== 'radio' || this.readOnlyValue) {
            return;
        }

        const form = input.form;
        const topicId = form?.dataset.assessmentTopicId;

        if (!topicId) {
            return;
        }

        this.#removeInheritedMark(input.closest('fieldset'));

        clearTimeout(this.timers.get(topicId));
        this.timers.set(topicId, setTimeout(() => this.#save(form, topicId), SAVE_DELAY));
    }

    markMinimum({ params: { topicId } }) {
        const form = this.formTargets.find(form => form.dataset.assessmentTopicId === String(topicId));

        if (!form || this.readOnlyValue) {
            return;
        }

        this.#fieldsetsOf(form).forEach(fieldset => {
            const radio = fieldset.querySelector(`input[type="radio"][value="${fieldset.dataset.scaleMin}"]`);

            if (radio && !radio.checked && !radio.disabled) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    #show(view, persist) {
        this.element.querySelectorAll('fieldset[data-answer-id]').forEach(fieldset => {
            const answerId = CSS.escape(fieldset.dataset.answerId);
            const slot = view === MATRIX
                ? this.matrixTarget.querySelector(`[data-slot-answer-id="${answerId}"]`)
                : this.accordionTarget.querySelector(`[data-origin-answer-id="${answerId}"]`);

            if (!slot) {
                return;
            }

            slot.appendChild(fieldset);
            this.#applyViewClasses(fieldset, slot.hasAttribute('data-slot-compact'));
        });

        this.accordionTarget.classList.toggle('d-none', view !== ACCORDION);
        this.matrixTarget.classList.toggle('d-none', view !== MATRIX);

        this.viewButtonTargets.forEach(button => {
            const active = button.dataset.view === view;

            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        this.view = view;

        if (persist) {
            localStorage.setItem(this.storageKeyValue, view);
        }
    }

    #applyViewClasses(fieldset, compact) {
        const current = VIEW_CLASSES[compact ? MATRIX : ACCORDION];
        const other = VIEW_CLASSES[compact ? ACCORDION : MATRIX];
        const swap = (elements, part) => elements.forEach(element => {
            element.classList.remove(...other[part]);
            element.classList.add(...current[part]);
        });

        fieldset.classList.toggle('materiality-answer--compact', compact);

        swap(fieldset.querySelectorAll('[data-options]'), 'options');
        swap(fieldset.querySelectorAll('[data-option]'), 'option');
        swap(fieldset.querySelectorAll('input[type="radio"]'), 'input');
        swap(fieldset.querySelectorAll('[data-option] > label'), 'label');
    }

    #collapseAllExcept(topicId) {
        this.accordionTarget.querySelectorAll('.accordion-collapse').forEach(panel => {
            if (panel.id === `topic-${topicId}`) {
                return;
            }

            panel.classList.remove('show');

            const button = this.accordionTarget.querySelector(`[data-bs-target="#${CSS.escape(panel.id)}"]`);

            button?.classList.add('collapsed');
            button?.setAttribute('aria-expanded', 'false');
        });
    }

    #topicIdFromHash() {
        return window.location.hash.startsWith(TOPIC_HASH_PREFIX) ? window.location.hash.slice(TOPIC_HASH_PREFIX.length) : null;
    }

    #fieldsetsOf(form) {
        return Array.from(this.element.querySelectorAll('fieldset[data-answer-id]'))
            .filter(fieldset => fieldset.querySelector('input[type="radio"]')?.form === form);
    }

    #removeInheritedMark(fieldset) {
        const mark = fieldset?.querySelector('[data-inherited]');

        if (mark) {
            Tooltip.getInstance(mark)?.dispose();
            mark.remove();
        }
    }

    #save(form, topicId) {
        const previous = this.queues.get(topicId) ?? Promise.resolve();
        const next = previous.then(() => this.#send(form, topicId));

        this.queues.set(topicId, next.catch(() => {}));
    }

    async #send(form, topicId) {
        let response;

        try {
            response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new FormData(form),
            });
        } catch {
            this.#fail(topicId, this.errorMessageValue);

            return;
        }

        const isJson = !response.redirected && (response.headers.get('content-type') ?? '').includes('application/json');
        const data = isJson ? await response.json().catch(() => null) : null;

        if (!response.ok || data === null) {
            this.#fail(topicId, data?.message ?? this.errorMessageValue);

            if (response.status === 409) {
                this.#disableAll();
            }

            return;
        }

        this.#applyCounts(topicId, data);
    }

    #fail(topicId, message) {
        showToast(message, 'danger');

        this.#topicBadges(topicId).forEach(badge => {
            badge.className = 'badge bg-danger';
            badge.textContent = this.unsavedLabelValue;
        });
    }

    #disableAll() {
        this.readOnlyValue = true;
        this.element.querySelectorAll('fieldset[data-answer-id] input[type="radio"]').forEach(input => {
            input.disabled = true;
        });
        this.completeTargets.forEach(button => {
            button.disabled = true;
        });
    }

    #applyCounts(topicId, { topicAnswered, topicTotal, answered, total, fullyAnswered }) {
        const wasComplete = this.#isTopicComplete(topicId);

        this.#topicBadges(topicId).forEach(badge => {
            badge.dataset.answered = topicAnswered;
            badge.dataset.total = topicTotal;
            this.#setBadge(badge, topicAnswered, topicTotal);
        });

        this.groupBadgeTargets.forEach(groupBadge => {
            const rows = this.topicBadgeTargets.filter(badge => badge.dataset.groupKey === groupBadge.dataset.groupKey);
            const sum = key => rows.reduce((carry, badge) => carry + Number(badge.dataset[key] ?? 0), 0);

            this.#setBadge(groupBadge, sum('answered'), sum('total'));
        });

        this.globalBadgeTargets.forEach(badge => this.#setBadge(badge, answered, total));
        this.answeredTargets.forEach(element => {
            element.textContent = answered;
        });
        this.totalTargets.forEach(element => {
            element.textContent = total;
        });
        this.completeTargets.forEach(button => {
            button.disabled = !fullyAnswered;
        });

        if (!wasComplete && topicAnswered === topicTotal && this.view === ACCORDION) {
            this.#openNextPendingTopic(topicId);
        }
    }

    #isTopicComplete(topicId) {
        const badge = this.#accordionBadge(topicId);

        return badge !== undefined && Number(badge.dataset.answered) === Number(badge.dataset.total);
    }

    #accordionBadge(topicId) {
        return this.topicBadgeTargets.find(badge => badge.dataset.topicId === topicId && badge.dataset.groupKey !== undefined);
    }

    #openNextPendingTopic(topicId) {
        const panels = Array.from(this.accordionTarget.querySelectorAll('.accordion-collapse'));
        const current = panels.findIndex(panel => panel.id === `topic-${topicId}`);

        if (current === -1) {
            return;
        }

        const next = panels.slice(current + 1).find(panel => !this.#isTopicComplete(panel.id.slice('topic-'.length)));

        if (!next) {
            return;
        }

        next.addEventListener('shown.bs.collapse', async () => {
            const item = next.closest('.accordion-item') ?? next;

            await this.#frame(item);
            this.#collapseKeepingPosition(panels[current], item);
        }, { once: true });

        Collapse.getOrCreateInstance(next, { toggle: false }).show();
    }

    async #frame(element) {
        const offset = this.#topOffset();
        const rect = element.getBoundingClientRect();
        const fits = rect.top >= offset && rect.bottom <= window.innerHeight;

        if (fits) {
            return;
        }

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        window.scrollTo({ top: window.scrollY + rect.top - offset, behavior: reducedMotion ? 'auto' : 'smooth' });

        if (!reducedMotion) {
            await this.#scrollEnd();
        }
    }

    #collapseKeepingPosition(panel, anchor) {
        const before = anchor.getBoundingClientRect().top;

        panel.classList.remove('show');

        const button = this.accordionTarget.querySelector(`[data-bs-target="#${CSS.escape(panel.id)}"]`);

        button?.classList.add('collapsed');
        button?.setAttribute('aria-expanded', 'false');

        const shift = anchor.getBoundingClientRect().top - before;

        if (shift !== 0) {
            window.scrollBy({ top: shift, behavior: 'instant' });
        }
    }

    #topOffset() {
        const topbar = document.querySelector('.topbar');

        return (topbar ? Math.max(topbar.getBoundingClientRect().bottom, 0) : 0) + TOPIC_SCROLL_MARGIN;
    }

    #scrollEnd() {
        return new Promise(resolve => {
            const done = () => {
                clearTimeout(timer);
                window.removeEventListener('scrollend', done);
                resolve();
            };
            const timer = setTimeout(done, SCROLL_END_FALLBACK);

            window.addEventListener('scrollend', done, { once: true });
        });
    }

    #topicBadges(topicId) {
        return this.topicBadgeTargets.filter(badge => badge.dataset.topicId === topicId);
    }

    #setBadge(badge, answered, total) {
        badge.className = `badge ${answered === total ? 'bg-success' : 'bg-warning text-dark'}`;
        badge.textContent = `${answered}/${total}`;
    }
}
