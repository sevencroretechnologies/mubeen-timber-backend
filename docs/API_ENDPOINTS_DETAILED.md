# WorkDo HRMS API Endpoints - Detailed Documentation

Complete documentation of all API endpoints with request/response examples.

---

## Base Information

- **Base URL:** `http://localhost:8000/api`
- **Authentication:** Bearer Token (include `Authorization: Bearer <token>` header)
- **Content-Type:** `application/json`
- **Response Format:** All responses follow this structure:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

---

# 🔐 Authentication Module

## POST /auth/sign-in

Login and receive authentication token.

**Request:**

```json
{
  "email": "admin@hrms.local",
  "password": "password"
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@hrms.local",
      "role": "admin",
      "role_display": "Administrator"
    },
    "token": "1|abc123xyz...",
    "expires_at": "2025-01-19T00:00:00Z"
  }
}
```

**Error (401):**

```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

---

## POST /auth/sign-up

Register a new user account.

**Request:**

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securePassword123",
  "password_confirmation": "securePassword123"
}
```

**Response (201):**

```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 5,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "5|xyz789..."
  }
}
```

---

## POST /auth/sign-out

Logout and invalidate current token.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**

```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## GET /auth/profile

Get current authenticated user's profile.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@hrms.local",
      "role": "admin",
      "staff_member": {
        "id": 1,
        "staff_code": "EMP001",
        "full_name": "Admin User",
        "division": "Management",
        "job_title": "System Administrator"
      }
    }
  }
}
```

---

## POST /auth/change-password

Change the current user's password.

**Headers:** `Authorization: Bearer <token>`

**Request:**

```json
{
  "current_password": "oldPassword123",
  "new_password": "newSecurePassword456",
  "new_password_confirmation": "newSecurePassword456"
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

---

# 📊 Dashboard Module

## GET /dashboard

Get complete dashboard data including statistics, attendance, and recent activities.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**

```json
{
  "success": true,
  "data": {
    "employees": {
      "total": 50,
      "active": 45,
      "on_leave": 3,
      "inactive": 2,
      "new_this_month": 5
    },
    "attendance": {
      "date": "2025-12-19",
      "total_employees": 45,
      "present": 40,
      "absent": 5,
      "late": 3,
      "attendance_percentage": 88.9
    },
    "leave_requests": {
      "total": 120,
      "pending": 8,
      "approved": 100,
      "rejected": 12
    },
    "payroll": {
      "current_month": "December 2025",
      "total_payable": 250000.00,
      "processed": 40,
      "pending": 5
    },
    "upcoming_birthdays": [
      {"name": "John Doe", "date": "2025-12-25"}
    ],
    "upcoming_events": [
      {"title": "Christmas Party", "date": "2025-12-24"}
    ],
    "announcements": [
      {"title": "Office Closed", "date": "2025-12-25"}
    ]
  }
}
```

---

# 👥 Staff Management Module

## GET /staff-members

List all staff members with filtering and pagination.

**Headers:** `Authorization: Bearer <token>`

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | int | Page number (default: 1) |
| `per_page` | int | Items per page (default: 15) |
| `search` | string | Search by name or staff code |
| `status` | string | Filter: active, on_leave, suspended, terminated, resigned |
| `division_id` | int | Filter by division |
| `office_location_id` | int | Filter by location |

**Response (200):**

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "user_id": 1,
        "staff_code": "EMP001",
        "full_name": "John Smith",
        "personal_email": "john.personal@email.com",
        "mobile_number": "+1234567890",
        "birth_date": "1990-05-15",
        "gender": "male",
        "nationality": "American",
        "hire_date": "2023-01-15",
        "employment_status": "active",
        "compensation_type": "monthly",
        "base_salary": 5000.00,
        "office_location": {
          "id": 1,
          "name": "Headquarters"
        },
        "division": {
          "id": 2,
          "title": "Engineering"
        },
        "job_title": {
          "id": 3,
          "title": "Software Engineer"
        }
      }
    ],
    "current_page": 1,
    "last_page": 4,
    "per_page": 15,
    "total": 50
  }
}
```

---

## POST /staff-members

Create a new staff member.

**Headers:** `Authorization: Bearer <token>`

**Request:**

```json
{
  "full_name": "Jane Doe",
  "personal_email": "jane@email.com",
  "mobile_number": "+1987654321",
  "birth_date": "1992-08-20",
  "gender": "female",
  "nationality": "Canadian",
  "home_address": "123 Main St, Toronto",
  "staff_code": "EMP051",
  "office_location_id": 1,
  "division_id": 2,
  "job_title_id": 3,
  "hire_date": "2025-01-01",
  "compensation_type": "monthly",
  "base_salary": 4500.00,
  "bank_account_name": "Jane Doe",
  "bank_account_number": "1234567890",
  "bank_name": "TD Bank",
  "create_user_account": true,
  "user_email": "jane.doe@company.com",
  "user_password": "tempPassword123"
}
```

**Response (201):**

```json
{
  "success": true,
  "message": "Staff member created successfully",
  "data": {
    "id": 51,
    "staff_code": "EMP051",
    "full_name": "Jane Doe",
    "user_id": 55
  }
}
```

---

## GET /staff-members/{id}

Get detailed information about a specific staff member.

**Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "staff_code": "EMP001",
    "full_name": "John Smith",
    "personal_email": "john@email.com",
    "mobile_number": "+1234567890",
    "birth_date": "1990-05-15",
    "gender": "male",
    "home_address": "456 Oak Ave, New York",
    "nationality": "American",
    "passport_number": "AB1234567",
    "hire_date": "2023-01-15",
    "employment_status": "active",
    "compensation_type": "monthly",
    "base_salary": 5000.00,
    "bank_account_name": "John Smith",
    "bank_account_number": "9876543210",
    "bank_name": "Chase Bank",
    "office_location": {
      "id": 1,
      "name": "Headquarters",
      "address": "100 Business Plaza"
    },
    "division": {
      "id": 2,
      "title": "Engineering"
    },
    "job_title": {
      "id": 3,
      "title": "Software Engineer"
    },
    "user": {
      "id": 1,
      "email": "john.smith@company.com"
    }
  }
}
```

---

# ⏰ Attendance Module

## POST /clock-in

Record clock-in for the current authenticated user.

**Headers:** `Authorization: Bearer <token>`

**Request (optional):**

```json
{
  "notes": "Working from home today"
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "Clocked in successfully",
  "data": {
    "id": 150,
    "staff_member_id": 1,
    "log_date": "2025-12-19",
    "clock_in": "09:05:00",
    "status": "present",
    "late_minutes": 5,
    "clock_in_ip": "192.168.1.100"
  }
}
```

**Error (400):**

```json
{
  "success": false,
  "message": "Already clocked in today"
}
```

---

## POST /clock-out

Record clock-out for the current authenticated user.

**Response (200):**

```json
{
  "success": true,
  "message": "Clocked out successfully",
  "data": {
    "id": 150,
    "clock_out": "18:00:00",
    "total_hours": 8.92,
    "overtime_minutes": 0
  }
}
```

---

## GET /work-logs

List work log entries with filtering.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `staff_member_id` | int | Filter by employee |
| `start_date` | date | Start date (YYYY-MM-DD) |
| `end_date` | date | End date (YYYY-MM-DD) |
| `status` | string | present, absent, late, half_day |

**Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 150,
      "staff_member_id": 1,
      "staff_member": {
        "full_name": "John Smith",
        "staff_code": "EMP001"
      },
      "log_date": "2025-12-19",
      "status": "present",
      "clock_in": "09:05:00",
      "clock_out": "18:00:00",
      "late_minutes": 5,
      "early_leave_minutes": 0,
      "overtime_minutes": 0,
      "break_minutes": 60
    }
  ]
}
```

---

# 🏖️ Leave Management Module

## GET /time-off-categories

List all leave types/categories.

**Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Annual Leave",
      "annual_quota": 20,
      "is_paid": true,
      "is_active": true
    },
    {
      "id": 2,
      "title": "Sick Leave",
      "annual_quota": 10,
      "is_paid": true,
      "is_active": true
    },
    {
      "id": 3,
      "title": "Unpaid Leave",
      "annual_quota": null,
      "is_paid": false,
      "is_active": true
    }
  ]
}
```

---

## POST /time-off-requests

Submit a new leave request.

**Request:**

```json
{
  "time_off_category_id": 1,
  "start_date": "2025-12-25",
  "end_date": "2025-12-27",
  "reason": "Family vacation"
}
```

**Response (201):**

```json
{
  "success": true,
  "message": "Leave request submitted successfully",
  "data": {
    "id": 25,
    "staff_member_id": 1,
    "time_off_category_id": 1,
    "start_date": "2025-12-25",
    "end_date": "2025-12-27",
    "total_days": 3,
    "reason": "Family vacation",
    "approval_status": "pending",
    "request_date": "2025-12-19"
  }
}
```

---

## POST /time-off-requests/{id}/process

Approve or decline a leave request (Manager/HR only).

**Request:**

```json
{
  "action": "approve",
  "remarks": "Approved. Enjoy your vacation!"
}
```

Or to decline:

```json
{
  "action": "decline",
  "remarks": "Insufficient leave balance"
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "Leave request approved",
  "data": {
    "id": 25,
    "approval_status": "approved",
    "approved_by": 2,
    "approval_remarks": "Approved. Enjoy your vacation!",
    "approved_at": "2025-12-19T10:30:00Z"
  }
}
```

---

## GET /time-off-balance

Get leave balance for current user or specific employee.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `staff_member_id` | int | Employee ID (optional, defaults to current user) |

**Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "category": "Annual Leave",
      "category_id": 1,
      "annual_quota": 20,
      "used": 5,
      "pending": 3,
      "remaining": 12
    },
    {
      "category": "Sick Leave",
      "category_id": 2,
      "annual_quota": 10,
      "used": 2,
      "pending": 0,
      "remaining": 8
    }
  ]
}
```

---

# 💰 Payroll Module

## GET /salary-slips

List salary slips with filtering.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `staff_member_id` | int | Filter by employee |
| `salary_period` | string | Filter by period (YYYY-MM) |
| `status` | string | draft, generated, sent, paid |

**Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "slip_reference": "SAL-2025-12-001",
      "staff_member": {
        "id": 1,
        "full_name": "John Smith",
        "staff_code": "EMP001"
      },
      "salary_period": "2025-12",
      "basic_salary": 5000.00,
      "total_earnings": 5500.00,
      "total_deductions": 750.00,
      "net_payable": 4750.00,
      "status": "generated"
    }
  ]
}
```

---

## GET /salary-slips/{id}

Get detailed salary slip breakdown.

**Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "slip_reference": "SAL-2025-12-001",
    "staff_member": {
      "id": 1,
      "full_name": "John Smith",
      "staff_code": "EMP001",
      "bank_account_number": "****3210",
      "bank_name": "Chase Bank"
    },
    "salary_period": "2025-12",
    "basic_salary": 5000.00,
    "benefits_breakdown": {
      "Health Insurance": 200.00,
      "Transport Allowance": 150.00
    },
    "incentives_breakdown": {
      "Performance Bonus": 100.00
    },
    "bonus_breakdown": null,
    "overtime_breakdown": {
      "hours": 5,
      "rate": 10.00,
      "total": 50.00
    },
    "deductions_breakdown": {
      "Tax": 500.00,
      "Pension": 250.00
    },
    "total_earnings": 5500.00,
    "total_deductions": 750.00,
    "net_payable": 4750.00,
    "status": "generated",
    "generated_at": "2025-12-01T00:00:00Z"
  }
}
```

---

## POST /salary-slips/{id}/mark-paid

Mark a salary slip as paid.

**Request:**

```json
{
  "payment_date": "2025-12-25",
  "payment_reference": "TRX-123456"
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "Salary slip marked as paid",
  "data": {
    "id": 1,
    "status": "paid",
    "paid_at": "2025-12-25T00:00:00Z"
  }
}
```

---

# 🎯 Recruitment Module

## GET /jobs

List job postings.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | draft, published, closed |
| `department_id` | int | Filter by department |

**Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Senior Software Engineer",
      "department": "Engineering",
      "location": "Headquarters",
      "employment_type": "full_time",
      "experience_level": "senior",
      "salary_range": "$80,000 - $120,000",
      "status": "published",
      "applications_count": 25,
      "published_at": "2025-12-01"
    }
  ]
}
```

---

## POST /jobs

Create a new job posting.

**Request:**

```json
{
  "title": "Full Stack Developer",
  "description": "We are looking for an experienced...",
  "requirements": "5+ years experience, React, Node.js...",
  "division_id": 2,
  "office_location_id": 1,
  "employment_type": "full_time",
  "experience_level": "mid",
  "min_salary": 60000,
  "max_salary": 90000,
  "vacancies": 2,
  "deadline": "2026-01-31"
}
```

**Response (201):**

```json
{
  "success": true,
  "message": "Job posting created",
  "data": {
    "id": 5,
    "title": "Full Stack Developer",
    "status": "draft"
  }
}
```

---

## POST /job-applications/{id}/move-stage

Move application to next recruitment stage.

**Request:**

```json
{
  "job_stage_id": 3,
  "notes": "Passed technical interview"
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "Application moved to Interview stage"
}
```

---

# 📈 Performance Module

## POST /performance-objectives

Create a performance objective/goal.

**Request:**

```json
{
  "staff_member_id": 1,
  "title": "Complete Project X",
  "description": "Deliver all milestones for Project X",
  "target_date": "2025-06-30",
  "key_results": [
    "Deliver Phase 1 by March",
    "Deliver Phase 2 by May",
    "Complete testing by June"
  ],
  "weight": 30
}
```

**Response (201):**

```json
{
  "success": true,
  "data": {
    "id": 15,
    "title": "Complete Project X",
    "progress": 0,
    "status": "active"
  }
}
```

---

## POST /performance-objectives/{id}/progress

Update objective progress.

**Request:**

```json
{
  "progress": 65,
  "notes": "Phase 1 and 2 completed, starting testing"
}
```

**Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 15,
    "progress": 65,
    "status": "in_progress"
  }
}
```

---

# 📊 Reports Module

## GET /reports/attendance

Generate attendance report.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `start_date` | date | Start date (required) |
| `end_date` | date | End date (required) |
| `division_id` | int | Filter by department |
| `staff_member_id` | int | Filter by employee |

**Response (200):**

```json
{
  "success": true,
  "data": {
    "period": {
      "start": "2025-12-01",
      "end": "2025-12-31"
    },
    "summary": {
      "total_working_days": 22,
      "average_attendance": 92.5,
      "total_late_instances": 15,
      "total_overtime_hours": 45
    },
    "records": [
      {
        "employee": "John Smith",
        "staff_code": "EMP001",
        "present_days": 20,
        "absent_days": 2,
        "late_days": 3,
        "total_hours": 176.5
      }
    ]
  }
}
```

---

## GET /reports/payroll

Generate payroll report.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `period` | string | Salary period (YYYY-MM) |

**Response (200):**

```json
{
  "success": true,
  "data": {
    "period": "2025-12",
    "summary": {
      "total_employees": 50,
      "total_gross": 275000.00,
      "total_deductions": 42500.00,
      "total_net": 232500.00,
      "paid_count": 48,
      "pending_count": 2
    },
    "by_department": [
      {
        "department": "Engineering",
        "employee_count": 20,
        "total_payable": 120000.00
      }
    ]
  }
}
```

---

# Error Responses

All endpoints may return these common errors:

## 401 Unauthorized

```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

## 403 Forbidden

```json
{
  "success": false,
  "message": "You do not have permission to perform this action"
}
```

## 404 Not Found

```json
{
  "success": false,
  "message": "Resource not found"
}
```

## 422 Validation Error

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "start_date": ["The start date must be a date after today."]
  }
}
```

## 500 Server Error

```json
{
  "success": false,
  "message": "An unexpected error occurred"
}
```

---

**Documentation Version:** 1.0.0
**Last Updated:** December 2025
**Total Endpoints:** 444
