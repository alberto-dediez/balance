import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String,
        interval: { type: Number, default: 2000 },
        errorInterval: { type: Number, default: 3000 },
    };

    static targets = ['bar', 'step'];

    timer = null;

    connect() {
        this.schedule(this.intervalValue);
    }

    disconnect() {
        clearTimeout(this.timer);
    }

    schedule(delay) {
        this.timer = setTimeout(() => this.poll(), delay);
    }

    async poll() {
        try {
            const response = await fetch(this.urlValue, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();

            if (data) {
                this.updateBar(data.percentage, data.currentStep);

                if (data.status === 'Completed' || data.status === 'Failed') {
                    if (data.status === 'Completed') {
                        this.updateBar(100, data.currentStep);
                    }

                    window.location.reload();
                    return;
                }
            }
        } catch {
            this.schedule(this.errorIntervalValue);
            return;
        }

        this.schedule(this.intervalValue);
    }

    updateBar(percentage, step) {
        if (this.hasBarTarget) {
            const pct = percentage + '%';
            this.barTarget.style.width = pct;
            this.barTarget.textContent = pct;
            this.barTarget.setAttribute('aria-valuenow', String(percentage));
        }

        if (this.hasStepTarget) {
            this.stepTarget.textContent = step || '\u00a0';
        }
    }
}
