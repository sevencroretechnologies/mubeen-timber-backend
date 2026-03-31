# WorkDo HRMS Database Schema Documentation

Complete database schema documentation with entity relationships and table structures.

---

## 📊 Entity Relationship Diagram (ERD)

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              WORKDO HRMS DATABASE                                │
│                                 63 Tables Total                                  │
└─────────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────────┐
│                            CORE ENTITIES                                          │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│   ┌─────────────┐         ┌─────────────────┐         ┌──────────────┐           │
│   │    users    │────────►│  staff_members  │◄────────│    roles     │           │
│   └─────────────┘   1:1   └─────────────────┘   M:N   └──────────────┘           │
│         │                         │                                              │
│         │                         ├─────────────────────────────────┐            │
│         ▼                         ▼                                 ▼            │
│   ┌───────────┐          ┌─────────────────┐           ┌───────────────┐         │
│   │permissions│          │office_locations │           │   divisions   │         │
│   └───────────┘          └─────────────────┘           └───────────────┘         │
│                                   │                            │                 │
│                                   └────────────┬───────────────┘                 │
│                                                ▼                                 │
│                                        ┌────────────┐                            │
│                                        │ job_titles │                            │
│                                        └────────────┘                            │
│                                                                                   │
└──────────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────────┐
│                         ATTENDANCE & TIME TRACKING                                │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│   ┌─────────────────┐                                                            │
│   │  staff_members  │                                                            │
│   └────────┬────────┘                                                            │
│            │                                                                      │
│     ┌──────┼──────┬───────────────┬────────────────────┐                         │
│     ▼      ▼      ▼               ▼                    ▼                         │
│ ┌────────┐ ┌──────────┐ ┌──────────────────┐ ┌──────────────────┐ ┌───────────┐  │
│ │work_logs│ │timesheets│ │attendance_       │ │extra_hours_      │ │  shifts   │  │
│ │        │ │          │ │regularizations   │ │records           │ │           │  │
│ └────────┘ └──────────┘ └──────────────────┘ └──────────────────┘ └───────────┘  │
│                                                                                   │
└──────────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────────┐
│                            LEAVE MANAGEMENT                                       │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│   ┌─────────────────┐         ┌────────────────────┐                             │
│   │  staff_members  │────────►│ time_off_requests  │                             │
│   └─────────────────┘   1:N   └─────────┬──────────┘                             │
│                                         │                                        │
│                                         ▼                                        │
│                               ┌────────────────────┐                             │
│                               │time_off_categories │                             │
│                               └────────────────────┘                             │
│                                                                                   │
└──────────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────────┐
│                               PAYROLL                                             │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│   ┌─────────────────┐                                                            │
│   │  staff_members  │                                                            │
│   └────────┬────────┘                                                            │
│            │                                                                      │
│     ┌──────┼───────┬──────────────┬─────────────────┐                            │
│     ▼      ▼       ▼              ▼                 ▼                            │
│ ┌────────────┐ ┌────────────┐ ┌─────────────┐ ┌────────────────┐ ┌─────────────┐ │
│ │salary_slips│ │salary_     │ │staff_       │ │recurring_      │ │bonus_       │ │
│ │            │ │advances    │ │benefits     │ │deductions      │ │payments     │ │
│ └────────────┘ └────────────┘ └─────────────┘ └────────────────┘ └─────────────┘ │
│        │                           │                                              │
│        │                           ▼                                              │
│        │                    ┌─────────────┐                                       │
│        │                    │benefit_types│                                       │
│        │                    └─────────────┘                                       │
│        ▼                                                                          │
│   ┌─────────┐   ┌──────────────┐   ┌───────────────────┐                         │
│   │tax_slabs│   │tax_exemptions│   │minimum_tax_limits │                         │
│   └─────────┘   └──────────────┘   └───────────────────┘                         │
│                                                                                   │
└──────────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────────┐
│                              RECRUITMENT                                          │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│   ┌──────────┐        ┌─────────────────┐        ┌────────────────┐              │
│   │   jobs   │◄──────►│ job_applications │◄──────►│   candidates   │              │
│   └────┬─────┘   1:N  └────────┬────────┘   N:1  └────────────────┘              │
│        │                       │                                                  │
│        ▼                       ▼                                                  │
│   ┌───────────┐     ┌──────────────────────┐     ┌────────────┐                  │
│   │job_stages │     │interview_schedules   │     │   offers   │                  │
│   └───────────┘     └──────────────────────┘     └────────────┘                  │
│                                                                                   │
└──────────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────────┐
│                              PERFORMANCE                                          │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│   ┌─────────────────┐                                                            │
│   │  staff_members  │                                                            │
│   └────────┬────────┘                                                            │
│            │                                                                      │
│     ┌──────┴──────────┬─────────────────────┐                                    │
│     ▼                 ▼                     ▼                                    │
│ ┌────────────────┐ ┌────────────────┐ ┌───────────────────┐                      │
│ │performance_    │ │appraisal_      │ │recognition_       │                      │
│ │objectives      │ │records         │ │records            │                      │
│ └────────────────┘ └───────┬────────┘ └─────────┬─────────┘                      │
│                            │                    │                                │
│                            ▼                    ▼                                │
│                    ┌───────────────┐   ┌────────────────────┐                    │
│                    │appraisal_     │   │recognition_        │                    │
│                    │cycles         │   │categories          │                    │
│                    └───────────────┘   └────────────────────┘                    │
│                                                                                   │
└──────────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────────┐
│                              TRAINING                                             │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│   ┌───────────────┐      ┌──────────────────┐      ┌──────────────────┐          │
│   │training_types │◄────►│training_programs │◄────►│training_sessions │          │
│   └───────────────┘ 1:N  └──────────────────┘ 1:N  └────────┬─────────┘          │
│                                                             │                    │
│                                                             ▼                    │
│                                                   ┌────────────────────┐         │
│                                                   │training_participants│         │
│                                                   └────────────────────┘         │
│                                                                                   │
└──────────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────────┐
│                           ASSETS & INVENTORY                                      │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│   ┌─────────────┐       ┌────────┐       ┌───────────────────┐                   │
│   │ asset_types │◄─────►│ assets │◄─────►│ asset_assignments │                   │
│   └─────────────┘  1:N  └────────┘  1:N  └───────────────────┘                   │
│                              │                     │                              │
│                              ▼                     ▼                              │
│                    ┌──────────────────┐    ┌─────────────────┐                   │
│                    │asset_maintenance │    │  staff_members  │                   │
│                    └──────────────────┘    └─────────────────┘                   │
│                                                                                   │
└──────────────────────────────────────────────────────────────────────────────────┘
```

---

## 📋 Table Definitions

### Core Tables

#### `users`

Main authentication table for all system users.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| name | VARCHAR(255) | NOT NULL | User's display name |
| email | VARCHAR(255) | UNIQUE, NOT NULL | Login email |
| email_verified_at | TIMESTAMP | NULLABLE | Email verification time |
| password | VARCHAR(255) | NOT NULL | Hashed password |
| remember_token | VARCHAR(100) | NULLABLE | Session token |
| is_active | BOOLEAN | DEFAULT true | Account status |
| created_at | TIMESTAMP | | Creation timestamp |
| updated_at | TIMESTAMP | | Last update timestamp |

**Indexes:** `email` (unique)

---

#### `staff_members`

Core employee information table.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| user_id | BIGINT | FK → users.id | Link to user account |
| full_name | VARCHAR(255) | NOT NULL | Employee full name |
| personal_email | VARCHAR(255) | NULLABLE | Personal email |
| mobile_number | VARCHAR(20) | NULLABLE | Phone number |
| birth_date | DATE | NULLABLE | Date of birth |
| gender | ENUM | male, female, other | Gender |
| home_address | TEXT | NULLABLE | Residential address |
| nationality | VARCHAR(100) | NULLABLE | Country of citizenship |
| passport_number | VARCHAR(50) | NULLABLE | Passport/ID number |
| country_code | VARCHAR(3) | NULLABLE | Country code |
| region | VARCHAR(100) | NULLABLE | State/region |
| city_name | VARCHAR(100) | NULLABLE | City |
| postal_code | VARCHAR(20) | NULLABLE | ZIP/postal code |
| staff_code | VARCHAR(50) | UNIQUE | Employee ID |
| biometric_id | VARCHAR(50) | NULLABLE | Biometric device ID |
| office_location_id | BIGINT | FK → office_locations.id | Work location |
| division_id | BIGINT | FK → divisions.id | Department |
| job_title_id | BIGINT | FK → job_titles.id | Position |
| hire_date | DATE | NULLABLE | Employment start date |
| bank_account_name | VARCHAR(255) | NULLABLE | Bank account holder |
| bank_account_number | VARCHAR(50) | NULLABLE | Account number |
| bank_name | VARCHAR(255) | NULLABLE | Bank name |
| bank_branch | VARCHAR(255) | NULLABLE | Branch name |
| compensation_type | ENUM | monthly, hourly, daily, contract | Pay type |
| base_salary | DECIMAL(12,2) | DEFAULT 0 | Base pay amount |
| employment_status | ENUM | active, on_leave, suspended, terminated, resigned | Status |
| tenant_id | BIGINT | NULLABLE | Multi-tenant ID |
| author_id | BIGINT | FK → users.id | Created by |
| created_at | TIMESTAMP | | Creation timestamp |
| updated_at | TIMESTAMP | | Last update |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete |

**Relationships:**

- `user_id` → `users.id` (CASCADE DELETE)
- `office_location_id` → `office_locations.id` (NULL ON DELETE)
- `division_id` → `divisions.id` (NULL ON DELETE)
- `job_title_id` → `job_titles.id` (NULL ON DELETE)

---

#### `office_locations`

Company office/branch locations.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| name | VARCHAR(255) | NOT NULL | Location name |
| address | TEXT | NULLABLE | Full address |
| timezone | VARCHAR(50) | NULLABLE | Timezone (e.g., America/New_York) |
| is_active | BOOLEAN | DEFAULT true | Active status |
| tenant_id | BIGINT | NULLABLE | Multi-tenant ID |
| author_id | BIGINT | FK → users.id | Created by |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

---

#### `divisions`

Departments/divisions within the organization.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| title | VARCHAR(255) | NOT NULL | Division name |
| office_location_id | BIGINT | FK → office_locations.id | Parent location |
| notes | TEXT | NULLABLE | Description |
| is_active | BOOLEAN | DEFAULT true | Active status |
| tenant_id | BIGINT | NULLABLE | Multi-tenant ID |
| author_id | BIGINT | FK → users.id | Created by |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

---

#### `job_titles`

Position titles within divisions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| title | VARCHAR(255) | NOT NULL | Job title name |
| division_id | BIGINT | FK → divisions.id | Parent division |
| notes | TEXT | NULLABLE | Job description |
| is_active | BOOLEAN | DEFAULT true | Active status |
| tenant_id | BIGINT | NULLABLE | Multi-tenant ID |
| author_id | BIGINT | FK → users.id | Created by |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

---

### Attendance Tables

#### `work_logs`

Daily attendance records.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| staff_member_id | BIGINT | FK → staff_members.id | Employee |
| log_date | DATE | NOT NULL | Work date |
| status | ENUM | present, absent, late, half_day | Attendance status |
| clock_in | TIME | NULLABLE | Check-in time |
| clock_out | TIME | NULLABLE | Check-out time |
| late_minutes | INT | DEFAULT 0 | Minutes late |
| early_leave_minutes | INT | DEFAULT 0 | Early departure minutes |
| overtime_minutes | INT | DEFAULT 0 | OT minutes |
| break_minutes | INT | DEFAULT 0 | Break duration |
| clock_in_ip | VARCHAR(45) | NULLABLE | Clock-in IP address |
| clock_out_ip | VARCHAR(45) | NULLABLE | Clock-out IP address |
| author_id | BIGINT | FK → users.id | Created by |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Unique Index:** `(staff_member_id, log_date)`

---

#### `shifts`

Work shift definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| name | VARCHAR(255) | NOT NULL | Shift name |
| start_time | TIME | NOT NULL | Shift start |
| end_time | TIME | NOT NULL | Shift end |
| break_duration | INT | DEFAULT 60 | Break minutes |
| is_night_shift | BOOLEAN | DEFAULT false | Night shift flag |
| grace_period_minutes | INT | DEFAULT 15 | Late grace period |
| is_active | BOOLEAN | DEFAULT true | Active status |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

---

### Leave Tables

#### `time_off_categories`

Leave type definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| title | VARCHAR(255) | NOT NULL | Leave type name |
| annual_quota | INT | NULLABLE | Days per year |
| is_paid | BOOLEAN | DEFAULT true | Paid leave flag |
| is_active | BOOLEAN | DEFAULT true | Active status |
| tenant_id | BIGINT | NULLABLE | Multi-tenant ID |
| author_id | BIGINT | FK → users.id | Created by |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

---

#### `time_off_requests`

Employee leave requests.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| staff_member_id | BIGINT | FK → staff_members.id | Requesting employee |
| time_off_category_id | BIGINT | FK → time_off_categories.id | Leave type |
| request_date | DATE | NOT NULL | Date of request |
| start_date | DATE | NOT NULL | Leave start |
| end_date | DATE | NOT NULL | Leave end |
| total_days | DECIMAL(5,1) | NOT NULL | Total days (supports 0.5) |
| reason | TEXT | NULLABLE | Request reason |
| approval_status | ENUM | pending, approved, declined | Status |
| approved_by | BIGINT | FK → users.id | Approver |
| approval_remarks | TEXT | NULLABLE | Approver notes |
| approved_at | TIMESTAMP | NULLABLE | Approval timestamp |
| tenant_id | BIGINT | NULLABLE | Multi-tenant ID |
| author_id | BIGINT | FK → users.id | Created by |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

---

### Payroll Tables

#### `salary_slips`

Monthly payslip records.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| staff_member_id | BIGINT | FK → staff_members.id | Employee |
| slip_reference | VARCHAR(255) | UNIQUE | Slip reference number |
| salary_period | VARCHAR(7) | NOT NULL | Period (YYYY-MM) |
| basic_salary | DECIMAL(12,2) | NOT NULL | Base salary |
| benefits_breakdown | JSON | NULLABLE | Benefits details |
| incentives_breakdown | JSON | NULLABLE | Incentives details |
| bonus_breakdown | JSON | NULLABLE | Bonus details |
| overtime_breakdown | JSON | NULLABLE | OT details |
| contributions_breakdown | JSON | NULLABLE | Employer contributions |
| deductions_breakdown | JSON | NULLABLE | Deductions details |
| advances_breakdown | JSON | NULLABLE | Advance deductions |
| tax_breakdown | JSON | NULLABLE | Tax calculation |
| total_earnings | DECIMAL(12,2) | NOT NULL | Gross earnings |
| total_deductions | DECIMAL(12,2) | NOT NULL | Total deductions |
| net_payable | DECIMAL(12,2) | NOT NULL | Net salary |
| status | ENUM | draft, generated, sent, paid | Slip status |
| generated_at | TIMESTAMP | NULLABLE | Generation time |
| sent_at | TIMESTAMP | NULLABLE | Email sent time |
| paid_at | TIMESTAMP | NULLABLE | Payment time |
| tenant_id | BIGINT | NULLABLE | Multi-tenant ID |
| author_id | BIGINT | FK → users.id | Created by |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Unique Index:** `(staff_member_id, salary_period)`

---

### Recruitment Tables

#### `jobs`

Job postings.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| title | VARCHAR(255) | NOT NULL | Job title |
| slug | VARCHAR(255) | UNIQUE | URL slug |
| description | TEXT | NULLABLE | Full description |
| requirements | TEXT | NULLABLE | Requirements |
| division_id | BIGINT | FK → divisions.id | Department |
| office_location_id | BIGINT | FK → office_locations.id | Location |
| employment_type | ENUM | full_time, part_time, contract, internship | Type |
| experience_level | ENUM | entry, junior, mid, senior, lead | Level |
| min_salary | DECIMAL(12,2) | NULLABLE | Minimum salary |
| max_salary | DECIMAL(12,2) | NULLABLE | Maximum salary |
| vacancies | INT | DEFAULT 1 | Number of openings |
| status | ENUM | draft, published, closed | Posting status |
| published_at | TIMESTAMP | NULLABLE | Publish date |
| deadline | DATE | NULLABLE | Application deadline |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

---

#### `candidates`

Job applicant profiles.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| first_name | VARCHAR(255) | NOT NULL | First name |
| last_name | VARCHAR(255) | NOT NULL | Last name |
| email | VARCHAR(255) | UNIQUE | Email address |
| phone | VARCHAR(20) | NULLABLE | Phone number |
| resume_path | VARCHAR(255) | NULLABLE | Resume file path |
| source | VARCHAR(100) | NULLABLE | Application source |
| status | ENUM | new, in_review, shortlisted, hired, rejected | Status |
| is_archived | BOOLEAN | DEFAULT false | Archived flag |
| notes | TEXT | NULLABLE | Internal notes |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

---

#### `job_applications`

Applications linking candidates to jobs.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO | Primary key |
| job_id | BIGINT | FK → jobs.id | Job posting |
| candidate_id | BIGINT | FK → candidates.id | Applicant |
| job_stage_id | BIGINT | FK → job_stages.id | Current stage |
| applied_at | TIMESTAMP | NOT NULL | Application date |
| status | ENUM | pending, in_progress, hired, rejected | Status |
| rejection_reason | TEXT | NULLABLE | Rejection notes |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Unique Index:** `(job_id, candidate_id)`

---

## 🔗 Relationship Summary

| Parent Table | Child Table | Relationship | Description |
|--------------|-------------|--------------|-------------|
| users | staff_members | 1:1 | User has one employee profile |
| staff_members | work_logs | 1:N | Employee has many attendance records |
| staff_members | time_off_requests | 1:N | Employee has many leave requests |
| staff_members | salary_slips | 1:N | Employee has many payslips |
| office_locations | divisions | 1:N | Location has many departments |
| divisions | job_titles | 1:N | Department has many positions |
| divisions | staff_members | 1:N | Department has many employees |
| time_off_categories | time_off_requests | 1:N | Category has many requests |
| jobs | job_applications | 1:N | Job has many applications |
| candidates | job_applications | 1:N | Candidate can apply to many jobs |
| asset_types | assets | 1:N | Type has many assets |
| assets | asset_assignments | 1:N | Asset can be assigned multiple times |
| training_programs | training_sessions | 1:N | Program has many sessions |

---

## 📊 Database Statistics

| Category | Count |
|----------|-------|
| **Total Tables** | 63 |
| **Core Tables** | 8 |
| **Attendance Tables** | 6 |
| **Leave Tables** | 2 |
| **Payroll Tables** | 14 |
| **Recruitment Tables** | 8 |
| **Performance Tables** | 5 |
| **Training Tables** | 4 |
| **Asset Tables** | 4 |
| **Company Tables** | 6 |
| **Document Tables** | 6 |
| **Configuration Tables** | 5 |

---

## 🔧 Index Strategy

### Primary Indexes (Automatic)

- All `id` columns have primary key indexes

### Foreign Key Indexes

- All `*_id` foreign key columns are indexed

### Unique Indexes

- `users.email`
- `staff_members.staff_code`
- `salary_slips.slip_reference`
- `jobs.slug`
- `candidates.email`
- `(staff_member_id, salary_period)` on salary_slips
- `(staff_member_id, log_date)` on work_logs
- `(job_id, candidate_id)` on job_applications

### Composite Indexes (Recommended)

```sql
-- For attendance queries
CREATE INDEX idx_work_logs_date_status ON work_logs(log_date, status);

-- For leave queries
CREATE INDEX idx_time_off_status ON time_off_requests(approval_status, start_date);

-- For payroll queries
CREATE INDEX idx_salary_period ON salary_slips(salary_period, status);
```

---

## 🗄️ Multi-Tenancy Support

Most tables include these columns for multi-tenant support:

| Column | Description |
|--------|-------------|
| `tenant_id` | Organization/company identifier |
| `author_id` | User who created the record |

This enables:

- Data isolation between organizations
- Audit trail of record creators
- Row-level security policies

---

**Schema Version:** 1.0.0
**Database Engine:** SQLite (development) / MySQL/PostgreSQL (production)
**Last Updated:** December 2025
