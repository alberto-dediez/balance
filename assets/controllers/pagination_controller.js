import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        
    }

    limit(event) {
        let link = event.target;

        if (link.tagName != 'a') {
            link = link.closest('a');
        }

        if (link.classList.contains('active')) {
            return ;
        }

        let limit = document.querySelector('input[name*="filter[limit]"]');
        limit.value = link.getAttribute('data-limit');

        limit.dispatchEvent(new Event("change"));

        limit.closest('form').submit();
    }

    page(event) {
        let link = event.target;

        if (link.tagName != 'a') {
            link = link.closest('a');
        }

        if (link.classList.contains('disabled')) {
            return ;
        }

        let page = document.querySelector('input[name*="filter[page]"]');
        page.value = link.getAttribute('data-page') - 1;
        page.closest('form').submit();
    }
}
