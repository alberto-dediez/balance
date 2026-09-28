import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'list', 'fileTemplate'];

    browse(event) {
        if (event.target === this.inputTarget) {
            return;
        }

        this.inputTarget.click();
    }

    dragOver(event) {
        event.preventDefault();

        this.element.classList.add('is-dragover');
    }

    dragLeave() {
        this.element.classList.remove('is-dragover');
    }

    drop(event) {
        event.preventDefault();

        this.element.classList.remove('is-dragover');

        const files = Array.from(event.dataTransfer.files);

        if (0 === files.length) {
            return;
        }

        const dataTransfer = new DataTransfer();

        (this.inputTarget.multiple ? files : files.slice(0, 1)).forEach(file => dataTransfer.items.add(file));

        this.inputTarget.files = dataTransfer.files;

        this.update();
    }

    update() {
        this.listTarget.innerHTML = '';

        Array.from(this.inputTarget.files).forEach(file => {
            const item = document.createElement('li');
            item.className = 'dropzone__file';

            item.appendChild(this.fileTemplateTarget.content.cloneNode(true));

            const name = document.createElement('span');
            name.className = 'text-truncate';
            name.textContent = file.name;

            const size = document.createElement('span');
            size.className = 'text-muted ms-auto';
            size.textContent = this.formatSize(file.size);

            item.appendChild(name);
            item.appendChild(size);

            this.listTarget.appendChild(item);
        });
    }

    formatSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];

        let size = bytes;
        let unit = 0;

        while (size >= 1024 && unit < units.length - 1) {
            size /= 1024;
            unit++;
        }

        return `${0 === unit ? size : size.toFixed(1)} ${units[unit]}`;
    }
}
