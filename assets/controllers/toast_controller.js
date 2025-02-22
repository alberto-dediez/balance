import { Controller } from '@hotwired/stimulus';

import { Toast } from 'bootstrap';

export default class extends Controller {
    connect() {
        var toast = Toast.getOrCreateInstance(this.element);
        
        toast.show();
    }
}
