import { Controller } from '@hotwired/stimulus';

import TomSelect from 'tom-select';

import 'tom-select/dist/css/tom-select.bootstrap5.min.css';

export default class extends Controller {
    static values = {
        path: String,
        selectedLabel: String,
        submitOnChange: Boolean,
        preload: Boolean
    }

    connect() {
        let options = {
            'create': false,
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

            if (this.preloadValue) {
                options.preload = 'focus';
            }

            if (this.element.value && this.element.value.length > 0) {
                options.options = [{
                    value: this.element.value,
                    label: this.selectedOptionLabel()
                }];
                options.items = [this.element.value];
            }
        }

        if (this.submitOnChangeValue) {
            options.onChange = () => {
                if (this.element.form) {
                    this.element.form.requestSubmit();
                }
            };
        }

        new TomSelect(this.element, options);
    }

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
