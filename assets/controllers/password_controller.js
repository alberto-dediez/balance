import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        
    }

    toogleVisibility() {
        let pwd = this.element.querySelector('input');

        if (pwd.getAttribute('type') === 'password') {
            pwd.setAttribute('type', 'text');
            this.element.querySelector('.see').classList.add('d-none');
            this.element.querySelector('.hide').classList.remove('d-none');
        } else {
            pwd.setAttribute('type', 'password');
            this.element.querySelector('.see').classList.remove('d-none');
            this.element.querySelector('.hide').classList.add('d-none');
        }        
    }
}
