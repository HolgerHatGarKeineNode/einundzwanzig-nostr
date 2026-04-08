@script
<script>
(() => {
    const MARKDOWN_PATTERNS = [
        /^\s{0,3}#{1,6}\s+\S/m,
        /^\s{0,3}[-*+]\s+\S/m,
        /^\s{0,3}\d{1,9}[.)]\s+\S/m,
        /^\s{0,3}>\s?/m,
        /^\s{0,3}```/m,
        /^\s*\S.*\n={3,}\s*$/m,
        /^\s*\S.*\n-{3,}\s*$/m,
    ];

    const looksLikeMarkdown = (text) => MARKDOWN_PATTERNS.some((re) => re.test(text));

    const insertHtml = (editorEl, html) => {
        const tiptap = editorEl.editor;

        if (tiptap && tiptap.commands && typeof tiptap.commands.insertContent === 'function') {
            tiptap.commands.insertContent(html);
            return true;
        }

        const editable = editorEl.querySelector('[contenteditable="true"]');
        if (!editable) {
            return false;
        }

        if (editable !== document.activeElement) {
            editable.focus();
        }

        return document.execCommand('insertHTML', false, html);
    };

    const attach = (editorEl) => {
        if (editorEl.dataset.mdPasteBound === '1') {
            return;
        }
        editorEl.dataset.mdPasteBound = '1';

        editorEl.addEventListener(
            'paste',
            async (event) => {
                const clipboard = event.clipboardData;
                if (!clipboard) {
                    return;
                }

                const plain = clipboard.getData('text/plain');
                if (!plain || !looksLikeMarkdown(plain)) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();
                event.stopPropagation();

                try {
                    const rendered = await $wire.convertMarkdownToHtml(plain);
                    if (!rendered) {
                        return;
                    }

                    insertHtml(editorEl, rendered);
                } catch (error) {
                    console.error('Markdown paste conversion failed', error);
                }
            },
            true,
        );
    };

    const scan = (root) => {
        (root || document).querySelectorAll('[data-flux-editor]').forEach(attach);
    };

    scan(document);

    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== 1) {
                    return;
                }
                if (node.matches && node.matches('[data-flux-editor]')) {
                    attach(node);
                }
                scan(node);
            });
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
})();
</script>
@endscript
