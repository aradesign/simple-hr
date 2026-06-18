# HR Portal V2 — طراحی پایگاه داده

## ERD (Mermaid)

```mermaid
erDiagram
    users ||--o| persons : "has"
    persons ||--o{ employment_applications : "submits"
    persons ||--o{ employment_records : "has"
    persons ||--o{ interviews : "attends"
    persons ||--o{ documents : "owns"
    persons ||--o{ person_educations : "has"
    persons ||--o{ person_work_experiences : "has"
    persons ||--o{ person_family_members : "has"
    persons }o--o{ departments : "belongs_to"
    departments ||--o| users : "managed_by"
    employment_applications ||--o{ interviews : "has"
    documents ||--o{ document_versions : "has"
    users ||--o{ document_versions : "uploaded_by"
    calendar_events }o--o| persons : "related"
    calendar_events }o--o| interviews : "related"
    assignments }o--|| users : "assigned_to"
    audit_logs }o--o| users : "performed_by"
    users ||--o{ otp_codes : "receives"
    application_form_fields ||--|| application_form_fields : "ordered"

    users {
        bigint id PK
        string name
        string email UK
        string password
        enum role
        boolean hr_access
        bigint person_id FK
        timestamps
    }

    persons {
        bigint id PK
        string first_name
        string last_name
        string national_id UK
        string mobile UK
        date birth_date
        enum gender
        enum lifecycle_status
        enum marital_status
        text address
        string city
        string province
        string postal_code
        string profile_photo
        text notes
        timestamps
        soft_deletes
    }

    departments {
        bigint id PK
        string name
        string code UK
        text description
        boolean is_active
        int sort_order
        bigint manager_id FK
        timestamps
        soft_deletes
    }

    department_person {
        bigint id PK
        bigint department_id FK
        bigint person_id FK
        date joined_at
        date left_at
        boolean is_primary
        timestamps
    }

    employment_applications {
        bigint id PK
        bigint person_id FK
        string application_number UK
        enum status
        json form_data
        int current_step
        bigint assigned_to FK
        bigint reviewer_id FK
        timestamp submitted_at
        timestamp reviewed_at
        text hr_notes
        timestamps
        soft_deletes
    }

    employment_records {
        bigint id PK
        bigint person_id FK
        bigint department_id FK
        string employee_code UK
        enum employment_type
        enum status
        date start_date
        date end_date
        date probation_end_date
        date contract_end_date
        decimal salary
        text position_title
        text notes
        timestamps
        soft_deletes
    }

    interviews {
        bigint id PK
        bigint person_id FK
        bigint employment_application_id FK
        enum type
        enum status
        enum result
        datetime scheduled_at
        int duration_minutes
        string location
        string meeting_url
        bigint interviewer_id FK
        text notes
        text feedback
        timestamps
        soft_deletes
    }

    documents {
        bigint id PK
        bigint person_id FK
        enum type
        string title
        date expires_at
        boolean is_active
        timestamps
        soft_deletes
    }

    document_versions {
        bigint id PK
        bigint document_id FK
        int version_number
        string file_path
        string file_name
        string mime_type
        bigint file_size
        bigint uploaded_by FK
        timestamp uploaded_at
        text notes
        timestamps
    }

    person_educations {
        bigint id PK
        bigint person_id FK
        string degree
        string field_of_study
        string institution
        date start_date
        date end_date
        boolean is_current
        timestamps
    }

    person_work_experiences {
        bigint id PK
        bigint person_id FK
        string company_name
        string position
        date start_date
        date end_date
        boolean is_current
        text description
        timestamps
    }

    person_family_members {
        bigint id PK
        bigint person_id FK
        string full_name
        enum relation
        string national_id
        date birth_date
        string mobile
        timestamps
    }

    calendar_events {
        bigint id PK
        string title
        text description
        enum event_type
        datetime starts_at
        datetime ends_at
        boolean all_day
        bigint person_id FK
        bigint interview_id FK
        bigint created_by FK
        string color
        timestamps
        soft_deletes
    }

    assignments {
        bigint id PK
        string assignable_type
        bigint assignable_id
        bigint user_id FK
        enum role
        timestamps
    }

    application_form_fields {
        bigint id PK
        string field_key UK
        string label
        enum field_type
        json options
        int step
        int sort_order
        boolean is_visible
        boolean is_required
        timestamps
    }

    otp_codes {
        bigint id PK
        string mobile
        string code_hash
        enum purpose
        timestamp expires_at
        timestamp verified_at
        int attempts
        timestamps
    }

    audit_logs {
        bigint id PK
        bigint user_id FK
        string auditable_type
        bigint auditable_id
        enum action
        json old_values
        json new_values
        string ip_address
        string user_agent
        timestamps
    }

    notification_logs {
        bigint id PK
        bigint user_id FK
        string channel
        string recipient
        string subject
        text body
        enum status
        text error_message
        timestamps
    }
```

---

## جداول تفصیلی

### users

| ستون | نوع | توضیح |
|------|-----|-------|
| id | bigint PK | |
| name | varchar(255) | نام نمایشی |
| email | varchar(255) UNIQUE | ایمیل ورود HR |
| password | varchar(255) | bcrypt |
| role | enum | candidate, employee, hr, hr_manager, super_admin |
| hr_access | boolean default false | دسترسی HR مستقل از دپارتمان |
| person_id | bigint FK nullable | اتصال به Person |
| email_verified_at | timestamp nullable | |
| remember_token | varchar(100) | |
| created_at, updated_at | timestamps | |

### persons

| ستون | نوع | توضیح |
|------|-----|-------|
| id | bigint PK | |
| first_name | varchar(100) | |
| last_name | varchar(100) | |
| national_id | varchar(10) UNIQUE nullable | کد ملی |
| mobile | varchar(15) UNIQUE | موبایل |
| birth_date | date nullable | میلادی در DB |
| gender | enum nullable | male, female, other |
| lifecycle_status | enum | applicant, interviewed, accepted, employee, former_employee |
| marital_status | enum nullable | single, married, divorced, widowed |
| address | text nullable | |
| city | varchar(100) nullable | |
| province | varchar(100) nullable | |
| postal_code | varchar(20) nullable | |
| profile_photo | varchar(255) nullable | |
| notes | text nullable | یادداشت HR |
| deleted_at | timestamp nullable | soft delete |
| created_at, updated_at | timestamps | |

**Indexes:** `lifecycle_status`, `mobile`, `national_id`

### departments

| ستون | نوع | توضیح |
|------|-----|-------|
| id | bigint PK | |
| name | varchar(255) | |
| code | varchar(50) UNIQUE | کد دپارتمان |
| description | text nullable | |
| is_active | boolean default true | |
| sort_order | int default 0 | |
| manager_id | bigint FK nullable → users | |
| deleted_at | timestamp nullable | |
| created_at, updated_at | timestamps | |

### department_person (pivot)

| ستون | نوع | توضیح |
|------|-----|-------|
| id | bigint PK | |
| department_id | bigint FK | |
| person_id | bigint FK | |
| joined_at | date nullable | |
| left_at | date nullable | |
| is_primary | boolean default false | دپارتمان اصلی |
| created_at, updated_at | timestamps | |

**Unique:** `(department_id, person_id)` where left_at is null

### employment_applications

| ستون | نوع | توضیح |
|------|-----|-------|
| id | bigint PK | |
| person_id | bigint FK | |
| application_number | varchar(20) UNIQUE | شماره پیگیری |
| status | enum | draft, submitted, under_review, interview_scheduled, interviewed, accepted, rejected, withdrawn |
| form_data | json | پاسخ‌های فرم |
| current_step | int default 1 | |
| assigned_to | bigint FK nullable → users | |
| reviewer_id | bigint FK nullable → users | |
| submitted_at | timestamp nullable | |
| reviewed_at | timestamp nullable | |
| hr_notes | text nullable | |
| deleted_at | timestamp nullable | |
| created_at, updated_at | timestamps | |

### employment_records

| ستون | نوع | توضیح |
|------|-----|-------|
| id | bigint PK | |
| person_id | bigint FK | |
| department_id | bigint FK nullable | |
| employee_code | varchar(50) UNIQUE | کد پرسنلی |
| employment_type | enum | full_time, part_time, contract, intern |
| status | enum | active, on_leave, terminated, retired |
| start_date | date | |
| end_date | date nullable | |
| probation_end_date | date nullable | |
| contract_end_date | date nullable | |
| salary | decimal(15,2) nullable | |
| position_title | varchar(255) | |
| notes | text nullable | |
| deleted_at | timestamp nullable | |
| created_at, updated_at | timestamps | |

### interviews

| ستون | نوع | توضیح |
|------|-----|-------|
| id | bigint PK | |
| person_id | bigint FK | |
| employment_application_id | bigint FK nullable | |
| type | enum | in_person, online |
| status | enum | scheduled, completed, cancelled, no_show |
| result | enum nullable | passed, failed, pending, next_round |
| scheduled_at | datetime | |
| duration_minutes | int default 60 | |
| location | varchar(255) nullable | |
| meeting_url | varchar(500) nullable | |
| interviewer_id | bigint FK → users | |
| notes | text nullable | |
| feedback | text nullable | |
| deleted_at | timestamp nullable | |
| created_at, updated_at | timestamps | |

### documents

| ستون | نوع | توضیح |
|------|-----|-------|
| id | bigint PK | |
| person_id | bigint FK | |
| type | enum | contract, decree, education, national_id, birth_certificate, certificate, general |
| title | varchar(255) | |
| expires_at | date nullable | |
| is_active | boolean default true | |
| deleted_at | timestamp nullable | |
| created_at, updated_at | timestamps | |

### document_versions

| ستون | نوع | توضیح |
|------|-----|-------|
| id | bigint PK | |
| document_id | bigint FK | |
| version_number | int | |
| file_path | varchar(500) | |
| file_name | varchar(255) | |
| mime_type | varchar(100) | |
| file_size | bigint | bytes |
| uploaded_by | bigint FK → users | |
| uploaded_at | timestamp | |
| notes | text nullable | |
| created_at, updated_at | timestamps | |

**Unique:** `(document_id, version_number)`

### calendar_events

| ستون | نوع | توضیح |
|------|-----|-------|
| id | bigint PK | |
| title | varchar(255) | |
| description | text nullable | |
| event_type | enum | interview, birthday, contract_end, contract_renewal, probation_end, training, hr_event |
| starts_at | datetime | |
| ends_at | datetime nullable | |
| all_day | boolean default false | |
| person_id | bigint FK nullable | |
| interview_id | bigint FK nullable | |
| created_by | bigint FK → users | |
| color | varchar(7) nullable | hex color |
| deleted_at | timestamp nullable | |
| created_at, updated_at | timestamps | |

### assignments

| ستون | نوع | توضیح |
|------|-----|-------|
| id | bigint PK | |
| assignable_type | varchar(255) | morph |
| assignable_id | bigint | morph |
| user_id | bigint FK | |
| role | enum | assignee, reviewer, collaborator |
| created_at, updated_at | timestamps | |

**Index:** `(assignable_type, assignable_id)`

### application_form_fields

| ستون | نوع | توضیح |
|------|-----|-------|
| id | bigint PK | |
| field_key | varchar(100) UNIQUE | |
| label | varchar(255) | |
| field_type | enum | text, textarea, select, radio, checkbox, date, file, number, email, tel |
| options | json nullable | گزینه‌های select/radio |
| step | int default 1 | مرحله فرم |
| sort_order | int default 0 | |
| is_visible | boolean default true | |
| is_required | boolean default false | |
| created_at, updated_at | timestamps | |

### otp_codes

| ستون | نوع | توضیح |
|------|-----|-------|
| id | bigint PK | |
| mobile | varchar(15) | |
| code_hash | varchar(255) | |
| purpose | enum | recruitment, portal |
| expires_at | timestamp | |
| verified_at | timestamp nullable | |
| attempts | int default 0 | |
| created_at, updated_at | timestamps | |

**Index:** `mobile`, `expires_at`

### audit_logs

| ستون | نوع | توضیح |
|------|-----|-------|
| id | bigint PK | |
| user_id | bigint FK nullable | |
| auditable_type | varchar(255) | morph |
| auditable_id | bigint | morph |
| action | enum | created, updated, deleted, downloaded, status_changed, message_sent |
| old_values | json nullable | |
| new_values | json nullable | |
| ip_address | varchar(45) nullable | |
| user_agent | text nullable | |
| created_at, updated_at | timestamps | |

### notification_logs

| ستون | نوع | توضیح |
|------|-----|-------|
| id | bigint PK | |
| user_id | bigint FK nullable | |
| channel | varchar(50) | sms, in_app, email |
| recipient | varchar(255) | |
| subject | varchar(255) nullable | |
| body | text | |
| status | enum | pending, sent, failed |
| error_message | text nullable | |
| created_at, updated_at | timestamps | |

---

## Enums خلاصه

### PersonLifecycleStatus
`applicant` | `interviewed` | `accepted` | `employee` | `former_employee`

### ApplicationStatus
`draft` | `submitted` | `under_review` | `interview_scheduled` | `interviewed` | `accepted` | `rejected` | `withdrawn`

### UserRole
`candidate` | `employee` | `hr` | `hr_manager` | `super_admin`

### DocumentType
`contract` | `decree` | `education` | `national_id` | `birth_certificate` | `certificate` | `general`

### CalendarEventType
`interview` | `birthday` | `contract_end` | `contract_renewal` | `probation_end` | `training` | `hr_event`

---

## نکات Migration

1. تمام `enum` ها در Laravel به صورت `string` + PHP Enum cast
2. تاریخ‌ها در DB به صورت `date`/`datetime` میلادی
3. SQLite و MySQL هر دو پشتیبانی — از `json` type استفاده شود
4. Foreign keys با `onDelete` مناسب: `cascade` برای وابستگی‌های قوی، `set null` برای اختیاری
5. Soft deletes روی Person, Application, Interview, Document, Department, CalendarEvent, EmploymentRecord

---

## Seed Data Plan

| Seeder | محتوا |
|--------|-------|
| UserSeeder | super_admin, hr_manager, hr, sample employees |
| DepartmentSeeder | ۵ دپارتمان نمونه |
| ApplicationFormFieldSeeder | فیلدهای پیش‌فرض فرم استخدام |
| PersonSeeder | ۱۰ Person با وضعیت‌های مختلف |
| EmploymentApplicationSeeder | ۵ درخواست نمونه |
| InterviewSeeder | ۳ مصاحبه |
| CalendarEventSeeder | رویدادهای نمونه |
| DocumentSeeder | اسناد نمونه |
