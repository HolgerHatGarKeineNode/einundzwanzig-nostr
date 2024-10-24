<div
    wire:ignore
    x-data="{
        value: $wire.entangle('{{ $model }}'),
        init() {
            this.$nextTick(() => {
                setTimeout(() => {
                    let editor = new EasyMDE({
                        element: this.$refs.editor,
                        lineNumbers: true,
                        uploadImage: false,
                        spellChecker: false,
                        showIcons: [
                            'heading',
                            'heading-smaller',
                            'heading-bigger',
                            'heading-1',
                            'heading-2',
                            'heading-3',
                            'code',
                            'table',
                            'quote',
                            'strikethrough',
                            'unordered-list',
                            'ordered-list',
                            'clean-block',
                            'horizontal-rule',
                            'undo',
                            'redo',
                            //'upload-image',
                        ],
                    });

                    editor.value(this.value);

                    editor.codemirror.on('change', () => {
                        this.value = editor.value();
                    });
                }, 100); // Adjust the delay as needed
            });
        },
    }"
    class="w-full"
>
    <div class="prose max-w-none">
        <textarea x-ref="editor"></textarea>
    </div>
    <style>
        .EasyMDEContainer {
            background-color: white;
        }
    </style>
</div>
