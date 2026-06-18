export function registerApplicationCsvImport(Alpine) {
    Alpine.data('applicationCsvImport', (config = {}) => ({
        uploadUrl: config.uploadUrl || '',
        processUrlTemplate: config.processUrlTemplate || '',
        idle: true,
        uploading: false,
        importing: false,
        completed: false,
        error: null,
        fileName: null,
        importId: null,
        total: 0,
        processed: 0,
        imported: 0,
        updated: 0,
        skipped: 0,
        percent: 0,
        currentLabel: null,
        errors: [],

        get canStart() {
            return this.fileName && ! this.uploading && ! this.importing && ! this.completed;
        },

        onFileSelected(event) {
            const file = event.target.files?.[0];

            this.resetState(false);
            this.fileName = file ? file.name : null;
            this.error = null;
        },

        resetState(full = true) {
            this.uploading = false;
            this.importing = false;
            this.completed = false;
            this.error = null;
            this.importId = null;
            this.total = 0;
            this.processed = 0;
            this.imported = 0;
            this.updated = 0;
            this.skipped = 0;
            this.percent = 0;
            this.currentLabel = null;
            this.errors = [];

            if (full) {
                this.fileName = null;
                this.idle = true;
            }
        },

        async startImport() {
            const input = this.$refs.fileInput;

            if (! input?.files?.[0]) {
                this.error = 'لطفاً یک فایل CSV انتخاب کنید.';

                return;
            }

            this.error = null;
            this.uploading = true;
            this.idle = false;

            try {
                const upload = await this.uploadFile(input.files[0]);
                this.importId = upload.import_id;
                this.total = upload.total;
                this.uploading = false;
                this.importing = true;

                await this.runBatches();
            } catch (exception) {
                this.error = exception.message || 'خطا در بارگذاری فایل.';
                this.uploading = false;
                this.importing = false;
            }
        },

        async uploadFile(file) {
            const body = new FormData();
            body.append('file', file);

            const response = await fetch(this.uploadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    Accept: 'application/json',
                },
                body,
            });

            const payload = await response.json();

            if (! response.ok) {
                throw new Error(payload.message || 'بارگذاری فایل ناموفق بود.');
            }

            return payload;
        },

        async runBatches() {
            while (! this.completed) {
                const response = await fetch(this.processUrl(this.importId), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        Accept: 'application/json',
                    },
                });

                const payload = await response.json();

                if (! response.ok) {
                    throw new Error(payload.message || 'پردازش import ناموفق بود.');
                }

                this.applyProgress(payload);

                if (payload.completed) {
                    this.importing = false;
                    this.completed = true;

                    break;
                }
            }
        },

        applyProgress(payload) {
            this.processed = payload.processed ?? 0;
            this.imported = payload.imported ?? 0;
            this.updated = payload.updated ?? 0;
            this.skipped = payload.skipped ?? 0;
            this.percent = payload.percent ?? 0;
            this.currentLabel = payload.current_label ?? null;
            this.errors = payload.errors ?? [];
        },

        processUrl(importId) {
            return this.processUrlTemplate.replace('__IMPORT_ID__', importId);
        },

        reloadPage() {
            window.location.reload();
        },
    }));
}
