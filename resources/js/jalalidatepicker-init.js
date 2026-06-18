import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css';
import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js';
import { parseJalaliInput } from './jalali-utils';

let started = false;

function syncHiddenField(visibleInput) {
    const targetId = visibleInput.dataset.jdpTarget;
    if (!targetId) {
        return;
    }

    const hidden = document.getElementById(targetId);
    if (!hidden) {
        return;
    }

    const mode = visibleInput.dataset.jdpMode || 'date';
    hidden.value = parseJalaliInput(visibleInput.value, mode);
    hidden.dispatchEvent(new Event('change', { bubbles: true }));
}

function bindInput(input) {
    if (input.dataset.jdpBound === '1') {
        return;
    }

    input.dataset.jdpBound = '1';
    input.addEventListener('change', () => syncHiddenField(input));
    input.addEventListener('jdp:change', () => syncHiddenField(input));
}

function bindAll() {
    document.querySelectorAll('input[data-jdp][data-jdp-target]').forEach(bindInput);
}

export function initJalaliDatepicker() {
    const jdp = window.jalaliDatepicker;

    if (!jdp) {
        return;
    }

    if (!started) {
        jdp.startWatch({
            time: true,
            hasSecond: false,
            persianDigits: false,
            useDropDownYears: true,
            autoShow: true,
            autoHide: true,
            hideAfterChange: true,
            showTodayBtn: true,
            showEmptyBtn: true,
            separatorChars: {
                date: '/',
                between: ' ',
                time: ':',
            },
            zIndex: 1100,
        });
        started = true;
    } else {
        jdp.updateOptions?.({});
    }

    bindAll();
    document.querySelectorAll('input[data-jdp][data-jdp-target]').forEach(syncHiddenField);
}

document.addEventListener('DOMContentLoaded', initJalaliDatepicker);
document.addEventListener('submit', (event) => {
    event.target?.querySelectorAll?.('input[data-jdp][data-jdp-target]')?.forEach(syncHiddenField);
}, true);
window.initJalaliDatepicker = initJalaliDatepicker;

// MutationObserver for dynamically shown forms (modals)
const observer = new MutationObserver(() => bindAll());
observer.observe(document.documentElement, { childList: true, subtree: true });
