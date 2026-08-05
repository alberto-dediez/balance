import { Controller } from '@hotwired/stimulus';

import TomSelect from 'tom-select';

import 'tom-select/dist/css/tom-select.bootstrap5.min.css';

export default class extends Controller {
    static values = {
        path: String,
        selectedLabel: String
    }

    connect() {
        let options = {
            'create': false,
            // On a text input TomSelect would otherwise allow unlimited items.
            'maxItems': this.element.multiple ? null : 1,
        };

        if (this.pathValue.length > 0) {
            var baseUrl = this.pathValue;

            options.valueField = 'value';
            options.labelField = 'label';
            options.searchField = 'label';
            options.load = function(query, callback) {
                var separator = baseUrl.indexOf('?') === -1 ? '?' : '&';
                var url = baseUrl + separator + 'query=' + encodeURIComponent(query);

                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        callback(json);
                    }).catch(()=>{
                        callback();
                    });

            };

            if (this.element.value && this.element.value.length > 0) {
                options.options = [{
                    value: this.element.value,
                    label: this.selectedOptionLabel()
                }];
                options.items = [this.element.value];
            }
        }

        new TomSelect(this.element, options);
    }

    // Passing options to TomSelect discards the ones read from the <select>,
    // so the label of the pre-selected option has to be carried over.
    selectedOptionLabel() {
        if (this.selectedLabelValue.length > 0) {
            return this.selectedLabelValue;
        }

        const selected = this.element.querySelector
            ? this.element.querySelector('option[value="' + CSS.escape(this.element.value) + '"]')
            : null;

        if (selected && selected.textContent.trim().length > 0) {
            return selected.textContent.trim();
        }

        return this.element.value;
    }
}
