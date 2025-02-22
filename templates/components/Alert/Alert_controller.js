import { Controller } from "@hotwired/stimulus";

import('./Alert.scss');

export default class extends Controller {
    connect() {
        console.log('Alert controller connected!');
    }
}