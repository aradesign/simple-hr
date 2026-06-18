export function registerRecruitmentForm(Alpine) {
    Alpine.data('recruitmentForm', (config = {}) => ({
        fields: config.fields || [],
        values: { ...(config.initialValues || {}) },

        init() {
            this.fields
                .filter((field) => field.type === 'list')
                .forEach((field) => {
                    if (! Array.isArray(this.values[field.key])) {
                        this.values[field.key] = Array.isArray(this.values[field.key]) && this.values[field.key].length
                            ? this.values[field.key]
                            : [{}];
                    }
                });

            this.fields
                .filter((field) => field.type === 'checkbox')
                .forEach((field) => {
                    if (! Array.isArray(this.values[field.key])) {
                        this.values[field.key] = this.values[field.key]
                            ? [this.values[field.key]]
                            : [];
                    }
                });

            this.$watch('values.birth_date', () => this.updateAge());
            this.updateAge();
        },

        syncInput(event) {
            const target = event.target;

            if (! target?.name?.startsWith('form_data[')) {
                return;
            }

            const match = target.name.match(/^form_data\[(.+?)\]/);

            if (! match) {
                return;
            }

            if (target.type === 'checkbox') {
                return;
            }

            this.values[match[1]] = target.value;
        },

        updateAge() {
            const birth = this.values.birth_date;
            if (! birth) {
                this.values.age = '';
                return;
            }

            const age = this.ageFromJalali(birth);
            if (age !== null) {
                this.values.age = age;
            }
        },

        ageFromJalali(value) {
            const normalized = String(value).trim();
            const parts = normalized.split(/[/-]/).map(Number);

            if (parts.length !== 3 || parts[0] < 1300) {
                return null;
            }

            try {
                const { toGregorian } = window.jalaali || {};
                if (! toGregorian) {
                    return null;
                }

                const { gy, gm, gd } = toGregorian(parts[0], parts[1], parts[2]);
                const born = new Date(gy, gm - 1, gd);
                const today = new Date();
                let age = today.getFullYear() - born.getFullYear();
                const monthDiff = today.getMonth() - born.getMonth();

                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < born.getDate())) {
                    age -= 1;
                }

                return age;
            } catch {
                return null;
            }
        },

        isVisible(field) {
            const logic = field.conditional_logic;

            if (! logic || ! logic.rules || logic.rules.length === 0) {
                return field.type !== 'hidden';
            }

            const results = logic.rules.map((rule) => this.evaluateRule(rule));
            const matched = logic.match === 'any'
                ? results.some(Boolean)
                : results.every(Boolean);

            const visible = logic.action === 'show' ? matched : ! matched;

            return visible && field.type !== 'hidden';
        },

        evaluateRule(rule) {
            const key = rule.field_key || rule.field_id;
            const actual = this.values[key];
            const expected = rule.value ?? '';
            const operator = rule.operator ?? 'is';

            if (Array.isArray(actual)) {
                if (operator === 'is') {
                    return actual.includes(expected);
                }

                if (operator === 'isnot') {
                    return ! actual.includes(expected);
                }

                return false;
            }

            const actualString = actual === undefined || actual === null ? '' : String(actual);

            switch (operator) {
            case 'is':
                return actualString === String(expected);
            case 'isnot':
                return actualString !== String(expected);
            case '>':
                return Number(actualString) > Number(expected);
            case '<':
                return Number(actualString) < Number(expected);
            case 'contains':
                return actualString.includes(String(expected));
            default:
                return false;
            }
        },

        listRows(field) {
            if (! Array.isArray(this.values[field.key]) || this.values[field.key].length === 0) {
                this.values[field.key] = [{}];
            }

            return this.values[field.key];
        },

        addListRow(field) {
            if (! Array.isArray(this.values[field.key])) {
                this.values[field.key] = [];
            }

            this.values[field.key].push({});
        },

        removeListRow(field, index) {
            this.values[field.key].splice(index, 1);

            if (this.values[field.key].length === 0) {
                this.values[field.key].push({});
            }
        },

        inlineClass(field) {
            return (field.css_class || '').includes('gf_list_inline')
                ? 'flex flex-wrap gap-4'
                : 'space-y-2';
        },
    }));
}
