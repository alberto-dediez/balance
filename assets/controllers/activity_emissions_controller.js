import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { url: String, type: String };

    connect() {
        this.element.querySelectorAll('tbody tr').forEach((row) => this.restrictUnit(row));
    }

    itemChanged(event) {
        const row = event.target.closest('tr');

        this.restrictUnit(row);
        this.calculate(row);
    }

    restrictUnit(row) {
        const itemSelect = row.querySelector('[data-field="itemId"]');
        const unitSelect = row.querySelector('[data-field="unit"]');

        if (!itemSelect || !unitSelect) return;

        const option = itemSelect.selectedOptions[0];
        const unit = option ? option.dataset.unit : null;

        for (const unitOption of unitSelect.options) {
            unitOption.disabled = Boolean(unit) && unitOption.value !== unit;
        }

        if (unit) {
            unitSelect.value = unit;
        }
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
        const unit = row.querySelector('[data-field="unit"]')?.value;
        const quantity = row.querySelector('[data-field="quantity"]')?.value;

        if (!itemId || !quantity) {
            output.textContent = '—';
            return;
        }

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ type: this.typeValue, itemId, itemId2, condition, unit, quantity }),
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
