/**
 * Opens a server-rendered preview of unsaved editor HTML in a new tab by
 * posting it to admin.pages.preview (rendered like a raw page, relaxed CSP,
 * no tracking pixels).
 *
 * The HTML is base64-encoded so server WAFs (mod_security etc.) don't reject
 * the request for containing raw <script>/<html> markup in a form field.
 */
export function usePagePreview() {
    function toBase64Utf8(value: string): string {
        const bytes = new TextEncoder().encode(value);
        let binary = '';
        bytes.forEach((byte) => (binary += String.fromCharCode(byte)));

        return btoa(binary);
    }

    function openPreview(html: string, title: string): void {
        const previewForm = document.createElement('form');
        previewForm.method = 'POST';
        previewForm.action = route('admin.pages.preview');
        previewForm.target = '_blank';

        const addField = (name: string, value: string) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            previewForm.appendChild(input);
        };

        addField('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
        addField('html_b64', toBase64Utf8(html));
        addField('title', title || 'Preview');

        document.body.appendChild(previewForm);
        previewForm.submit();
        previewForm.remove();
    }

    return { openPreview };
}
