# WorkDo HRMS API Endpoints - Quick Reference

A concise reference of all 444 API endpoints organized by module.

---

## 🔐 Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/sign-up` | Register new user |
| POST | `/api/auth/sign-in` | Login and get token |
| POST | `/api/auth/sign-out` | Logout and invalidate token |
| GET | `/api/auth/profile` | Get current user profile |
| PUT | `/api/auth/profile` | Update user profile |
| POST | `/api/auth/change-password` | Change password |

---

## 📊 Dashboard

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/dashboard` | Get complete dashboard data |
| GET | `/api/dashboard/employee` | Get employee statistics |
| GET | `/api/dashboard/attendance` | Get attendance summary |
| GET | `/api/dashboard/employee-growth` | Get employee growth trend |
| GET | `/api/dashboard/department-distribution` | Get dept distribution |

---

## 👥 Staff Management

### Staff Members

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/staff-members` | List all staff members |
| POST | `/api/staff-members` | Create new staff member |
| GET | `/api/staff-members/{id}` | Get staff member details |
| PUT | `/api/staff-members/{id}` | Update staff member |
| DELETE | `/api/staff-members/{id}` | Delete staff member |

### Office Locations

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/office-locations` | List all locations |
| POST | `/api/office-locations` | Create location |
| GET | `/api/office-locations/{id}` | Get location details |
| PUT | `/api/office-locations/{id}` | Update location |
| DELETE | `/api/office-locations/{id}` | Delete location |

### Divisions (Departments)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/divisions` | List all divisions |
| POST | `/api/divisions` | Create division |
| GET | `/api/divisions/{id}` | Get division details |
| PUT | `/api/divisions/{id}` | Update division |
| DELETE | `/api/divisions/{id}` | Delete division |

### Job Titles

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/job-titles` | List all job titles |
| POST | `/api/job-titles` | Create job title |
| GET | `/api/job-titles/{id}` | Get job title details |
| PUT | `/api/job-titles/{id}` | Update job title |
| DELETE | `/api/job-titles/{id}` | Delete job title |

---

## ⏰ Attendance

### Clock In/Out

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/clock-in` | Clock in |
| POST | `/api/clock-out` | Clock out |
| GET | `/api/attendance-summary` | Get attendance summary |

### Work Logs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/work-logs` | List all work logs |
| POST | `/api/work-logs` | Create work log |
| POST | `/api/work-logs/bulk` | Bulk create work logs |
| GET | `/api/work-logs/{id}` | Get work log details |
| PUT | `/api/work-logs/{id}` | Update work log |
| DELETE | `/api/work-logs/{id}` | Delete work log |

### Shifts

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/shifts` | List all shifts |
| POST | `/api/shifts` | Create shift |
| GET | `/api/shifts/{id}` | Get shift details |
| PUT | `/api/shifts/{id}` | Update shift |
| DELETE | `/api/shifts/{id}` | Delete shift |
| POST | `/api/shifts/{id}/assign` | Assign shift to employee |

### Timesheets

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/timesheets` | List all timesheets |
| POST | `/api/timesheets` | Create timesheet |
| POST | `/api/timesheets/bulk` | Bulk create timesheets |
| GET | `/api/timesheets/{id}` | Get timesheet details |
| PUT | `/api/timesheets/{id}` | Update timesheet |
| DELETE | `/api/timesheets/{id}` | Delete timesheet |
| POST | `/api/timesheets/{id}/submit` | Submit for approval |
| POST | `/api/timesheets/{id}/approve` | Approve timesheet |
| POST | `/api/timesheets/{id}/reject` | Reject timesheet |

### Extra Hours (Overtime)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/extra-hours` | List extra hours records |
| POST | `/api/extra-hours` | Create extra hours |
| GET | `/api/extra-hours/{id}` | Get extra hours details |
| PUT | `/api/extra-hours/{id}` | Update extra hours |
| DELETE | `/api/extra-hours/{id}` | Delete extra hours |

### Attendance Regularization

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/attendance-regularizations` | List regularizations |
| POST | `/api/attendance-regularizations` | Request regularization |
| POST | `/api/attendance-regularizations/{id}/approve` | Approve |
| POST | `/api/attendance-regularizations/{id}/reject` | Reject |

---

## 🏖️ Leave Management

### Time-Off Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/time-off-categories` | List leave types |
| POST | `/api/time-off-categories` | Create leave type |
| GET | `/api/time-off-categories/{id}` | Get leave type |
| PUT | `/api/time-off-categories/{id}` | Update leave type |
| DELETE | `/api/time-off-categories/{id}` | Delete leave type |

### Time-Off Requests

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/time-off-requests` | List leave requests |
| POST | `/api/time-off-requests` | Create leave request |
| GET | `/api/time-off-requests/{id}` | Get request details |
| PUT | `/api/time-off-requests/{id}` | Update request |
| DELETE | `/api/time-off-requests/{id}` | Delete request |
| POST | `/api/time-off-requests/{id}/process` | Approve/Decline |
| GET | `/api/time-off-balance` | Get leave balance |

---

## 💰 Payroll

### Salary Slips

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/salary-slips` | List all salary slips |
| POST | `/api/salary-slips` | Generate salary slip |
| GET | `/api/salary-slips/{id}` | Get slip details |
| PUT | `/api/salary-slips/{id}` | Update slip |
| POST | `/api/salary-slips/{id}/send` | Send to employee |
| POST | `/api/salary-slips/{id}/mark-paid` | Mark as paid |

### Salary Advances

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/salary-advances` | List advances |
| POST | `/api/salary-advances` | Request advance |
| GET | `/api/salary-advances/{id}` | Get advance details |
| POST | `/api/salary-advances/{id}/approve` | Approve advance |
| POST | `/api/salary-advances/{id}/reject` | Reject advance |

### Benefits

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/benefit-types` | List benefit types |
| POST | `/api/benefit-types` | Create benefit type |
| GET | `/api/staff-benefits` | List staff benefits |
| POST | `/api/staff-benefits` | Assign benefit |

### Bonus & Incentives

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/bonus-payments` | List bonuses |
| POST | `/api/bonus-payments` | Create bonus |
| GET | `/api/incentive-records` | List incentives |
| POST | `/api/incentive-records` | Create incentive |

### Deductions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/recurring-deductions` | List deductions |
| POST | `/api/recurring-deductions` | Create deduction |
| GET | `/api/withholding-types` | List withholding types |

### Tax Configuration

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/tax-slabs` | List tax slabs |
| POST | `/api/tax-slabs` | Create tax slab |
| GET | `/api/tax-exemptions` | List exemptions |

---

## 🎯 Recruitment

### Jobs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/jobs` | List job postings |
| POST | `/api/jobs` | Create job posting |
| GET | `/api/jobs/{id}` | Get job details |
| PUT | `/api/jobs/{id}` | Update job |
| DELETE | `/api/jobs/{id}` | Delete job |
| POST | `/api/jobs/{id}/publish` | Publish job |
| POST | `/api/jobs/{id}/close` | Close job |

### Candidates

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/candidates` | List candidates |
| POST | `/api/candidates` | Create candidate |
| GET | `/api/candidates/{id}` | Get candidate |
| PUT | `/api/candidates/{id}` | Update candidate |

### Applications

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/job-applications` | List applications |
| POST | `/api/job-applications` | Create application |
| GET | `/api/job-applications/{id}` | Get application |
| POST | `/api/job-applications/{id}/move-stage` | Move stage |
| POST | `/api/job-applications/{id}/shortlist` | Shortlist |
| POST | `/api/job-applications/{id}/reject` | Reject |

### Interviews

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/interview-schedules` | List interviews |
| POST | `/api/interview-schedules` | Schedule interview |
| POST | `/api/interview-schedules/{id}/feedback` | Submit feedback |

### Offers

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/offers` | List offers |
| POST | `/api/offers` | Create offer |
| POST | `/api/offers/{id}/send` | Send offer |
| POST | `/api/offers/{id}/withdraw` | Withdraw offer |

---

## 📈 Performance

### Objectives (Goals/KPIs)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/performance-objectives` | List objectives |
| POST | `/api/performance-objectives` | Create objective |
| PUT | `/api/performance-objectives/{id}` | Update objective |
| POST | `/api/performance-objectives/{id}/progress` | Update progress |

### Appraisals

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/appraisal-cycles` | List cycles |
| POST | `/api/appraisal-cycles` | Create cycle |
| GET | `/api/appraisal-records` | List records |
| POST | `/api/appraisal-records/{id}/self-review` | Self review |
| POST | `/api/appraisal-records/{id}/manager-review` | Manager review |

### Recognition

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/recognition-categories` | List categories |
| GET | `/api/recognition-records` | List recognitions |
| POST | `/api/recognition-records` | Give recognition |

---

## 🎓 Training

### Programs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/training-programs` | List programs |
| POST | `/api/training-programs` | Create program |
| GET | `/api/training-programs/{id}` | Get program |
| PUT | `/api/training-programs/{id}` | Update program |

### Sessions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/training-sessions` | List sessions |
| POST | `/api/training-sessions` | Create session |
| POST | `/api/training-sessions/{id}/enroll` | Enroll employee |
| POST | `/api/training-sessions/{id}/complete` | Mark complete |

---

## 🗂️ Assets

### Asset Types

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/asset-types` | List asset types |
| POST | `/api/asset-types` | Create asset type |

### Assets

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/assets` | List assets |
| POST | `/api/assets` | Create asset |
| GET | `/api/assets/{id}` | Get asset |
| PUT | `/api/assets/{id}` | Update asset |
| POST | `/api/assets/{id}/assign` | Assign to employee |
| POST | `/api/assets/{id}/unassign` | Unassign |
| POST | `/api/assets/{id}/maintenance` | Log maintenance |

---

## 📅 Company

### Events

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/company-events` | List events |
| POST | `/api/company-events` | Create event |

### Holidays

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/company-holidays` | List holidays |
| POST | `/api/company-holidays` | Create holiday |

### Notices

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/company-notices` | List notices |
| POST | `/api/company-notices` | Create notice |

### Meetings

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/meetings` | List meetings |
| POST | `/api/meetings` | Schedule meeting |
| GET | `/api/meeting-rooms` | List rooms |
| POST | `/api/meeting-action-items` | Create action item |

---

## 📄 Documents & Policies

### Policies

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/organization-policies` | List policies |
| POST | `/api/organization-policies` | Create policy |
| POST | `/api/organization-policies/{id}/acknowledge` | Acknowledge |

### HR Documents

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/hr-documents` | List documents |
| POST | `/api/hr-documents` | Upload document |
| GET | `/api/document-categories` | List categories |

### Letter Templates

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/letter-templates` | List templates |
| POST | `/api/letter-templates` | Create template |
| POST | `/api/generated-letters` | Generate letter |

---

## ⚙️ Settings & Configuration

### System Config

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/system-configurations` | List configs |
| PUT | `/api/system-configurations/{id}` | Update config |

### IP Whitelisting

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/allowed-ip-addresses` | List IPs |
| POST | `/api/allowed-ip-addresses` | Add IP |
| DELETE | `/api/allowed-ip-addresses/{id}` | Remove IP |

---

## 📊 Reports

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/reports/attendance` | Attendance report |
| GET | `/api/reports/leave` | Leave report |
| GET | `/api/reports/payroll` | Payroll report |
| GET | `/api/reports/employees` | Employee report |
| GET | `/api/reports/recruitment` | Recruitment report |

---

## 🚀 Quick Start

```bash
# 1. Login to get token
curl -X POST http://localhost:8000/api/auth/sign-in \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@hrms.local","password":"password"}'

# 2. Use token in subsequent requests
curl http://localhost:8000/api/staff-members \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

**Total Endpoints:** 444
**Base URL:** `http://localhost:8000/api`
**Authentication:** Bearer Token (JWT via Sanctum)
