<?php

use App\Http\Controllers\Api\crm\AppointmentController;
use App\Http\Controllers\Api\crm\CampaignController;
// use App\Http\Controllers\Api\CompetitorController; // Does not exist yet
use App\Http\Controllers\Api\Staff\ContractController;
// use App\Http\Controllers\Api\CrmNoteController; // Does not exist yet
// use App\Http\Controllers\Api\CrmSettingController; // Does not exist yet
use App\Http\Controllers\Api\crm\DashboardController;
use App\Http\Controllers\Api\crm\EnumController;
use App\Http\Controllers\Api\crm\LeadController;
use App\Http\Controllers\Api\crm\OpportunityController;
use App\Http\Controllers\Api\crm\OpportunityLostReasonController;
use App\Http\Controllers\Api\crm\ProspectController;
// use App\Http\Controllers\Api\SalesStageController; // Does not exist yet
use App\Http\Controllers\Api\crm\SourceController;
use App\Http\Controllers\Api\crm\StatusController;
use App\Http\Controllers\Api\crm\RequestTypeController;
use App\Http\Controllers\Api\crm\IndustryTypeController;
// use App\Http\Controllers\Api\UserController; // Does not exist yet
use App\Http\Controllers\Api\crm\OpportunityStageController;
use App\Http\Controllers\Api\crm\OpportunityTypeController;
use App\Http\Controllers\Api\crm\TerritoryController;
use App\Http\Controllers\Api\crm\ContactController;
use App\Http\Controllers\Api\crm\CustomerGroupController;
use App\Http\Controllers\Api\crm\PaymentTermController;
use App\Http\Controllers\Api\crm\PriceListController;
use App\Http\Controllers\Api\crm\CustomerController;
use App\Http\Controllers\Api\crm\SalesTaskController;
use App\Http\Controllers\Api\crm\SalesTaskDetailController;
use App\Http\Controllers\Api\crm\TaskController;
use App\Http\Controllers\Api\crm\TaskSourceController;
use App\Http\Controllers\Api\crm\TaskTypeController;
use App\Http\Controllers\Api\crm\ProductCategoryController;
use App\Http\Controllers\Api\crm\ProductController;
use App\Http\Controllers\Api\crm\OpportunityProductController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\ResourceController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\UserRoleController;
use App\Http\Controllers\Api\Admin\UsersController;
use App\Http\Controllers\Api\Assets\AssetController;
use App\Http\Controllers\Api\Assets\AssetTypeController;
use App\Http\Controllers\Api\Attendance\AttendanceRegularizationController;
use App\Http\Controllers\Api\Attendance\ExtraHoursRecordController;
use App\Http\Controllers\Api\Attendance\ShiftController;
use App\Http\Controllers\Api\Attendance\TimesheetController;
use App\Http\Controllers\Api\Attendance\TimesheetProjectController;
// Attendance Controllers
use App\Http\Controllers\Api\Attendance\WorkLogController;
use App\Http\Controllers\Api\Auth\AccessController;
use App\Http\Controllers\Api\Company\CompanyEventController;
use App\Http\Controllers\Api\Company\CompanyHolidayController;
use App\Http\Controllers\Api\Company\CompanyNoticeController;
use App\Http\Controllers\Api\Company\MeetingController;
// Leave Controllers
use App\Http\Controllers\Api\Company\MeetingRoomController;
use App\Http\Controllers\Api\Company\MeetingTypeController;
use App\Http\Controllers\Api\Company\MeetingAttendeeController;
use App\Http\Controllers\Api\Company\MeetingMinutesController;
use App\Http\Controllers\Api\Company\MeetingActionItemController;
// Payroll Controllers
use App\Http\Controllers\Api\Documents\DocumentCategoryController;
use App\Http\Controllers\Api\Documents\DocumentLocationController;
use App\Http\Controllers\Api\Documents\FileCategoryController;
use App\Http\Controllers\Api\Documents\GeneratedLetterController;
use App\Http\Controllers\Api\Documents\HrDocumentController;
use App\Http\Controllers\Api\Documents\LetterTemplateController;
use App\Http\Controllers\Api\Documents\MediaDirectoryController;
use App\Http\Controllers\Api\Documents\MediaFileController;
use App\Http\Controllers\Api\Leave\TimeOffCategoryController;
use App\Http\Controllers\Api\Leave\TimeOffRequestController;
use App\Http\Controllers\Api\Organization\DivisionController;
use App\Http\Controllers\Api\Organization\OfficeLocationController;
use App\Http\Controllers\Api\Organization\OrganizationDocumentController;
// Recruitment Controllers
use App\Http\Controllers\Api\Organization\OrganizationPolicyController;
use App\Http\Controllers\Api\Payroll\AdvanceTypeController;
use App\Http\Controllers\Api\Payroll\BenefitTypeController;
use App\Http\Controllers\Api\Payroll\BonusPaymentController;
use App\Http\Controllers\Api\Payroll\CompensationCategoryController;
use App\Http\Controllers\Api\Payroll\EmployerContributionController;
use App\Http\Controllers\Api\Payroll\IncentiveRecordController;
use App\Http\Controllers\Api\Payroll\MinimumTaxLimitController;
use App\Http\Controllers\Api\Payroll\RecurringDeductionController;
use App\Http\Controllers\Api\Payroll\SalaryAdvanceController;
use App\Http\Controllers\Api\Payroll\SalarySlipController;
// Performance Controllers
use App\Http\Controllers\Api\Payroll\StaffBenefitController;
use App\Http\Controllers\Api\Payroll\TaxExemptionController;
use App\Http\Controllers\Api\Payroll\TaxSlabController;
use App\Http\Controllers\Api\Payroll\WithholdingTypeController;
use App\Http\Controllers\Api\Performance\AppraisalCycleController;
// Staff Controllers
use App\Http\Controllers\Api\Performance\AppraisalRecordController;
use App\Http\Controllers\Api\Performance\PerformanceObjectiveController;
use App\Http\Controllers\Api\Performance\RecognitionCategoryController;
use App\Http\Controllers\Api\Performance\RecognitionRecordController;
use App\Http\Controllers\Api\Recruitment\CandidateAssessmentController;
use App\Http\Controllers\Api\Recruitment\CandidateController;
use App\Http\Controllers\Api\Recruitment\InterviewScheduleController;
use App\Http\Controllers\Api\Recruitment\JobApplicationController;
use App\Http\Controllers\Api\Recruitment\JobCategoryController;
use App\Http\Controllers\Api\Recruitment\JobController;
use App\Http\Controllers\Api\Recruitment\JobRequisitionController;
use App\Http\Controllers\Api\Recruitment\JobStageController;
use App\Http\Controllers\Api\Recruitment\JobTitleController;
// Organization Controllers
use App\Http\Controllers\Api\Recruitment\OfferController;
use App\Http\Controllers\Api\Recruitment\OfferTemplateController;
use App\Http\Controllers\Api\Reports\DashboardController as ReportsDashboardController;
use App\Http\Controllers\Api\Reports\DataExportController;
use App\Http\Controllers\Api\Recruitment\CandidateSourceController;
// Assets Controllers
use App\Http\Controllers\Api\Reports\DataImportController;
use App\Http\Controllers\Api\Reports\DataTableController;
// Company Controllers
use App\Http\Controllers\Api\Reports\ReportController;
use App\Http\Controllers\Api\Settings\AllowedIpAddressController;
use App\Http\Controllers\Api\Settings\WorkingDayController;
use App\Http\Controllers\Api\Settings\SystemConfigurationController;
use App\Http\Controllers\Api\Staff\ContractController as StaffContractController;
use App\Http\Controllers\Api\Staff\ContractTypeController;
use App\Http\Controllers\Api\Staff\DisciplineNoteController;
// Training Controllers
use App\Http\Controllers\Api\Staff\EmployeeOnboardingController;
use App\Http\Controllers\Api\Staff\ExitCategoryController;
use App\Http\Controllers\Api\Staff\GrievanceController;
// Documents Controllers
use App\Http\Controllers\Api\Staff\LocationTransferController;
use App\Http\Controllers\Api\Staff\OffboardingController;
use App\Http\Controllers\Api\Staff\OnboardingTemplateController;
use App\Http\Controllers\Api\Staff\RoleUpgradeController;
use App\Http\Controllers\Api\Staff\StaffFileController;
use App\Http\Controllers\Api\Staff\StaffMemberController;
use App\Http\Controllers\Api\Staff\VoluntaryExitController;
use App\Http\Controllers\Api\Training\TrainingProgramController;
// Reports Controllers
use App\Http\Controllers\Api\Training\TrainingSessionController;
use App\Http\Controllers\Api\Training\TrainingTypeController;
use App\Http\Controllers\Api\Travel\BusinessTripController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DocumentConfigController;
// Settings Controllers
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentTypeController;

// Travel Controllers
use App\Http\Controllers\OrganizationController;


//updated code

Route::prefix('auth')->group(function () {
    Route::post('/sign-up', [AccessController::class , 'signUp']);
    Route::post('/sign-in', [AccessController::class , 'signIn']);
    Route::post('/forgot-password', [AccessController::class , 'forgotPassword']);
    Route::post('/reset-password', [AccessController::class , 'resetPassword']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::prefix('auth')->group(function () {
            Route::post('/sign-out', [AccessController::class , 'signOut']);
            Route::get('/profile', [AccessController::class , 'profile']);
        }
        );
        // Users
        Route::get('users', [UsersController::class , 'index']);

        Route::get('dashboard/stats', [DashboardController::class , 'stats']);
        Route::get('dashboard/sales-overview', [DashboardController::class , 'salesOverview']);
        Route::get('dashboard/lead-conversion-funnel', [DashboardController::class , 'leadConversionFunnel']);
        Route::get('dashboard/opportunity-pipeline', [DashboardController::class , 'opportunityPipeline']);

    Route::get('leads/get-lead', [LeadController::class, 'getLead']);
    Route::apiResource('leads', LeadController::class);
    Route::post('leads/{id}/convert-to-opportunity', [LeadController::class, 'convertToOpportunity']);
    Route::post('leads/{id}/add-to-prospect', [LeadController::class, 'addToProspect']);
    Route::post('leads/{id}/create-prospect', [LeadController::class, 'createProspect']);
  
    Route::get('opportunity/get-opportunity', [OpportunityController::class, 'getOpportunity']);
    Route::apiResource('opportunities', OpportunityController::class);
    Route::post('opportunities/{id}/declare-lost', [OpportunityController::class, 'declareLost']);
    Route::get('opportunities/{id}/products', [OpportunityController::class, 'getProducts']);
    Route::post('opportunities/set-multiple-status', [OpportunityController::class, 'setMultipleStatus']);

        Route::apiResource('prospects', ProspectController::class);

        Route::apiResource('campaigns', CampaignController::class);

        Route::apiResource('sources', SourceController::class);

        Route::apiResource('contracts', ContractController::class);
        Route::post('contracts/{id}/sign', [ContractController::class , 'sign']);

        Route::apiResource('appointments', AppointmentController::class);

        // Route::get('notes', [CrmNoteController::class, 'index']);
        // Route::post('notes', [CrmNoteController::class, 'store']);
        // Route::delete('notes/{id}', [CrmNoteController::class, 'destroy']);
    
        // Route::apiResource('sales-stages', SalesStageController::class);
        Route::apiResource('statuses', StatusController::class);
        Route::apiResource('request-types', RequestTypeController::class);
        Route::apiResource('industry-types', IndustryTypeController::class);
        Route::apiResource('opportunity-stages', OpportunityStageController::class);
        Route::apiResource('opportunity-types', OpportunityTypeController::class);
        Route::apiResource('lost-reasons', OpportunityLostReasonController::class);
        // Route::apiResource('competitors', CompetitorController::class)->only(['index', 'store', 'destroy']);
        Route::apiResource('territories', TerritoryController::class);
        Route::apiResource('contacts', ContactController::class);
        Route::apiResource('contract-types', ContractTypeController::class);
        Route::apiResource('contracts', ContractController::class);
        Route::post('/contracts/{contract}/renew', [ContractController::class , 'renew']);
        Route::post('/contracts/{contract}/terminate', [ContractController::class , 'terminate']);
        Route::get('/contracts-expiring', [ContractController::class , 'expiring']);
        Route::get('/contracts/employee/{staffMemberId}', [ContractController::class , 'byEmployee']);
        // Master Data
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('customer-groups', CustomerGroupController::class);
        Route::apiResource('payment-terms', PaymentTermController::class);
        Route::apiResource('price-lists', PriceListController::class);
        Route::apiResource('product-categories', ProductCategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('opportunity-products', OpportunityProductController::class);
        Route::apiResource('sales-tasks', SalesTaskController::class);
        Route::apiResource('sales-task-details', SalesTaskDetailController::class);
        // Route::apiResource('tasks', TaskController::class);
        Route::apiResource('task-sources', TaskSourceController::class);
        Route::apiResource('task-types', TaskTypeController::class);

        // Enum routes
        Route::get('enums/qualification-statuses', [EnumController::class , 'qualificationStatuses']);
        Route::get('enums/genders', [EnumController::class , 'genders']);

    // Route::get('settings', [CrmSettingController::class, 'show']);
    // Route::put('settings', [CrmSettingController::class, 'update']);
});



// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/sign-up', [AccessController::class , 'signUp']);
    Route::post('/sign-in', [AccessController::class , 'signIn']);
    Route::post('/forgot-password', [AccessController::class , 'forgotPassword']);
    Route::post('/reset-password', [AccessController::class , 'resetPassword']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::prefix('auth')->group(function () {
            Route::post('/sign-out', [AccessController::class , 'signOut']);
            Route::get('/profile', [AccessController::class , 'profile']);
        }
        );

        // ============================================
        // Dashboard
        // ============================================
        Route::get('/dashboard', [DashboardController::class , 'index']);
        Route::get('/dashboard/employee-stats', [DashboardController::class , 'employeeStats']);
        Route::get('/dashboard/attendance-summary', [DashboardController::class , 'attendanceSummary']);
        Route::get('/dashboard/employee-growth', [DashboardController::class , 'employeeGrowth']);
        Route::get('/dashboard/department-distribution', [DashboardController::class , 'departmentDistribution']);

        // ============================================
        // PROMPT SET 2: Organization Structure
        // ============================================
        Route::apiResource('office-locations', OfficeLocationController::class);
        Route::apiResource('divisions', DivisionController::class);
        Route::apiResource('job-titles', JobTitleController::class);
        Route::apiResource('file-categories', FileCategoryController::class);

        // AJAX endpoints for cascading dropdowns
        Route::post('/fetch-divisions', [DivisionController::class , 'fetchByLocation']);
        Route::post('/fetch-job-titles', [JobTitleController::class , 'fetchByDivision']);

        // ============================================
        // PROMPT SET 3: Staff Member Management
        // ============================================
        Route::get('/staff-members', [StaffMemberController::class , 'index'])->middleware('permission:view_staff');
        Route::post('/staff-members', [StaffMemberController::class , 'store'])->middleware('permission:create_staff');
        Route::get('/staff-members/{staff_member}', [StaffMemberController::class , 'show'])->middleware('permission:view_staff');
        Route::put('/staff-members/{staff_member}', [StaffMemberController::class , 'update'])->middleware('permission:edit_staff');
        Route::delete('/staff-members/{staff_member}', [StaffMemberController::class , 'destroy'])->middleware('permission:delete_staff');
        Route::get('/staff-members-dropdown', [StaffMemberController::class , 'dropdown'])->middleware('permission:view_staff');

        // Staff Files (nested resource)
        Route::get('/staff-members/{staffMember}/files', [StaffFileController::class , 'index']);
        Route::post('/staff-members/{staffMember}/files', [StaffFileController::class , 'store']);
        Route::get('/staff-members/{staffMember}/files/{file}', [StaffFileController::class , 'show']);
        Route::delete('/staff-members/{staffMember}/files/{file}', [StaffFileController::class , 'destroy']);

        // ============================================
        // PROMPT SET 4: Recognition & Advancement
        // ============================================
        Route::apiResource('recognition-categories', RecognitionCategoryController::class);
        Route::apiResource('recognition-records', RecognitionRecordController::class);
        Route::apiResource('role-upgrades', RoleUpgradeController::class);
        Route::apiResource('location-transfers', LocationTransferController::class);

        // ============================================
        // PROMPT SET 5: Discipline & Exit
        // ============================================
        Route::apiResource('discipline-notes', DisciplineNoteController::class);
        Route::apiResource('exit-categories', ExitCategoryController::class);
        Route::apiResource('offboardings', OffboardingController::class);
        Route::apiResource('voluntary-exits', VoluntaryExitController::class);
        Route::post('/voluntary-exits/{voluntaryExit}/process', [VoluntaryExitController::class , 'processApproval']);

        // ============================================
        // PROMPT SET 6: Business Trips & Grievances
        // ============================================
        Route::apiResource('business-trips', BusinessTripController::class);
        Route::post('/business-trips/{businessTrip}/process', [BusinessTripController::class , 'processApproval']);

        Route::apiResource('grievances', GrievanceController::class);
        Route::post('/grievances/{grievance}/status', [GrievanceController::class , 'updateStatus']);

        Route::apiResource('company-notices', CompanyNoticeController::class);
        Route::post('/company-notices/{companyNotice}/read', [CompanyNoticeController::class , 'markAsRead']);

        Route::apiResource('company-holidays', CompanyHolidayController::class);
        Route::post('/company-holidays/bulk-import', [CompanyHolidayController::class , 'bulkImport']);

        // ============================================
        // PROMPT SET 7: Leave Management
        // ============================================
        Route::apiResource('time-off-categories', TimeOffCategoryController::class)->middleware('permission:manage_time_off_categories');
        Route::get('/leave/categories-list', [TimeOffCategoryController::class , 'index']); // Public endpoint for dropdown - no permission needed
        Route::get('/leave/my-requests', [TimeOffRequestController::class , 'myRequests']);
        Route::get('/leave/all-requests', [TimeOffRequestController::class , 'index'])->middleware('permission:view_all_time_off');
        Route::apiResource('time-off-requests', TimeOffRequestController::class);
        Route::post('/time-off-requests/{timeOffRequest}/cancel', [TimeOffRequestController::class , 'cancel']);
        Route::post('/time-off-requests/{timeOffRequest}/process', [TimeOffRequestController::class , 'processApproval'])->middleware('permission:approve_time_off');
        Route::get('/time-off-stats', [TimeOffRequestController::class , 'statistics'])->middleware('permission:view_time_off');
        Route::get('/time-off-balance', [TimeOffRequestController::class , 'getBalance'])->middleware('permission:view_time_off');
        Route::get('/leave/my-balance', [TimeOffRequestController::class , 'myBalance']);

        // ============================================
        // PROMPT SET 8: Attendance Management
        // ============================================
        Route::apiResource('work-logs', WorkLogController::class)->middleware('permission:view_attendance');
        Route::post('/clock-in', [WorkLogController::class , 'clockIn'])->middleware('permission:view_attendance');
        Route::post('/clock-out', [WorkLogController::class , 'clockOut'])->middleware('permission:view_attendance');
        Route::post('/clock-in-self', [WorkLogController::class , 'clockInSelf']);
        Route::post('/clock-out-self', [WorkLogController::class , 'clockOutSelf']);
        Route::get('/current-status-self', [WorkLogController::class , 'currentStatusSelf']);
        Route::post('/work-logs/bulk', [WorkLogController::class , 'bulkStore'])->middleware('permission:bulk_attendance');
        Route::get('/current-status', [WorkLogController::class , 'currentStatus'])->middleware('permission:view_attendance');
        Route::get('/attendance-summary', [WorkLogController::class , 'summary'])->middleware('permission:view_attendance');
        Route::get('/my-logs', [WorkLogController::class , 'myLogs']); // My Work Logs - no permission needed
        Route::get('/my-summary', [WorkLogController::class , 'mySummary']);
        Route::get('/my-monthly-attendance', [WorkLogController::class , 'myMonthlyAttendance']);
        Route::get('/attendance/shift-analytics', [WorkLogController::class , 'shiftAnalytics']);

        // ============================================
        // PROMPT SET 9: Payroll Setup
        // ============================================
        Route::apiResource('compensation-categories', CompensationCategoryController::class)->middleware('permission:view_compensation');
        Route::apiResource('benefit-types', BenefitTypeController::class)->middleware('permission:view_benefits');
        Route::apiResource('advance-types', AdvanceTypeController::class)->middleware('permission:view_compensation');
        Route::apiResource('withholding-types', WithholdingTypeController::class)->middleware('permission:view_benefits');

        // ============================================
        // PROMPT SET 10: Salary Components
        // ============================================
        Route::apiResource('staff-benefits', StaffBenefitController::class);
        Route::apiResource('incentive-records', IncentiveRecordController::class);
        Route::apiResource('salary-advances', SalaryAdvanceController::class);
        Route::post('/salary-advances/{salaryAdvance}/payment', [SalaryAdvanceController::class , 'recordPayment']);
        Route::apiResource('recurring-deductions', RecurringDeductionController::class);

        // ============================================
        // PROMPT SET 11: Payroll Processing
        // ============================================
        Route::apiResource('bonus-payments', BonusPaymentController::class);
        Route::apiResource('extra-hours-records', ExtraHoursRecordController::class);
        Route::apiResource('employer-contributions', EmployerContributionController::class);

        // ============================================
        // PROMPT SET 12: Payslip Generation
        // ============================================
        Route::apiResource('salary-slips', SalarySlipController::class)->except(['store', 'update'])->middleware('permission:view_payslips');
        Route::get('/payroll/my-slips', [SalarySlipController::class , 'mySlips']); // No permission needed - for own slips
        Route::post('/salary-slips/calculate', [SalarySlipController::class , 'calculate'])->middleware('permission:generate_payslips');
        Route::post('/salary-slips/generate', [SalarySlipController::class , 'generate'])->middleware('permission:generate_payslips');
        Route::post('/salary-slips/bulk-generate', [SalarySlipController::class , 'bulkGenerate'])->middleware('permission:generate_payslips');
        Route::post('/salary-slips/{salarySlip}/mark-paid', [SalarySlipController::class , 'markPaid'])->middleware('permission:generate_payslips');
        Route::get('payroll/salary-slips/{id}/download', [SalarySlipController::class , 'download'])->middleware('permission:view_payslips');

        // ============================================
        // PROMPT SET 13: Tax Management
        // ============================================
        Route::apiResource('tax-slabs', TaxSlabController::class)->middleware('permission:view_benefits');
        Route::post('/tax-slabs/calculate', [TaxSlabController::class , 'calculate'])->middleware('permission:view_benefits');
        Route::apiResource('tax-exemptions', TaxExemptionController::class);
        Route::apiResource('minimum-tax-limits', MinimumTaxLimitController::class);

        // ============================================
        // PROMPT SET 14: Events & Calendar
        // ============================================
        Route::apiResource('company-events', CompanyEventController::class);
        Route::post('/company-events/{companyEvent}/rsvp', [CompanyEventController::class , 'rsvp']);
        Route::get('/calendar-data', [CompanyEventController::class , 'calendarData']);

        // ============================================
        // PROMPT SET 15: Company Policies & Documents
        // ============================================
        Route::apiResource('document-types', DocumentTypeController::class);
        Route::apiResource('organization-policies', OrganizationPolicyController::class);
        Route::post('/organization-policies/{organizationPolicy}/acknowledge', [OrganizationPolicyController::class , 'acknowledge']);
        Route::get('/policies-pending', [OrganizationPolicyController::class , 'pending']);
        Route::apiResource('organization-documents', OrganizationDocumentController::class);
        Route::get('/organization-documents/{organizationDocument}/download', [OrganizationDocumentController::class , 'download']);

        // ============================================
        // PROMPT SET 16: Letter Templates
        // ============================================
        Route::apiResource('letter-templates', LetterTemplateController::class);
        Route::get('/letter-placeholders', [LetterTemplateController::class , 'placeholders']);
        Route::apiResource('generated-letters', GeneratedLetterController::class)->except(['store', 'update']);
        Route::post('/generated-letters/generate', [GeneratedLetterController::class , 'generate']);
        Route::get('/generated-letters/{generatedLetter}/preview', [GeneratedLetterController::class , 'preview']);

        // ============================================
        // PROMPT SET 17: IP Restriction & Settings
        // ============================================
        Route::apiResource('allowed-ip-addresses', AllowedIpAddressController::class);
        Route::get('/check-ip', [AllowedIpAddressController::class , 'check']);

        // Working Days
        Route::get('working-days/count', [WorkingDayController::class , 'getWorkingDaysCount']);
        Route::get('working-days/active', [WorkingDayController::class , 'getActiveConfig']);
        Route::apiResource('working-days', WorkingDayController::class);

        Route::get('/system-configurations', [SystemConfigurationController::class , 'index']);
        Route::post('/system-configurations/get', [SystemConfigurationController::class , 'getValue']);
        Route::post('/system-configurations/set', [SystemConfigurationController::class , 'setValue']);
        Route::post('/system-configurations/bulk', [SystemConfigurationController::class , 'bulkUpdate']);
        Route::get('/system-configurations/category/{category}', [SystemConfigurationController::class , 'getByCategory']);
        Route::delete('/system-configurations/{systemConfiguration}', [SystemConfigurationController::class , 'destroy']);

        // Working Days Configuration
        Route::apiResource('working-days', WorkingDayController::class);
        Route::get('/working-days/count', [WorkingDayController::class , 'getWorkingDaysCount']);
        Route::get('/working-days/active', [WorkingDayController::class , 'getActiveConfig']);

        // ============================================
        // PROMPT SET 18: Reports & Dashboard
        // ============================================
        Route::prefix('reports')->group(function () {
            Route::get('/attendance', [ReportController::class , 'attendanceReport']);
            Route::get('/leave', [ReportController::class , 'leaveReport']);
            Route::get('/payroll', [ReportController::class , 'payrollReport']);
            Route::get('/headcount', [ReportController::class , 'headcountReport']);
            Route::get('/attendance/export', [ReportController::class , 'exportAttendanceReport']);
            Route::get('/leave/export', [ReportController::class , 'exportLeaveReport']);
            Route::get('/payroll/export', [ReportController::class , 'exportPayrollReport']);
        }
        );
        Route::get('/dashboard', [ReportController::class , 'dashboard']);

        // ============================================
        // PROMPT SET 19: DataTables (Server-Side)
        // ============================================
        Route::prefix('datatables')->group(function () {
            Route::get('/staff-members', [DataTableController::class , 'staffMembers']);
            Route::get('/attendance', [DataTableController::class , 'attendance']);
            Route::get('/leave-requests', [DataTableController::class , 'leaveRequests']);
            Route::get('/payslips', [DataTableController::class , 'payslips']);
        }
        );

        // ============================================
        // PROMPT SET 20: Import/Export
        // ============================================
        Route::prefix('imports')->group(function () {
            Route::get('/', [DataImportController::class , 'index']);
            Route::get('/template', [DataImportController::class , 'template']);
            Route::post('/staff-members', [DataImportController::class , 'importStaffMembers']);
            Route::post('/attendance', [DataImportController::class , 'importAttendance']);
            Route::post('/holidays', [DataImportController::class , 'importHolidays']);
            Route::get('/{dataImport}', [DataImportController::class , 'show']);
        }
        );

        Route::prefix('exports')->group(function () {
            Route::get('/staff-members', [DataExportController::class , 'exportStaffMembers']);
            Route::get('/attendance', [DataExportController::class , 'exportAttendance']);
            Route::get('/leaves', [DataExportController::class , 'exportLeaves']);
            Route::get('/payroll', [DataExportController::class , 'exportPayroll']);
        }
        );

        // ============================================
        // PROMPT SET 21: Performance Management
        // ============================================
        Route::apiResource('performance-objectives', PerformanceObjectiveController::class);
        Route::post('/performance-objectives/{performanceObjective}/progress', [PerformanceObjectiveController::class , 'updateProgress']);
        Route::post('/performance-objectives/{performanceObjective}/rate', [PerformanceObjectiveController::class , 'rate']);

        // ============================================
        // PROMPT SET 22: Appraisals
        // ============================================
        Route::apiResource('appraisal-cycles', AppraisalCycleController::class);
        Route::post('/appraisal-cycles/{appraisalCycle}/activate', [AppraisalCycleController::class , 'activate']);
        Route::post('/appraisal-cycles/{appraisalCycle}/close', [AppraisalCycleController::class , 'close']);
        Route::apiResource('appraisal-records', AppraisalRecordController::class)->except(['store', 'update', 'destroy']);
        Route::post('/appraisal-records/{appraisalRecord}/self-review', [AppraisalRecordController::class , 'submitSelfReview']);
        Route::post('/appraisal-records/{appraisalRecord}/manager-review', [AppraisalRecordController::class , 'submitManagerReview']);
        Route::get('/my-appraisals', [AppraisalRecordController::class , 'myAppraisals']);

        // ============================================
        // PROMPT SET 23: Asset Management
        // ============================================
        Route::apiResource('asset-types', AssetTypeController::class);
        Route::apiResource('assets', AssetController::class);
        Route::post('/assets/{asset}/assign', [AssetController::class , 'assign']);
        Route::post('/assets/{asset}/return', [AssetController::class , 'returnAsset']);
        Route::post('/assets/{asset}/maintenance', [AssetController::class , 'setMaintenance']);
        Route::get('/assets-available', [AssetController::class , 'available']);
        Route::get('/assets/employee/{staffMemberId}', [AssetController::class , 'byEmployee']);

        // ============================================
        // PROMPT SET 24: Training Management
        // ============================================
        Route::apiResource('training-types', TrainingTypeController::class);
        Route::apiResource('training-programs', TrainingProgramController::class);
        Route::apiResource('training-sessions', TrainingSessionController::class);
        Route::post('/training-sessions/{trainingSession}/enroll', [TrainingSessionController::class , 'enroll']);
        Route::post('/training-sessions/{trainingSession}/complete', [TrainingSessionController::class , 'complete']);
        Route::get('/training/employee/{staffMemberId}', [TrainingSessionController::class , 'employeeTraining']);
        Route::get('/training-participants', [TrainingSessionController::class , 'allParticipants']);
        Route::put('/training-participants/{trainingParticipant}', [TrainingSessionController::class , 'updateParticipant']);
        Route::delete('/training-participants/{trainingParticipant}', [TrainingSessionController::class , 'deleteParticipant']);

        // ============================================
        // PROMPT SET 25: Recruitment - Jobs
        // ============================================
        Route::apiResource('job-categories', JobCategoryController::class);
        Route::apiResource('job-stages', JobStageController::class);
        Route::post('/job-stages/reorder', [JobStageController::class , 'reorder']);
        Route::apiResource('jobs', JobController::class);
        Route::post('/jobs/{job}/publish', [JobController::class , 'publish']);
        Route::post('/jobs/{job}/close', [JobController::class , 'close']);
        Route::get('/jobs/{job}/questions', [JobController::class , 'questions']);
        Route::post('/jobs/{job}/questions', [JobController::class , 'addQuestion']);
        Route::put('/custom-questions/{question}', [JobController::class , 'updateQuestion']);
        Route::delete('/custom-questions/{question}', [JobController::class , 'deleteQuestion']);

        // ============================================
        // PROMPT SET 26: Recruitment - Candidates
        // ============================================
        Route::apiResource('candidate-sources', CandidateSourceController::class);
        Route::apiResource('candidates', CandidateController::class);
        Route::get('/candidates/{candidate}/resume', [CandidateController::class , 'downloadResume']);
        Route::post('/candidates/{candidate}/archive', [CandidateController::class , 'archive']);
        Route::post('/candidates/{candidate}/convert-to-employee', [CandidateController::class , 'convertToEmployee']);

        // ============================================
        // PROMPT SET 27: Recruitment - Applications & Interviews
        // ============================================
        Route::get('/job-applications', [JobApplicationController::class , 'index']);
        Route::post('/jobs/{job}/applications', [JobApplicationController::class , 'store']);
        Route::get('/job-applications/{jobApplication}', [JobApplicationController::class , 'show']);
        Route::post('/job-applications/{jobApplication}/move-stage', [JobApplicationController::class , 'moveStage']);
        Route::post('/job-applications/{jobApplication}/rate', [JobApplicationController::class , 'rate']);
        Route::post('/job-applications/{jobApplication}/notes', [JobApplicationController::class , 'addNote']);
        Route::post('/job-applications/{jobApplication}/shortlist', [JobApplicationController::class , 'shortlist']);
        Route::post('/job-applications/{jobApplication}/reject', [JobApplicationController::class , 'reject']);
        Route::post('/job-applications/{jobApplication}/hire', [JobApplicationController::class , 'hire']);

        Route::apiResource('interview-schedules', InterviewScheduleController::class);
        Route::post('/interview-schedules/{interviewSchedule}/feedback', [InterviewScheduleController::class , 'feedback']);
        Route::post('/interview-schedules/{interviewSchedule}/reschedule', [InterviewScheduleController::class , 'reschedule']);
        Route::get('/interviews/calendar', [InterviewScheduleController::class , 'calendar']);
        Route::get('/interviews/today', [InterviewScheduleController::class , 'today']);

        // ============================================
        // PROMPT SET 28: Onboarding
        // ============================================
        Route::apiResource('onboarding-templates', OnboardingTemplateController::class);
        Route::post('/onboarding-templates/{onboardingTemplate}/tasks', [OnboardingTemplateController::class , 'addTask']);
        Route::apiResource('employee-onboardings', EmployeeOnboardingController::class)->except(['update', 'destroy']);
        Route::post('/employee-onboardings/{employeeOnboarding}/complete-task', [EmployeeOnboardingController::class , 'completeTask']);
        Route::get('/onboardings/pending', [EmployeeOnboardingController::class , 'pending']);

        // ============================================
        // PROMPT SET 29: Contract Management
        // ============================================
        Route::apiResource('contract-types', ContractTypeController::class);
        Route::apiResource('contracts', ContractController::class);
        Route::post('/contracts/{contract}/renew', [ContractController::class , 'renew']);
        Route::post('/contracts/{contract}/terminate', [ContractController::class , 'terminate']);
        Route::get('/contracts-expiring', [ContractController::class , 'expiring']);
        Route::get('/contracts/employee/{staffMemberId}', [ContractController::class , 'byEmployee']);

        // ============================================
        // PROMPT SET 30: Meeting Management
        // ============================================
        Route::apiResource('meeting-types', MeetingTypeController::class);
        Route::apiResource('meeting-rooms', MeetingRoomController::class);
        Route::get('/meeting-rooms-available', [MeetingRoomController::class , 'available']);
        Route::apiResource('meetings', MeetingController::class);
        Route::apiResource('meeting-attendees', MeetingAttendeeController::class);
        Route::apiResource('meeting-minutes', MeetingMinutesController::class);
        Route::apiResource('meeting-action-items', MeetingActionItemController::class);
        Route::post('/meetings/{meeting}/attendees', [MeetingController::class , 'addAttendees']);
        Route::post('/meetings/{meeting}/start', [MeetingController::class , 'start']);
        Route::post('/meetings/{meeting}/complete', [MeetingController::class , 'complete']);
        Route::post('/meetings/{meeting}/minutes', [MeetingController::class , 'addMinutes']);
        Route::post('/meetings/{meeting}/action-items', [MeetingController::class , 'addActionItem']);
        Route::post('/meeting-action-items/{meetingActionItem}/complete', [MeetingController::class , 'completeActionItem']);
        Route::get('/meetings-calendar', [MeetingController::class , 'calendar']);
        Route::get('/my-meetings', [MeetingController::class , 'myMeetings']);

        // ============================================
        // PROMPT SET 31: Shifts Management
        // ============================================
        Route::apiResource('shifts', ShiftController::class)->middleware('permission:view_attendance');
        Route::post('/shifts/{shift}/assign', [ShiftController::class , 'assign'])->middleware('permission:edit_attendance');
        Route::get('/shift-roster', [ShiftController::class , 'roster'])->middleware('permission:view_attendance');
        Route::get('/shifts/employee/{staffMemberId}', [ShiftController::class , 'employeeShifts'])->middleware('permission:view_attendance');

        // ============================================
        // PROMPT SET 32: Timesheets
        // ============================================
        Route::apiResource('timesheet-projects', TimesheetProjectController::class);
        Route::apiResource('timesheets', TimesheetController::class);
        Route::post('/timesheets/bulk', [TimesheetController::class , 'bulkStore']);
        Route::post('/timesheets/{timesheet}/submit', [TimesheetController::class , 'submit']);
        Route::post('/timesheets/{timesheet}/approve', [TimesheetController::class , 'approve']);
        Route::post('/timesheets/{timesheet}/reject', [TimesheetController::class , 'reject']);
        Route::get('/timesheet-summary', [TimesheetController::class , 'summary']);
        Route::get('/timesheets/employee/{staffMemberId}', [TimesheetController::class , 'employeeTimesheets']);
        Route::get('/timesheet-report', [TimesheetController::class , 'report']);

        // ============================================
        // DOCUMENT MANAGEMENT (100% Coverage)
        // ============================================
        Route::apiResource('document-categories', DocumentCategoryController::class);
        Route::apiResource('hr-documents', HrDocumentController::class);
        Route::get('/hr-documents/{hrDocument}/download', [HrDocumentController::class , 'download']);
        Route::post('/hr-documents/{hrDocument}/acknowledge', [HrDocumentController::class , 'acknowledge']);
        Route::get('/hr-documents/{hrDocument}/acknowledgments', [HrDocumentController::class , 'acknowledgments']);
        Route::get('/pending-acknowledgments', [HrDocumentController::class , 'pendingAcknowledgments']);

        // ============================================
        // MEDIA LIBRARY (100% Coverage)
        // ============================================
        Route::apiResource('media-directories', MediaDirectoryController::class);
        Route::post('/media-directories/{mediaDirectory}/move', [MediaDirectoryController::class , 'move']);
        Route::apiResource('media-files', MediaFileController::class);
        Route::get('/media-files/{mediaFile}/download', [MediaFileController::class , 'download']);
        Route::post('/media-files/{mediaFile}/move', [MediaFileController::class , 'move']);
        Route::post('/media-files/{mediaFile}/share', [MediaFileController::class , 'share']);
        Route::post('/media-files/{mediaFile}/unshare', [MediaFileController::class , 'unshare']);

        // ============================================
        // JOB REQUISITIONS (100% Coverage)
        // ============================================
        Route::apiResource('job-requisitions', JobRequisitionController::class);
        Route::post('/job-requisitions/{jobRequisition}/approve', [JobRequisitionController::class , 'approve']);
        Route::post('/job-requisitions/{jobRequisition}/reject', [JobRequisitionController::class , 'reject']);
        Route::get('/job-requisitions-pending', [JobRequisitionController::class , 'pending']);

        // ============================================
        // OFFER MANAGEMENT (100% Coverage)
        // ============================================
        Route::apiResource('offer-templates', OfferTemplateController::class);
        Route::get('/offer-template-variables', [OfferTemplateController::class , 'variables']);
        Route::apiResource('offers', OfferController::class);
        Route::post('/offers/{offer}/send', [OfferController::class , 'send']);
        Route::post('/offers/{offer}/accept', [OfferController::class , 'accept']);
        Route::post('/offers/{offer}/reject', [OfferController::class , 'reject']);
        Route::post('/offers/{offer}/withdraw', [OfferController::class , 'withdraw']);
        Route::get('/offers-pending', [OfferController::class , 'pending']);
        Route::get('/offers-expired', [OfferController::class , 'expired']);

        // ============================================
        // CANDIDATE ASSESSMENTS (100% Coverage)
        // ============================================
        Route::apiResource('candidate-assessments', CandidateAssessmentController::class);
        Route::post('/candidate-assessments/{candidateAssessment}/complete', [CandidateAssessmentController::class , 'complete']);
        Route::post('/candidate-assessments/{candidateAssessment}/cancel', [CandidateAssessmentController::class , 'cancel']);
        Route::get('/candidates/{candidateId}/assessments', [CandidateAssessmentController::class , 'candidateAssessments']);

        // ============================================
        // ATTENDANCE REGULARIZATION (100% Coverage)
        // ============================================
        Route::apiResource('attendance-regularizations', AttendanceRegularizationController::class)->only(['index', 'store', 'show'])->middleware('permission:view_attendance');
        Route::post('/attendance-regularizations/{attendanceRegularization}/approve', [AttendanceRegularizationController::class , 'approve'])->middleware('permission:approve_attendance');
        Route::post('/attendance-regularizations/{attendanceRegularization}/reject', [AttendanceRegularizationController::class , 'reject'])->middleware('permission:approve_attendance');
        Route::get('/attendance-regularizations-pending', [AttendanceRegularizationController::class , 'pending'])->middleware('permission:view_attendance');
        Route::get('/my-regularization-requests', [AttendanceRegularizationController::class , 'myRequests']);

        // ============================================
        // DYNAMIC STORAGE DOCUMENTS & CRUD
        // ============================================
    
        // UNIFIED UPLOAD (Recommended - Auto-detects storage based on org/company)
        Route::post('/documents/upload/{staff_id}', [DocumentController::class , 'upload']);
        // Standard CRUD
        Route::get('/documents', [DocumentController::class , 'index']); // List
        Route::get('/documents/{id}', [DocumentController::class , 'show']); // Show (includes URL)
        Route::get('/documents/{id}/download', [DocumentController::class , 'download']); // Download
        Route::put('/documents/{id}', [DocumentController::class , 'update']); // Update Metadata
        Route::delete('/documents/{id}', [DocumentController::class , 'destroy']); // Delete File & Record
        Route::get('documents/{id}/view', [DocumentController::class , 'view'])
            ->withoutMiddleware('auth:sanctum');


        // ============================================
        // ORGANIZATIONS & COMPANIES
        // ============================================
        // Organizations
        Route::get('/organizations', [OrganizationController::class , 'index'])->middleware('permission:manage_settings');
        Route::post('/organizations', [OrganizationController::class , 'store'])->middleware('permission:manage_settings');
        Route::get('/organizations/{organization}', [OrganizationController::class , 'show'])->middleware('permission:manage_settings');
        Route::put('/organizations/{organization}', [OrganizationController::class , 'update'])->middleware('permission:manage_settings');
        Route::delete('/organizations/{organization}', [OrganizationController::class , 'destroy'])->middleware('permission:manage_settings');

        // Companies
        Route::get('/companies', [CompanyController::class , 'index'])->middleware('permission:view_companies');
        Route::post('/companies', [CompanyController::class , 'store'])->middleware('permission:create_companies');
        Route::get('/companies/{company}', [CompanyController::class , 'show'])->middleware('permission:view_companies');
        Route::put('/companies/{company}', [CompanyController::class , 'update'])->middleware('permission:edit_companies');
        Route::delete('/companies/{company}', [CompanyController::class , 'destroy'])->middleware('permission:delete_companies');

        // Document Locations
        Route::get('/document-locations', [DocumentLocationController::class , 'index']);
        Route::post('/document-locations', [DocumentLocationController::class , 'store']);

        // Document Types
        Route::get('/document-types', [DocumentTypeController::class , 'index']);
        Route::post('/document-types', [DocumentTypeController::class , 'store']);
        Route::get('/document-types/{documentType}', [DocumentTypeController::class , 'show']);
        Route::put('/document-types/{documentType}', [DocumentTypeController::class , 'update']);
        Route::delete('/document-types/{documentType}', [DocumentTypeController::class , 'destroy']);

        // Document Configurations (Separate Tables)
        // Local
        Route::post('/document-configs/local', [DocumentConfigController::class , 'storeLocal']);
        Route::put('/document-configs/local/{id}', [DocumentConfigController::class , 'updateLocal']);

        // Wasabi
        Route::post('/document-configs/wasabi', [DocumentConfigController::class , 'storeWasabi']);
        Route::put('/document-configs/wasabi/{id}', [DocumentConfigController::class , 'updateWasabi']);

        // AWS
        Route::post('/document-configs/aws', [DocumentConfigController::class , 'storeAws']);
        Route::put('/document-configs/aws/{id}', [DocumentConfigController::class , 'updateAws']);

        // Show Config
        Route::get('/document-configs/local/{locationId}', [DocumentConfigController::class , 'showLocal']);
        Route::get('/document-configs/wasabi/{locationId}', [DocumentConfigController::class , 'showWasabi']);
        Route::get('/document-configs/aws/{locationId}', [DocumentConfigController::class , 'showAws']);
        Route::get('/document-configs/{locationId}', [DocumentConfigController::class , 'show']);

        // ============================================
        // ROLE MANAGEMENT (RBAC)
        // ============================================
        Route::prefix('roles')->group(function () {
            Route::get('/', [RoleController::class , 'index'])->middleware('permission:view_roles');
            Route::post('/', [RoleController::class , 'store'])->middleware('permission:create_roles');
            Route::get('/{id}', [RoleController::class , 'show'])->middleware('permission:view_roles');
            Route::put('/{id}', [RoleController::class , 'update'])->middleware('permission:edit_roles');
            Route::delete('/{id}', [RoleController::class , 'destroy'])->middleware('permission:delete_roles');
            Route::post('/{id}/permissions', [RoleController::class , 'syncPermissions'])->middleware('permission:edit_roles');
            Route::get('/{id}/permissions', [RoleController::class , 'getPermissions'])->middleware('permission:view_roles');
        }
        );

        Route::prefix('permissions')->middleware('permission:view_roles')->group(function () {
            Route::get('/', [PermissionController::class , 'index']);
            Route::get('/grouped', [PermissionController::class , 'groupedByResource']);
            Route::get('/{id}', [PermissionController::class , 'show']);
        }
        );

        Route::prefix('resources')->middleware('permission:view_roles')->group(function () {
            Route::get('/', [ResourceController::class , 'index']);
            Route::get('/{id}', [ResourceController::class , 'show']);
            Route::get('/slug/{slug}', [ResourceController::class , 'getBySlug']);
        }
        );

        Route::prefix('users')->group(function () {
            Route::get('/', [UserRoleController::class , 'index'])->middleware('permission:view_roles');
            Route::get('/{id}', [UserRoleController::class , 'show'])->middleware('permission:view_roles');
            Route::put('/{id}', [UserRoleController::class , 'update'])->middleware('permission:edit_users');
            Route::delete('/{id}', [UserRoleController::class , 'destroy'])->middleware('permission:edit_users');
            Route::get('/{id}/roles', [UserRoleController::class , 'getUserRoles'])->middleware('permission:view_roles');
            Route::post('/{id}/roles', [UserRoleController::class , 'assignRoles'])->middleware('permission:assign_roles');
            Route::post('/{id}/roles/add', [UserRoleController::class , 'addRole'])->middleware('permission:assign_roles');
            Route::post('/{id}/roles/remove', [UserRoleController::class , 'removeRole'])->middleware('permission:assign_roles');
        }
        );

        // Users by Organization
        Route::get('/users-by-org', [UsersController::class , 'getUsersByOrgId'])->middleware('permission:view_roles');
        // Users by Company
        Route::get('/users-by-company', [UsersController::class , 'getUsersByCompanyId'])->middleware('permission:view_roles');
});
