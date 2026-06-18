import Alpine from 'alpinejs';
import './jalalidatepicker-init';
import { registerApplicationCsvImport } from './application-csv-import';
import { registerBulkTable } from './bulk-table';
import { registerHrCalendar } from './hr-calendar';
import { registerRecruitmentForm } from './recruitment-form';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    registerBulkTable(Alpine);
    registerHrCalendar(Alpine);
    registerRecruitmentForm(Alpine);

    Alpine.store('theme', {
        dark: localStorage.getItem('theme') !== 'light',

        toggle() {
            this.dark = !this.dark;
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', this.dark);
        },
    });
});

Alpine.data('sidebar', () => ({
    open: false,
    toggleSidebar() {
        this.open = !this.open;
    },
    closeSidebar() {
        this.open = false;
    },
}));

Alpine.data('modal', () => ({
    open: false,
    show() {
        this.open = true;
    },
    hide() {
        this.open = false;
    },
}));

registerApplicationCsvImport(Alpine);

Alpine.start();
