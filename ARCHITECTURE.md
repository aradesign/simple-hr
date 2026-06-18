# HR Portal V2 — معماری سیستم

## ۱. نمای کلی

**HR Portal V2** یک **People Lifecycle Platform** است؛ نه صرفاً ATS و نه صرفاً پنل پرسنلی. هر انسان در سیستم **یک پرونده (Person)** دارد و تمام رویدادهای چرخه عمر — از اولین درخواست استخدام تا پایان همکاری — در همان پرونده ثبت می‌شود.

### اصل طراحی دامنه

```
Person (Aggregate Root)
├── Lifecycle States: applicant → interviewed → accepted → employee → former_employee
├── Contact & Profile Data
├── Employment Applications
├── Interviews
├── Documents (versioned)
├── Calendar Events
├── Assignments (assignee, reviewer, collaborator)
├── Notifications
└── Audit Trail
```

**Applicant** و **Employee** موجودیت مستقل نیستند؛ وضعیت‌های `Person` هستند که در فیلد `lifecycle_status` و جداول مرتبط (مثل `employment_records`) بازتاب می‌یابند.

---

## ۲. لایه‌بندی معماری (Clean Architecture + DDD Lite)

```
┌─────────────────────────────────────────────────────────┐
│  Presentation Layer                                     │
│  Blade Views · AlpineJS · TailwindCSS · Controllers     │
├─────────────────────────────────────────────────────────┤
│  Application Layer                                      │
│  Services · DTOs · Form Requests · Policies · Events    │
├─────────────────────────────────────────────────────────┤
│  Domain Layer                                           │
│  Models · Enums · Value Objects · Domain Events         │
├─────────────────────────────────────────────────────────┤
│  Infrastructure Layer                                   │
│  Migrations · Notifications · SMS Gateway · Storage   │
└─────────────────────────────────────────────────────────┘
```

### ساختار پوشه‌ها

```
app/
├── Domain/
│   ├── Enums/              # PersonLifecycleStatus, DocumentType, ...
│   ├── Events/             # PersonStatusChanged, InterviewScheduled, ...
│   └── ValueObjects/       # PhoneNumber, NationalId (در صورت نیاز)
├── DTOs/                   # Data transfer objects برای سرویس‌ها
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # HR Panel
│   │   ├── Recruitment/    # Public applicant flow
│   │   └── Portal/         # Employee self-service
│   ├── Middleware/
│   └── Requests/           # Form Request validation
├── Models/                 # Eloquent models
├── Policies/
├── Services/
│   ├── Person/
│   ├── Recruitment/
│   ├── Interview/
│   ├── Calendar/
│   ├── Document/
│   ├── Notification/
│   ├── Otp/
│   └── Audit/
├── Listeners/              # Event-driven notification & audit
└── Observers/              # Model lifecycle hooks
```

Repository Pattern فقط در صورت پیچیدگی واقعی (مثلاً گزارش‌گیری سنگین) اضافه می‌شود؛ در فاز اول Eloquent مستقیم در Service Layer کافی است.

---

## ۳. ماژول‌ها و مرزهای دامنه

### ۳.۱ Recruitment Module

| مسئولیت | جزئیات |
|---------|--------|
| احراز هویت | OTP از طریق موبایل |
| درخواست استخدام | فرم چندمرحله‌ای Schema-Driven |
| پیگیری | متقاضی وضعیت درخواست را می‌بیند |
| مدیریت HR | فیلتر پیشرفته، PDF، CSV |

**سرویس‌های کلیدی:** `OtpService`, `ApplicationService`, `ApplicationFormSchemaService`

**جریان OTP:**
```
User → Request OTP → OtpService (store hashed code) → SMS Channel
User → Verify OTP → Session/Token → Access granted
```

### ۳.۲ Interview Module

مصاحبه entity مستقل است ولی به `Person` و `EmploymentApplication` متصل است.

- انواع: حضوری (`in_person`)، آنلاین (`online`)
- نتیجه: قبول / رد / مردد / نیاز به مصاحبه بعدی
- تغییر وضعیت درخواست پس از ثبت نتیجه
- ارسال اعلان از طریق Event

**سرویس:** `InterviewService`

### ۳.۳ HR Calendar

تقویم **مستقل** از Interview طراحی شده؛ Interview فقط یک `event_type` است.

| Event Type | توضیح |
|------------|-------|
| `interview` | مصاحبه |
| `birthday` | تولد پرسنل |
| `contract_end` | پایان قرارداد |
| `contract_renewal` | تمدید قرارداد |
| `probation_end` | پایان دوره آزمایشی |
| `training` | آموزش |
| `hr_event` | رویداد عمومی HR |

**سرویس:** `CalendarService` — تولید خودکار رویدادهای تکراری (تولد، انقضای قرارداد) از طریق Scheduled Command.

### ۳.۴ Personnel Module

- ایجاد پرسنل مستقیم یا تبدیل Applicant → Employee
- `EmploymentRecord` برای سوابق همکاری
- اتصال به Department

**سرویس:** `PersonService`, `EmploymentService`

### ۳.۵ Employee Portal

مسیر جدا با OTP؛ دسترسی محدود به داده‌های خود Person.

### ۳.۶ Document Center

`Document` موجودیت مستقل با:
- نسخه‌بندی (`document_versions`)
- تاریخ بارگذاری و انقضا
- ثبت‌کننده (`uploaded_by`)
- انواع: قرارداد، حکم، مدرک تحصیلی، کارت ملی، شناسنامه، گواهی، عمومی

**سرویس:** `DocumentService`

### ۳.۷ Department Management

CRUD کامل با: فعال/غیرفعال، ترتیب نمایش، مدیر، اعضا (many-to-many با Person).

### ۳.۸ HR Permissions

| نقش | سطح دسترسی |
|-----|-------------|
| `candidate` | متقاضی (OTP) |
| `employee` | پرسنل |
| `hr` | دسترسی HR (مستقل از دپارتمان) |
| `hr_manager` | مدیر HR |
| `super_admin` | مدیر سیستم |

- `User` مدل احراز هویت Laravel
- هر `User` می‌تواند به یک `Person` متصل باشد
- `hr_access` flag مستقل از department
- Spatie Permission یا پیاده‌سازی ساده با enum `role` + Policies

### ۳.۹ Assignment System

Polymorphic `assignments` table:
- `assignable_type` + `assignable_id` (Person, EmploymentApplication, ...)
- `user_id` + `role`: assignee | reviewer | collaborator

### ۳.۱۰ Notification Center (Event-Driven)

```
Domain Event → Listener → NotificationService
                              ├── InAppNotification
                              ├── SmsNotification
                              └── EmailNotification (future)
```

جدول `notifications` (Laravel built-in) + جدول `notification_logs` برای SMS.

### ۳.۱۱ Audit Log

`AuditLog` برای: create, update, delete, download, status_change, message_sent

از Observer و Event Listener ثبت می‌شود.

---

## ۴. فرم استخدام (Schema-Driven)

جدول `application_form_fields`:
- `key`, `label`, `type`, `options` (JSON), `order`, `is_visible`, `is_required`, `step`

مدیر فقط ترتیب، نمایش و گزینه‌ها را مدیریت می‌کند؛ بدون Form Builder پیچیده.

پاسخ‌ها در `employment_applications.form_data` (JSON) ذخیره می‌شوند.

---

## ۵. احراز هویت

| مسیر | روش |
|------|-----|
| HR Panel | Email/Password + Session (Laravel Breeze-style) |
| Recruitment | OTP موبایل |
| Employee Portal | OTP موبایل |

`OtpService`:
- کد ۶ رقمی
- Hash در DB
- TTL ۲ دقیقه
- Rate limiting

---

## ۶. تاریخ و زمان

| لایه | فرمت |
|------|------|
| Database | UTC/Gregorian (`datetime`) |
| UI | Jalali (شمسی) — پکیج `morilog/jalali` |
| DatePicker | Alpine + Persian date component محلی |

---

## ۷. Frontend Architecture

- **Blade Components:** `<x-layout>`, `<x-sidebar>`, `<x-header>`, `<x-card>`, `<x-table>`, `<x-modal>`, `<x-drawer>`, `<x-badge>`, `<x-jalali-date>`
- **AlpineJS:** تعاملات UI (modal, drawer, dark mode toggle, date picker)
- **TailwindCSS:** utility-first، RTL با `dir="rtl"`
- **Vite:** bundle محلی — بدون CDN
- **Fonts:** Vazirmatn (local woff2)
- **Icons:** Heroicons SVG inline یا sprite محلی

### Dark/Light Mode
`localStorage` + `class="dark"` on `<html>` + Tailwind `dark:` variants

---

## ۸. API & Routes Structure

```
/                           → Redirect
/recruitment/               → Applicant OTP + Application
/portal/                    → Employee OTP + Self-service
/admin/                     → HR Dashboard (auth required)
    /dashboard
    /persons
    /applications
    /interviews
    /calendar
    /documents
    /departments
    /users
    /settings/form-fields
    /audit-logs
```

---

## ۹. Testing Strategy (Pest)

- Feature Tests: OTP flow, application submission, interview result, person lifecycle
- Unit Tests: DTOs, Enums, OtpService
- Policy Tests: role-based access

---

## ۱۰. Deployment Notes

| Environment | Database |
|-------------|----------|
| Development | SQLite |
| Production | MySQL |

`.env` switching؛ migrations سازگار با هر دو.

---

## ۱۱. ERD خلاصه

```
users ────────────── persons ────────────── employment_records
  │                      │                         │
  │                      ├── employment_applications
  │                      ├── interviews
  │                      ├── documents ── document_versions
  │                      ├── person_educations
  │                      ├── person_work_experiences
  │                      ├── person_family_members
  │                      └── department_person (pivot)
  │
departments ──────────── department_person
  │
calendar_events ──────── (polymorphic link to person/interview)
assignments ──────────── (polymorphic)
audit_logs ───────────── (polymorphic)
application_form_fields
otp_codes
notification_logs
```

جزئیات کامل در `DATABASE_DESIGN.md`.

---

## ۱۲. اصول SOLID در این پروژه

| اصل | پیاده‌سازی |
|-----|------------|
| SRP | هر Service یک مسئولیت (InterviewService فقط مصاحبه) |
| OCP | Event/Listener برای افزودن کانال اعلان جدید |
| LSP | Policies یکسان برای انواع assignable |
| ISP | DTOهای کوچک به‌جای آرایه‌های بزرگ |
| DIP | Serviceها از Interface برای SMS/Storage در آینده |

---

## ۱۳. فازبندی پیاده‌سازی

1. ✅ معماری و مستندات
2. Migrations + Models + Enums
3. Core Services (Person, Otp, Application)
4. HR Admin Panel (Dashboard, CRUD)
5. Recruitment Public Flow
6. Interview + Calendar
7. Document Center
8. Employee Portal
9. Notifications + Audit
10. Seeders + Tests
