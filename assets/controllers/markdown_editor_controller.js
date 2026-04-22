import { Controller } from '@hotwired/stimulus';
import Editor from '@toast-ui/editor';
import '@toast-ui/editor/dist/toastui-editor.min.css';

export default class extends Controller {
    static targets = ['container', 'input'];
    static values = {
        height: { type: String, default: '400px' },
        initialEditType: { type: String, default: 'wysiwyg' },
        hideImage: { type: Boolean, default: true },
        hideLink: { type: Boolean, default: true },
        hideModeSwitch: { type: Boolean, default: true },
    };

    connect() {
        const toolbarItems = [
            ['heading', 'bold', 'italic', 'strike'],
            ['hr', 'quote'],
            ['ul', 'ol', 'task', 'indent', 'outdent'],
            ['table', ...(!this.hideImageValue ? ['image'] : []), ...(!this.hideLinkValue ? ['link'] : [])],
            ['code', 'codeblock'],
        ].filter(group => group.length > 0);

        this.editor = new Editor({
            el: this.containerTarget,
            height: this.heightValue,
            initialEditType: this.initialEditTypeValue,
            initialValue: this.inputTarget.value,
            previewStyle: 'vertical',
            toolbarItems,
            hideModeSwitch: this.hideModeSwitchValue,
        });

        this.editor.on('change', () => {
            this.inputTarget.value = this.editor.getMarkdown();
        });
    }

    disconnect() {
        if (this.editor) {
            this.editor.destroy();
            this.editor = null;
        }
    }
}
