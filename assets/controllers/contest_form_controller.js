import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['participation', 'award', 'winnersCount', 'platformDraw', 'warning'];

    connect() {
        this.refresh();
    }

    refresh() {
        if (!this.hasAwardTarget) {
            return;
        }

        const award = this.awardTarget.value;
        const participation = this.hasParticipationTarget ? this.participationTarget.value : '';

        if (this.hasWinnersCountTarget) {
            this.winnersCountTarget.classList.toggle('d-none', award === 'everyone');
        }

        if (this.hasPlatformDrawTarget) {
            this.platformDrawTarget.classList.toggle('d-none', award !== 'draw');
        }

        if (this.hasWarningTarget) {
            this.warningTarget.classList.toggle('d-none', !(award === 'draw' && participation === 'compensation'));
        }
    }
}
