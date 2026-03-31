<?php

use App\Http\Controllers\Api\crm\AppointmentController;
use App\Http\Controllers\Api\crm\CampaignController;
use App\Http\Controllers\Api\crm\DashboardController;
use App\Http\Controllers\Api\crm\EnumController;
use App\Http\Controllers\Api\crm\LeadController;
use App\Http\Controllers\Api\crm\OpportunityController;
use App\Http\Controllers\Api\crm\OpportunityLostReasonController;
use App\Http\Controllers\Api\crm\ProspectController;
use App\Http\Controllers\Api\crm\SourceController;
use App\Http\Controllers\Api\crm\StatusController;
use App\Http\Controllers\Api\crm\RequestTypeController;
use App\Http\Controllers\Api\crm\IndustryTypeController;
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
use App\Http\Controllers\Api\Auth\AccessController;
use App\Http\Controllers\CompanyController as CompanyMgmtController;
use App\Http\Controllers\OrganizationController;

// ============================================
// Public Auth Routes
// ============================================
Route::prefix('auth')->group(function () {
    Route::post('/sign-up', [AccessController::class, 'signUp']);
    Route::post('/sign-in', [AccessController::class, 'signIn']);
    Route::post('/forgot-password', [AccessController::class, 'forgotPassword']);
    Route::post('/reset-password', [AccessController::class, 'resetPassword']);
});

// ============================================
// Protected Routes
// ============================================
Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/sign-out', [AccessController::class, 'signOut']);
        Route::get('/profile', [AccessController::class, 'profile']);
    });

    Route::get('users', [UsersController::class, 'index']);

    // ============================================
    // CRM Dashboard
    // ============================================
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('dashboard/sales-overview', [DashboardController::class, 'salesOverview']);
    Route::get('dashboard/lead-conversion-funnel', [DashboardController::class, 'leadConversionFunnel']);
    Route::get('dashboard/opportunity-pipeline', [DashboardController::class, 'opportunityPipeline']);

    // ============================================
    // CRM - Leads
    // ============================================
    Route::get('leads/get-lead', [LeadController::class, 'getLead']);
    Route::apiResource('leads', LeadController::class);
    Route::post('leads/{id}/convert-to-opportunity', [LeadController::class, 'convertToOpportunity']);
    Route::post('leads/{id}/add-to-prospect', [LeadController::class, 'addToProspect']);
    Route::post('leads/{id}/create-prospect', [LeadController::class, 'createProspect']);

    // ============================================
    // CRM - Opportunities
    // ============================================
    Route::get('opportunity/get-opportunity', [OpportunityController::class, 'getOpportunity']);
    Route::apiResource('opportunities', OpportunityController::class);
    Route::post('opportunities/{id}/declare-lost', [OpportunityController::class, 'declareLost']);
    Route::get('opportunities/{id}/products', [OpportunityController::class, 'getProducts']);
    Route::post('opportunities/set-multiple-status', [OpportunityController::class, 'setMultipleStatus']);

    // ============================================
    // CRM - Prospects, Campaigns, Sources
    // ============================================
    Route::apiResource('prospects', ProspectController::class);
    Route::apiResource('campaigns', CampaignController::class);
    Route::apiResource('sources', SourceController::class);
    Route::apiResource('appointments', AppointmentController::class);

    // ============================================
    // CRM - Master Data
    // ============================================
    Route::apiResource('statuses', StatusController::class);
    Route::apiResource('request-types', RequestTypeController::class);
    Route::apiResource('industry-types', IndustryTypeController::class);
    Route::apiResource('opportunity-stages', OpportunityStageController::class);
    Route::apiResource('opportunity-types', OpportunityTypeController::class);
    Route::apiResource('lost-reasons', OpportunityLostReasonController::class);
    Route::apiResource('territories', TerritoryController::class);
    Route::apiResource('contacts', ContactController::class);

    // ============================================
    // CRM - Customers & Products
    // ============================================
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('customer-groups', CustomerGroupController::class);
    Route::apiResource('payment-terms', PaymentTermController::class);
    Route::apiResource('price-lists', PriceListController::class);
    Route::apiResource('product-categories', ProductCategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('opportunity-products', OpportunityProductController::class);

    // ============================================
    // CRM - Sales Tasks
    // ============================================
    Route::apiResource('sales-tasks', SalesTaskController::class);
    Route::apiResource('sales-task-details', SalesTaskDetailController::class);
    Route::apiResource('task-sources', TaskSourceController::class);
    Route::apiResource('task-types', TaskTypeController::class);

    // ============================================
    // CRM - Enums
    // ============================================
    Route::get('enums/qualification-statuses', [EnumController::class, 'qualificationStatuses']);
    Route::get('enums/genders', [EnumController::class, 'genders']);

    // ============================================
    // Organizations & Companies
    // ============================================
    Route::get('/organizations', [OrganizationController::class, 'index'])->middleware('permission:manage_settings');
    Route::post('/organizations', [OrganizationController::class, 'store'])->middleware('permission:manage_settings');
    Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->middleware('permission:manage_settings');
    Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->middleware('permission:manage_settings');
    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy'])->middleware('permission:manage_settings');

    Route::get('/companies', [CompanyMgmtController::class, 'index'])->middleware('permission:view_companies');
    Route::post('/companies', [CompanyMgmtController::class, 'store'])->middleware('permission:create_companies');
    Route::get('/companies/{company}', [CompanyMgmtController::class, 'show'])->middleware('permission:view_companies');
    Route::put('/companies/{company}', [CompanyMgmtController::class, 'update'])->middleware('permission:edit_companies');
    Route::delete('/companies/{company}', [CompanyMgmtController::class, 'destroy'])->middleware('permission:delete_companies');

    // ============================================
    // RBAC - Roles, Permissions, Resources
    // ============================================
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->middleware('permission:view_roles');
        Route::post('/', [RoleController::class, 'store'])->middleware('permission:create_roles');
        Route::get('/{id}', [RoleController::class, 'show'])->middleware('permission:view_roles');
        Route::put('/{id}', [RoleController::class, 'update'])->middleware('permission:edit_roles');
        Route::delete('/{id}', [RoleController::class, 'destroy'])->middleware('permission:delete_roles');
        Route::post('/{id}/permissions', [RoleController::class, 'syncPermissions'])->middleware('permission:edit_roles');
        Route::get('/{id}/permissions', [RoleController::class, 'getPermissions'])->middleware('permission:view_roles');
    });

    Route::prefix('permissions')->middleware('permission:view_roles')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::get('/grouped', [PermissionController::class, 'groupedByResource']);
        Route::get('/{id}', [PermissionController::class, 'show']);
    });

    Route::prefix('resources')->middleware('permission:view_roles')->group(function () {
        Route::get('/', [ResourceController::class, 'index']);
        Route::get('/{id}', [ResourceController::class, 'show']);
        Route::get('/slug/{slug}', [ResourceController::class, 'getBySlug']);
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [UserRoleController::class, 'index'])->middleware('permission:view_roles');
        Route::get('/{id}', [UserRoleController::class, 'show'])->middleware('permission:view_roles');
        Route::put('/{id}', [UserRoleController::class, 'update'])->middleware('permission:edit_users');
        Route::delete('/{id}', [UserRoleController::class, 'destroy'])->middleware('permission:edit_users');
        Route::get('/{id}/roles', [UserRoleController::class, 'getUserRoles'])->middleware('permission:view_roles');
        Route::post('/{id}/roles', [UserRoleController::class, 'assignRoles'])->middleware('permission:assign_roles');
        Route::post('/{id}/roles/add', [UserRoleController::class, 'addRole'])->middleware('permission:assign_roles');
        Route::post('/{id}/roles/remove', [UserRoleController::class, 'removeRole'])->middleware('permission:assign_roles');
    });

    Route::get('/users-by-org', [UsersController::class, 'getUsersByOrgId'])->middleware('permission:view_roles');
    Route::get('/users-by-company', [UsersController::class, 'getUsersByCompanyId'])->middleware('permission:view_roles');

    // ============================================
    // TIMBER INVENTORY & STOCK MANAGEMENT
    // ============================================
    Route::prefix('timber')->group(function () {

        // Wood Types
        Route::apiResource('wood-types', \App\Http\Controllers\Api\Timber\TimberWoodTypeController::class);

        // Suppliers
        Route::apiResource('suppliers', \App\Http\Controllers\Api\Timber\TimberSupplierController::class);

        // Warehouses
        Route::get('warehouses', [\App\Http\Controllers\Api\Timber\TimberWarehouseController::class, 'index']);
        Route::post('warehouses', [\App\Http\Controllers\Api\Timber\TimberWarehouseController::class, 'store']);
        Route::put('warehouses/{id}', [\App\Http\Controllers\Api\Timber\TimberWarehouseController::class, 'update']);
        Route::delete('warehouses/{id}', [\App\Http\Controllers\Api\Timber\TimberWarehouseController::class, 'destroy']);

        // Stock
        Route::get('stock', [\App\Http\Controllers\Api\Timber\TimberStockController::class, 'index']);
        Route::get('stock/movements', [\App\Http\Controllers\Api\Timber\TimberStockController::class, 'movements']);
        Route::get('stock/low-alerts', [\App\Http\Controllers\Api\Timber\TimberStockController::class, 'lowAlerts']);
        Route::get('stock/valuation', [\App\Http\Controllers\Api\Timber\TimberStockController::class, 'valuation']);
        Route::get('stock/check-availability', [\App\Http\Controllers\Api\Timber\TimberStockController::class, 'checkAvailability']);
        Route::post('stock/adjust', [\App\Http\Controllers\Api\Timber\TimberStockController::class, 'adjust']);
        Route::put('stock/threshold/{woodTypeId}', [\App\Http\Controllers\Api\Timber\TimberStockController::class, 'setThreshold']);
        Route::get('stock/{woodTypeId}', [\App\Http\Controllers\Api\Timber\TimberStockController::class, 'show']);

        // Purchase Orders
        Route::apiResource('purchase-orders', \App\Http\Controllers\Api\Timber\TimberPurchaseOrderController::class);
        Route::post('purchase-orders/{id}/send', [\App\Http\Controllers\Api\Timber\TimberPurchaseOrderController::class, 'send']);
        Route::post('purchase-orders/{id}/receive', [\App\Http\Controllers\Api\Timber\TimberPurchaseOrderController::class, 'receive']);

        // Material Requisitions
        Route::get('material-requisitions', [\App\Http\Controllers\Api\Timber\TimberMaterialRequisitionController::class, 'index']);
        Route::post('material-requisitions', [\App\Http\Controllers\Api\Timber\TimberMaterialRequisitionController::class, 'store']);
        Route::get('material-requisitions/{id}', [\App\Http\Controllers\Api\Timber\TimberMaterialRequisitionController::class, 'show']);
        Route::post('material-requisitions/{id}/approve', [\App\Http\Controllers\Api\Timber\TimberMaterialRequisitionController::class, 'approve']);
        Route::post('material-requisitions/{id}/reject', [\App\Http\Controllers\Api\Timber\TimberMaterialRequisitionController::class, 'reject']);
        Route::post('material-requisitions/{id}/return', [\App\Http\Controllers\Api\Timber\TimberMaterialRequisitionController::class, 'returnMaterials']);

        // Stock Alerts
        Route::get('stock-alerts', [\App\Http\Controllers\Api\Timber\TimberStockAlertController::class, 'index']);
        Route::post('stock-alerts/{id}/resolve', [\App\Http\Controllers\Api\Timber\TimberStockAlertController::class, 'resolve']);
    });
});
