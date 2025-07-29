import { Controller } from '@hotwired/stimulus';

import TomSelect from 'tom-select';

import 'tom-select/dist/css/tom-select.bootstrap5.min.css';

export default class extends Controller {
    static values = {
        path: String
    }

    connect() {
        let options = {
            'create': false,
        };

        if (this.pathValue.length > 0) {
            var baseUrl = this.pathValue;

            options.valueField = 'value';
            options.labelField = 'label';
            options.searchField = 'label';
            options.load = function(query, callback) {
                var url = baseUrl + '?query=' + encodeURIComponent(query);
                
                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        callback(json);
                    }).catch(()=>{
                        callback();
                    });

            };
        }

        new TomSelect(this.element, options);
    }
}
