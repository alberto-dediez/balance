import { Controller } from '@hotwired/stimulus';

import TomSelect from 'tom-select';

import 'tom-select/dist/css/tom-select.bootstrap5.min.css';

export default class extends Controller {
    connect() {
        new TomSelect(this.element, {});
    }
}
