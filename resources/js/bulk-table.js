export function registerBulkTable(Alpine) {
    Alpine.data('bulkTable', (config = {}) => ({
        ids: (config.ids || []).map(String),
        deleteUrl: config.deleteUrl || '',
        selected: [],

        get allSelected() {
            return this.ids.length > 0 && this.selected.length === this.ids.length;
        },

        get isIndeterminate() {
            return this.selected.length > 0 && this.selected.length < this.ids.length;
        },

        toggleAll(checked) {
            this.selected = checked ? [...this.ids] : [];
        },

        submitDelete(event) {
            if (this.selected.length === 0) {
                return;
            }

            const count = this.selected.length;
            const message = `آیا از حذف ${count} مورد انتخاب‌شده مطمئن هستید؟`;

            if (! window.confirm(message)) {
                return;
            }

            const form = event.target;
            form.querySelectorAll('input[data-bulk-id]').forEach((input) => input.remove());

            this.selected.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                input.dataset.bulkId = '1';
                form.appendChild(input);
            });

            form.submit();
        },
    }));
}
