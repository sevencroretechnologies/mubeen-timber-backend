<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'advance_types',
        'application_notes',
        'asset_assignments',
        'attendance_regularizations',
        'benefit_types',
        'bonus_payments',
        'business_trips',
        'candidates',
        'candidate_assessments',
        'company_events',
        'company_event_attendees',
        'company_notice_recipients',
        'compensation_categories',
        'contracts',
        'contract_renewals',
        'contract_types',
        'custom_questions',
        'data_imports',
        'document_acknowledgments',
        'document_aws_configs',
        'document_local_configs',
        'document_locations',
        'document_wasabi_configs',
        'employee_onboardings',
        'generated_letters',
        'grievances',
        'hr_documents',
        'jobs',
        'job_applications',
        'job_categories',
        'job_postings',
        'job_requisitions',
        'letter_templates',
        'location_transfers',
        'media_directories',
        'media_files',
        'meetings',
        'meeting_action_items',
        'meeting_attendees',
        'meeting_minutes',
        'meeting_rooms',
        'meeting_types',
        'minimum_tax_limits',
        'offboardings',
        'offers',
        'offer_templates',
        'onboarding_tasks',
        'onboarding_task_completions',
        'organization_documents',
        'organization_policies',
        'policy_acknowledgments',
        'resources',
        'salary_advances',
        'salary_slips',
        'shift_assignments',
        'staff_benefits',
        'staff_files',
        'system_configurations',
        'tax_exemptions',
        'tax_slabs',
        'timesheets',
        'timesheet_projects',
        'time_off_categories',
        'time_off_requests',
        'training_participants',
        'training_programs',
        'training_sessions',
        'training_types',
        'users',
        'voluntary_exits',
        'withholding_types',
        'work_logs',

    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
