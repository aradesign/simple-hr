@php
    $importConfig = [
        'uploadUrl' => route('admin.applications.import.store'),
        'processUrlTemplate' => route('admin.applications.import.process', ['importId' => '__IMPORT_ID__']),
    ];
@endphp

<x-modal name="import-applications-csv" title="بارگذاری درخواست‌ها از CSV" maxWidth="2xl">
    <div
        x-data="applicationCsvImport(@js($importConfig))"
        class="space-y-5"
    >
        <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 p-4 text-sm text-slate-600 dark:text-slate-300 leading-6">
            <p>فایل خروجی گرویتی‌فرمز (همان فرمت قبلی) را بارگذاری کنید.</p>
            <p class="mt-2">تاریخ تولد و کد ملی به‌صورت خودکار اصلاح و اعتبارسنجی می‌شوند. ردیف‌های تکراری رد می‌شوند.</p>
        </div>

        <div x-show="!uploading && !importing && !completed">
            <label class="block text-sm font-medium mb-2">فایل CSV</label>
            <input
                x-ref="fileInput"
                type="file"
                accept=".csv,text/csv"
                class="cyber-input w-full rounded-lg px-3 py-2 text-sm"
                @change="onFileSelected($event)"
            >
            <p x-show="fileName" x-text="fileName" class="text-xs text-slate-500 mt-2"></p>
        </div>

        <div x-show="uploading || importing || completed" class="space-y-3" style="display: none;">
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600 dark:text-slate-300">پیشرفت بارگذاری</span>
                <span class="font-medium text-slate-900 dark:text-white" x-text="`${processed} / ${total}`"></span>
            </div>

            <div class="h-3 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                <div
                    class="h-full rounded-full bg-cyan-600 dark:bg-cyan-500 transition-all duration-300"
                    x-bind:style="`width: ${percent}%`"
                ></div>
            </div>

            <p class="text-xs text-slate-500" x-show="currentLabel" x-text="`در حال پردازش: ${currentLabel}`"></p>

            <div class="grid grid-cols-3 gap-3 text-center text-sm">
                <div class="rounded-lg border border-emerald-200 dark:border-emerald-800/50 bg-emerald-50 dark:bg-emerald-950/30 p-3">
                    <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300" x-text="imported + updated"></div>
                    <div class="text-emerald-600 dark:text-emerald-400">
                        <span x-text="imported"></span> جدید /
                        <span x-text="updated"></span> به‌روز
                    </div>
                </div>
                <div class="rounded-lg border border-amber-200 dark:border-amber-800/50 bg-amber-50 dark:bg-amber-950/30 p-3">
                    <div class="text-lg font-bold text-amber-700 dark:text-amber-300" x-text="skipped"></div>
                    <div class="text-amber-600 dark:text-amber-400">رد شده</div>
                </div>
                <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 p-3">
                    <div class="text-lg font-bold text-slate-800 dark:text-slate-200" x-text="errors.length"></div>
                    <div class="text-slate-600 dark:text-slate-400">خطا</div>
                </div>
            </div>
        </div>

        <div x-show="completed" class="rounded-lg border border-emerald-200 dark:border-emerald-800/50 bg-emerald-50 dark:bg-emerald-950/20 p-4 text-sm text-emerald-800 dark:text-emerald-200" style="display: none;">
            بارگذاری کامل شد. <span x-text="`${imported} درخواست جدید ثبت شد.`"></span>
        </div>

        <div x-show="errors.length > 0" class="rounded-lg border border-red-200 dark:border-red-800/50 bg-red-50 dark:bg-red-950/20 p-4" style="display: none;">
            <p class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">خطاهای import</p>
            <ul class="space-y-1 text-xs text-red-700 dark:text-red-300 max-h-32 overflow-y-auto">
                <template x-for="(item, index) in errors" :key="index">
                    <li x-text="item"></li>
                </template>
            </ul>
        </div>

        <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>

        <div class="flex items-center justify-end gap-2 pt-2">
            <button
                type="button"
                @click="completed ? reloadPage() : window.dispatchEvent(new CustomEvent('close-modal', { detail: 'import-applications-csv' }))"
                class="inline-flex items-center justify-center font-medium rounded-lg transition-colors duration-150 px-4 py-2 text-sm bg-transparent hover:bg-slate-100 text-slate-700 dark:hover:bg-cyan-500/10 dark:text-cyan-400 border border-slate-300 dark:border-transparent"
            >
                <span x-text="completed ? 'بستن و به‌روزرسانی لیست' : 'انصراف'">انصراف</span>
            </button>
            <button
                type="button"
                x-show="!completed"
                x-bind:disabled="!canStart"
                @click="startImport()"
                class="inline-flex items-center justify-center font-medium rounded-lg transition-colors duration-150 px-4 py-2 text-sm bg-cyan-600 hover:bg-cyan-700 text-white disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span x-show="!uploading && !importing">شروع بارگذاری</span>
                <span x-show="uploading" style="display: none;">در حال آپلود…</span>
                <span x-show="importing" style="display: none;">در حال import…</span>
            </button>
        </div>
    </div>
</x-modal>
