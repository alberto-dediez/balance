import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { url: String, type: String };

    connect() {
        this.element.querySelectorAll('tbody tr').forEach((row) => {
            this.restrictUnit(row);
            this.applyMode(row);
        });

        if (this.typeValue === 'electricity') {
            this.form = this.element.closest('form');
            this.validateSubmit = this.validateSubmit.bind(this);
            this.form?.addEventListener('submit', this.validateSubmit);
        }
    }

    disconnect() {
        this.form?.removeEventListener('submit', this.validateSubmit);
    }

    itemChanged(event) {
        const row = event.target.closest('tr');

        this.restrictUnit(row);
        this.calculate(row);
    }

    validateSubmit(event) {
        let firstInvalidRow = null;

        this.element.querySelectorAll('tbody tr').forEach((row) => {
            const isInvalid = this.hasMissingElectricitySource(row);

            this.toggleElectricitySourceError(row, isInvalid);

            if (isInvalid && !firstInvalidRow) {
                firstInvalidRow = row;
            }
        });

        if (firstInvalidRow) {
            event.preventDefault();
            firstInvalidRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    hasMissingElectricitySource(row) {
        const marketerField = row.querySelector('[data-field="itemId"]');
        const gdOField = row.querySelector('[data-field="itemId2"]');

        if (!marketerField || !gdOField) return false;

        return !marketerField.value && !gdOField.value;
    }

    toggleElectricitySourceError(row, isInvalid) {
        const marketerField = row.querySelector('[data-field="itemId"]');
        const cell = marketerField.closest('td');
        const wrapper = marketerField.closest('.ts-wrapper') || marketerField;

        wrapper.classList.toggle('is-invalid', isInvalid);

        let feedback = cell.querySelector('[data-electricity-source-feedback]');

        if (isInvalid && !feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback d-block';
            feedback.dataset.electricitySourceFeedback = '';
            feedback.textContent = 'Selecciona una comercializadora o una garantía de origen.';
            cell.appendChild(feedback);
        } else if (!isInvalid && feedback) {
            feedback.remove();
        }
    }

    modeChanged(event) {
        const row = event.target.closest('tr');

        this.applyMode(row);
        this.calculate(row);
    }

    applyMode(row) {
        const modeField = row.querySelector('[data-field="mode"]');

        if (!modeField) return;

        const mode = modeField.value;

        row.querySelectorAll('[data-mode-panel]').forEach((panel) => {
            panel.classList.toggle('d-none', panel.dataset.modePanel !== mode);
        });
    }

    restrictUnit(row) {
        const itemSelect = row.querySelector('[data-field="itemId"]');
        const unitSelect = row.querySelector('[data-field="unit"]');

        if (!itemSelect || !unitSelect) return;

        const option = itemSelect.selectedOptions[0];
        const unit = option ? option.dataset.unit : null;
        const magnitude = option ? option.dataset.magnitude : null;

        for (const unitOption of unitSelect.options) {
            const incompatible = Boolean(magnitude) && unitOption.dataset.magnitude !== magnitude;
            unitOption.disabled = incompatible;
            unitOption.hidden = incompatible;
        }

        const selected = unitSelect.selectedOptions[0];
        const selectedIsCompatible = selected && !selected.disabled;

        if (unit && !selectedIsCompatible) {
            unitSelect.value = unit;
        }
    }

    resolveQuantity(row) {
        const nightsField = row.querySelector('[data-field="nights"]');
        const peopleField = row.querySelector('[data-field="people"]');

        if (!nightsField || !peopleField) {
            return row.querySelector('[data-field="quantity"]')?.value;
        }

        const nights = parseInt(nightsField.value, 10);
        const people = parseInt(peopleField.value, 10);
        const total = nights > 0 && people > 0 ? nights * people : null;
        const totalOutput = row.querySelector('[data-field="quantityTotal"]');

        if (totalOutput) {
            totalOutput.textContent = total !== null ? String(total) : '—';
        }

        return total !== null ? String(total) : null;
    }

    recalculate(event) {
        const row = event.target.closest('tr');

        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.calculate(row), 300);
    }

    async calculate(row) {
        const output = row.querySelector('[data-field="emissions"]');

        if (!output) return;

        const itemId = row.querySelector('[data-field="itemId"]')?.value;
        const itemId2 = row.querySelector('[data-field="itemId2"]')?.value;
        const condition = row.querySelector('[data-field="condition"]')?.value;
        const mode = row.querySelector('[data-field="mode"]')?.value;

        let body;

        if (mode === 'container') {
            const containerCapacity = row.querySelector('[data-field="containerCapacity"]')?.value;
            const containerUnit = row.querySelector('[data-field="containerUnit"]')?.value;
            const fillPercentage = row.querySelector('[data-field="fillPercentage"]')?.value;

            if (!itemId || !containerCapacity || !fillPercentage) {
                output.textContent = '—';
                return;
            }

            body = { type: this.typeValue, itemId, mode, containerCapacity, containerUnit, fillPercentage };
        } else {
            const unit = row.querySelector('[data-field="unit"]')?.value;
            const quantity = this.resolveQuantity(row);

            if ((!itemId && !itemId2) || !quantity) {
                output.textContent = '—';
                return;
            }

            body = { type: this.typeValue, itemId, itemId2, condition, unit, quantity };
        }

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });

            if (!response.ok) {
                output.textContent = '—';
                return;
            }

            const data = await response.json();
            const co2eq = Number(data.emissions.co2eq);

            output.textContent = co2eq.toLocaleString(undefined, { maximumFractionDigits: 2 });
        } catch {
            output.textContent = '—';
        }
    }
}
