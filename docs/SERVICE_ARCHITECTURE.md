# Service-Oriented Architecture Documentation

This document describes the refactoring from controller-oriented to service-oriented architecture in the WorkDo HRMS application.

## Overview

The application has been refactored to follow a clean service-oriented architecture where controllers remain lightweight and delegate all business logic to dedicated service classes.

## Architecture Pattern

### Before (Controller-Oriented)

```
┌─────────────────────────────────────────────────────────────┐
│                      Controller                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  - Request validation                                │   │
│  │  - Business logic (queries, calculations, etc.)      │   │
│  │  - Data transformation                               │   │
│  │  - Response formatting                               │   │
│  │  - Error handling                                    │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                        Model                                 │
└─────────────────────────────────────────────────────────────┘
```

### After (Service-Oriented)

```
┌─────────────────────────────────────────────────────────────┐
│                      Controller                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  - Request validation                                │   │
│  │  - Delegate to service                               │   │
│  │  - Return standardized response                      │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                       Service                                │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  - Business logic                                    │   │
│  │  - Data queries and transformations                  │   │
│  │  - Complex calculations                              │   │
│  │  - Reusable across controllers                       │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                        Model                                 │
└─────────────────────────────────────────────────────────────┘
```

## Controller Responsibilities

Controllers are now lightweight and only handle:

1. Request validation using Laravel's built-in validation
2. Calling appropriate service methods
3. Returning standardized API responses using the `ApiResponse` trait

### Example Controller

```php
class AccessController extends Controller
{
    use ApiResponse;

    protected AuthService $service;

    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    public function signIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $result = $this->service->login($validated);

        return $this->success($result, 'Signed in successfully');
    }
}
```

## Service Responsibilities

Services contain all business logic and are:

1. Reusable across multiple controllers
2. Testable in isolation
3. Independent of HTTP concerns

### Example Service

```php
class AuthService
{
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new InvalidCredentialsException();
        }

        if (!$user->is_active) {
            throw new AccountDeactivatedException();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $this->formatUserData($user),
            'token' => $token,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }
}
```

## Standardized API Response Format

All API responses follow a consistent structure:

### Success Response

```json
{
    "success": true,
    "data": { ... },
    "message": "Operation successful"
}
```

### Success Response with Pagination

```json
{
    "success": true,
    "data": [ ... ],
    "message": "Records retrieved successfully",
    "meta": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 15,
        "total": 150
    }
}
```

### Error Response

```json
{
    "success": false,
    "data": null,
    "message": "Error description"
}
```

### Validation Error Response

```json
{
    "success": false,
    "data": null,
    "message": "The email field is required.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

## Error Handling

The application uses a comprehensive global exception handler that ensures all API errors return consistent JSON responses.

### Exception Types and HTTP Status Codes

| Exception Type | HTTP Status | Description |
|----------------|-------------|-------------|
| `InvalidCredentialsException` | 401 | Invalid email or password |
| `AccountDeactivatedException` | 403 | User account is deactivated |
| `AuthenticationException` | 401 | Missing or invalid token |
| `ValidationException` | 422 | Request validation failed |
| `AccessDeniedHttpException` | 403 | Permission denied |
| `ModelNotFoundException` | 404 | Resource not found |
| `NotFoundHttpException` | 404 | Route not found |
| `MethodNotAllowedHttpException` | 405 | HTTP method not allowed |

### Custom Exception Classes

Located in `app/Exceptions/`:

- `AuthenticationException.php` - Base authentication exception
- `InvalidCredentialsException.php` - Invalid login credentials
- `AccountDeactivatedException.php` - Deactivated account

## Services Created

The following services have been created to handle business logic:

### Authentication
- `AuthService` - User authentication, registration, password reset

### Staff Management
- `StaffMemberService` - Employee CRUD operations
- `JobTitleService` - Job title management
- `DivisionService` - Division management
- `OfficeLocationService` - Office location management

### Attendance & Time
- `AttendanceService` - Clock in/out, attendance tracking
- `WorkLogService` - Work log management
- `TimesheetService` - Timesheet management
- `ShiftService` - Shift management
- `AttendanceRegularizationService` - Attendance corrections

### Leave Management
- `LeaveService` - Leave requests and approvals
- `TimeOffCategoryService` - Leave type configuration

### Payroll
- `PayrollService` - Payroll processing
- `SalarySlipService` - Payslip generation
- `SalaryAdvanceService` - Salary advance requests
- `TaxService` - Tax calculations
- `StaffBenefitService` - Employee benefits
- `RecurringDeductionService` - Recurring deductions
- `IncentiveService` - Incentive management
- `BonusService` - Bonus payments
- `AllowanceService` - Allowance management
- `ExtraHoursService` - Overtime tracking
- `EmployerContributionService` - Employer contributions

### Recruitment
- `RecruitmentService` - Job postings and applications
- `CandidateService` - Candidate management
- `JobApplicationService` - Application processing
- `InterviewService` - Interview scheduling
- `CandidateAssessmentService` - Candidate assessments

### Assets
- `AssetService` - Asset management
- `AssetTypeService` - Asset type configuration

### Training
- `TrainingService` - Training programs

### Contracts
- `ContractService` - Employee contracts

### Meetings
- `MeetingService` - Meeting management

### Documents
- `HrDocumentService` - HR document management
- `LetterTemplateService` - Letter templates
- `OrganizationPolicyService` - Organization policies
- `MediaService` - Media file management

### Performance
- `PerformanceService` - Performance reviews
- `AppraisalService` - Appraisal management
- `ObjectiveService` - Objective tracking

### Onboarding/Offboarding
- `OnboardingService` - Employee onboarding
- `OffboardingService` - Employee offboarding
- `VoluntaryExitService` - Voluntary exit management

### Other
- `GrievanceService` - Grievance handling
- `RecognitionService` - Employee recognition
- `DisciplineService` - Disciplinary actions
- `BusinessTripService` - Business trip management
- `LocationTransferService` - Location transfers
- `CompanyEventService` - Company events
- `CompanyHolidayService` - Holiday management
- `CompanyNoticeService` - Company notices
- `RoleService` - Role management
- `SystemConfigurationService` - System settings
- `ReportService` - Report generation
- `DataImportService` - Data import
- `DataExportService` - Data export
- `DataTableService` - DataTable operations

## Frontend Integration

The frontend has been updated to properly handle the new error format:

### API Interceptor

The API interceptor now properly handles 401 errors without redirecting on login attempts:

```javascript
api.interceptors.response.use(
    (response) => response,
    (error) => {
        const isAuthEndpoint = error.config?.url?.includes('/auth/sign-in') || 
                               error.config?.url?.includes('/auth/sign-up');
        
        if (error.response?.status === 401 && !isAuthEndpoint) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            if (window.location.pathname !== '/login') {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);
```

### Login Form Error Handling

The login form now displays field-level validation errors:

```jsx
const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setFieldErrors({});

    try {
        await authService.login(email, password);
        navigate('/');
    } catch (err) {
        const response = err.response?.data;
        setError(response?.message || 'Invalid credentials.');
        if (response?.errors) {
            setFieldErrors(response.errors);
        }
    }
};
```

## Benefits of Service-Oriented Architecture

1. **Separation of Concerns**: Controllers handle HTTP, services handle business logic
2. **Reusability**: Services can be used across multiple controllers
3. **Testability**: Services can be unit tested in isolation
4. **Maintainability**: Easier to understand and modify code
5. **Scalability**: New features can be added without modifying existing controllers
6. **Consistency**: Standardized API responses across all endpoints
