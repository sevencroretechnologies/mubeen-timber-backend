<?php

namespace Database\Seeders;

use App\Models\Resource;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create resources (feature groups) - CRM + Timber only
        $resources = [
            ['name' => 'Organizations', 'slug' => 'organizations', 'icon' => 'Building', 'description' => 'Manage organizations', 'sort_order' => 1],
            ['name' => 'Companies', 'slug' => 'companies', 'icon' => 'Briefcase', 'description' => 'Manage companies', 'sort_order' => 2],
            ['name' => 'CRM', 'slug' => 'crm', 'icon' => 'Users', 'description' => 'Customer relationship management', 'sort_order' => 3],
            ['name' => 'Leads', 'slug' => 'leads', 'icon' => 'UserPlus', 'description' => 'Manage sales leads', 'sort_order' => 4],
            ['name' => 'Opportunities', 'slug' => 'opportunities', 'icon' => 'TrendingUp', 'description' => 'Manage sales opportunities', 'sort_order' => 5],
            ['name' => 'Customers', 'slug' => 'customers', 'icon' => 'Users', 'description' => 'Manage customers', 'sort_order' => 6],
            ['name' => 'Contacts', 'slug' => 'contacts', 'icon' => 'BookOpen', 'description' => 'Manage contacts', 'sort_order' => 7],
            ['name' => 'Products', 'slug' => 'products', 'icon' => 'Package', 'description' => 'Manage product catalog', 'sort_order' => 8],
            ['name' => 'Campaigns', 'slug' => 'campaigns', 'icon' => 'Megaphone', 'description' => 'Manage marketing campaigns', 'sort_order' => 9],
            ['name' => 'Sales Tasks', 'slug' => 'sales_tasks', 'icon' => 'CheckSquare', 'description' => 'Manage sales tasks', 'sort_order' => 10],
            ['name' => 'Timber Inventory', 'slug' => 'timber_inventory', 'icon' => 'Layers', 'description' => 'Timber stock and inventory management', 'sort_order' => 11],
            ['name' => 'Timber Purchases', 'slug' => 'timber_purchases', 'icon' => 'ShoppingCart', 'description' => 'Timber purchase orders and suppliers', 'sort_order' => 12],
            ['name' => 'Reports', 'slug' => 'reports', 'icon' => 'BarChart', 'description' => 'View and export reports', 'sort_order' => 13],
            ['name' => 'Settings', 'slug' => 'settings', 'icon' => 'Settings', 'description' => 'Configure system settings', 'sort_order' => 14],
            ['name' => 'Role Management', 'slug' => 'roles', 'icon' => 'Shield', 'description' => 'Manage roles and permissions', 'sort_order' => 15],
        ];

        foreach ($resources as $resource) {
            Resource::updateOrCreate(['slug' => $resource['slug']], $resource);
        }

        // Remove old HRMS resources that no longer exist
        Resource::whereIn('slug', [
            'staff', 'attendance', 'time_off', 'payroll',
            'benefits', 'deductions', 'tax_slabs', 'recruitment',
            'performance', 'meetings',
        ])->delete();

        // Define permissions with resource and action
        $permissionDefinitions = [
            // ============================================
            // ORGANIZATIONS (Admin only)
            // ============================================
            ['name' => 'view_organizations', 'resource' => 'organizations', 'action' => 'view', 'description' => 'View organizations', 'sort_order' => 1],
            ['name' => 'create_organizations', 'resource' => 'organizations', 'action' => 'create', 'description' => 'Create organizations', 'sort_order' => 2],
            ['name' => 'edit_organizations', 'resource' => 'organizations', 'action' => 'edit', 'description' => 'Edit organizations', 'sort_order' => 3],
            ['name' => 'delete_organizations', 'resource' => 'organizations', 'action' => 'delete', 'description' => 'Delete organizations', 'sort_order' => 4],

            // ============================================
            // COMPANIES (Admin, Org)
            // ============================================
            ['name' => 'view_companies', 'resource' => 'companies', 'action' => 'view', 'description' => 'View companies', 'sort_order' => 1],
            ['name' => 'create_companies', 'resource' => 'companies', 'action' => 'create', 'description' => 'Create companies', 'sort_order' => 2],
            ['name' => 'edit_companies', 'resource' => 'companies', 'action' => 'edit', 'description' => 'Edit companies', 'sort_order' => 3],
            ['name' => 'delete_companies', 'resource' => 'companies', 'action' => 'delete', 'description' => 'Delete companies', 'sort_order' => 4],

            // ============================================
            // LEADS (Admin, Org, Company)
            // ============================================
            ['name' => 'view_leads', 'resource' => 'leads', 'action' => 'view', 'description' => 'View leads', 'sort_order' => 1],
            ['name' => 'create_leads', 'resource' => 'leads', 'action' => 'create', 'description' => 'Create leads', 'sort_order' => 2],
            ['name' => 'edit_leads', 'resource' => 'leads', 'action' => 'edit', 'description' => 'Edit leads', 'sort_order' => 3],
            ['name' => 'delete_leads', 'resource' => 'leads', 'action' => 'delete', 'description' => 'Delete leads', 'sort_order' => 4],
            ['name' => 'export_leads', 'resource' => 'leads', 'action' => 'export', 'description' => 'Export leads', 'sort_order' => 5],

            // ============================================
            // OPPORTUNITIES (Admin, Org, Company)
            // ============================================
            ['name' => 'view_opportunities', 'resource' => 'opportunities', 'action' => 'view', 'description' => 'View opportunities', 'sort_order' => 1],
            ['name' => 'create_opportunities', 'resource' => 'opportunities', 'action' => 'create', 'description' => 'Create opportunities', 'sort_order' => 2],
            ['name' => 'edit_opportunities', 'resource' => 'opportunities', 'action' => 'edit', 'description' => 'Edit opportunities', 'sort_order' => 3],
            ['name' => 'delete_opportunities', 'resource' => 'opportunities', 'action' => 'delete', 'description' => 'Delete opportunities', 'sort_order' => 4],

            // ============================================
            // CUSTOMERS (Admin, Org, Company)
            // ============================================
            ['name' => 'view_customers', 'resource' => 'customers', 'action' => 'view', 'description' => 'View customers', 'sort_order' => 1],
            ['name' => 'create_customers', 'resource' => 'customers', 'action' => 'create', 'description' => 'Create customers', 'sort_order' => 2],
            ['name' => 'edit_customers', 'resource' => 'customers', 'action' => 'edit', 'description' => 'Edit customers', 'sort_order' => 3],
            ['name' => 'delete_customers', 'resource' => 'customers', 'action' => 'delete', 'description' => 'Delete customers', 'sort_order' => 4],
            ['name' => 'export_customers', 'resource' => 'customers', 'action' => 'export', 'description' => 'Export customers', 'sort_order' => 5],

            // ============================================
            // CONTACTS (Admin, Org, Company)
            // ============================================
            ['name' => 'view_contacts', 'resource' => 'contacts', 'action' => 'view', 'description' => 'View contacts', 'sort_order' => 1],
            ['name' => 'create_contacts', 'resource' => 'contacts', 'action' => 'create', 'description' => 'Create contacts', 'sort_order' => 2],
            ['name' => 'edit_contacts', 'resource' => 'contacts', 'action' => 'edit', 'description' => 'Edit contacts', 'sort_order' => 3],
            ['name' => 'delete_contacts', 'resource' => 'contacts', 'action' => 'delete', 'description' => 'Delete contacts', 'sort_order' => 4],

            // ============================================
            // PRODUCTS (Admin, Org, Company)
            // ============================================
            ['name' => 'view_products', 'resource' => 'products', 'action' => 'view', 'description' => 'View products', 'sort_order' => 1],
            ['name' => 'create_products', 'resource' => 'products', 'action' => 'create', 'description' => 'Create products', 'sort_order' => 2],
            ['name' => 'edit_products', 'resource' => 'products', 'action' => 'edit', 'description' => 'Edit products', 'sort_order' => 3],
            ['name' => 'delete_products', 'resource' => 'products', 'action' => 'delete', 'description' => 'Delete products', 'sort_order' => 4],

            // ============================================
            // CAMPAIGNS (Admin, Org, Company)
            // ============================================
            ['name' => 'view_campaigns', 'resource' => 'campaigns', 'action' => 'view', 'description' => 'View campaigns', 'sort_order' => 1],
            ['name' => 'create_campaigns', 'resource' => 'campaigns', 'action' => 'create', 'description' => 'Create campaigns', 'sort_order' => 2],
            ['name' => 'edit_campaigns', 'resource' => 'campaigns', 'action' => 'edit', 'description' => 'Edit campaigns', 'sort_order' => 3],
            ['name' => 'delete_campaigns', 'resource' => 'campaigns', 'action' => 'delete', 'description' => 'Delete campaigns', 'sort_order' => 4],

            // ============================================
            // SALES TASKS (Admin, Org, Company)
            // ============================================
            ['name' => 'view_sales_tasks', 'resource' => 'sales_tasks', 'action' => 'view', 'description' => 'View sales tasks', 'sort_order' => 1],
            ['name' => 'create_sales_tasks', 'resource' => 'sales_tasks', 'action' => 'create', 'description' => 'Create sales tasks', 'sort_order' => 2],
            ['name' => 'edit_sales_tasks', 'resource' => 'sales_tasks', 'action' => 'edit', 'description' => 'Edit sales tasks', 'sort_order' => 3],
            ['name' => 'delete_sales_tasks', 'resource' => 'sales_tasks', 'action' => 'delete', 'description' => 'Delete sales tasks', 'sort_order' => 4],

            // ============================================
            // TIMBER INVENTORY (Admin, Org, Company)
            // ============================================
            ['name' => 'view_timber_inventory', 'resource' => 'timber_inventory', 'action' => 'view', 'description' => 'View timber stock and inventory', 'sort_order' => 1],
            ['name' => 'create_timber_inventory', 'resource' => 'timber_inventory', 'action' => 'create', 'description' => 'Create stock entries', 'sort_order' => 2],
            ['name' => 'edit_timber_inventory', 'resource' => 'timber_inventory', 'action' => 'edit', 'description' => 'Edit stock entries', 'sort_order' => 3],
            ['name' => 'delete_timber_inventory', 'resource' => 'timber_inventory', 'action' => 'delete', 'description' => 'Delete stock entries', 'sort_order' => 4],
            ['name' => 'adjust_timber_stock', 'resource' => 'timber_inventory', 'action' => 'adjust', 'description' => 'Adjust stock levels', 'sort_order' => 5],
            ['name' => 'view_timber_warehouses', 'resource' => 'timber_inventory', 'action' => 'view_warehouses', 'description' => 'View warehouses', 'sort_order' => 6],
            ['name' => 'manage_timber_warehouses', 'resource' => 'timber_inventory', 'action' => 'manage_warehouses', 'description' => 'Manage warehouses', 'sort_order' => 7],
            ['name' => 'view_timber_requisitions', 'resource' => 'timber_inventory', 'action' => 'view_requisitions', 'description' => 'View material requisitions', 'sort_order' => 8],
            ['name' => 'create_timber_requisitions', 'resource' => 'timber_inventory', 'action' => 'create_requisitions', 'description' => 'Create material requisitions', 'sort_order' => 9],
            ['name' => 'approve_timber_requisitions', 'resource' => 'timber_inventory', 'action' => 'approve_requisitions', 'description' => 'Approve material requisitions', 'sort_order' => 10],
            ['name' => 'view_timber_alerts', 'resource' => 'timber_inventory', 'action' => 'view_alerts', 'description' => 'View low stock alerts', 'sort_order' => 11],
            ['name' => 'manage_timber_wood_types', 'resource' => 'timber_inventory', 'action' => 'manage_wood_types', 'description' => 'Manage wood types', 'sort_order' => 12],

            // ============================================
            // TIMBER PURCHASES (Admin, Org, Company)
            // ============================================
            ['name' => 'view_timber_purchases', 'resource' => 'timber_purchases', 'action' => 'view', 'description' => 'View purchase orders', 'sort_order' => 1],
            ['name' => 'create_timber_purchases', 'resource' => 'timber_purchases', 'action' => 'create', 'description' => 'Create purchase orders', 'sort_order' => 2],
            ['name' => 'edit_timber_purchases', 'resource' => 'timber_purchases', 'action' => 'edit', 'description' => 'Edit purchase orders', 'sort_order' => 3],
            ['name' => 'delete_timber_purchases', 'resource' => 'timber_purchases', 'action' => 'delete', 'description' => 'Delete purchase orders', 'sort_order' => 4],
            ['name' => 'approve_timber_purchases', 'resource' => 'timber_purchases', 'action' => 'approve', 'description' => 'Approve purchase orders', 'sort_order' => 5],
            ['name' => 'receive_timber_goods', 'resource' => 'timber_purchases', 'action' => 'receive', 'description' => 'Receive goods against purchase orders', 'sort_order' => 6],
            ['name' => 'view_timber_suppliers', 'resource' => 'timber_purchases', 'action' => 'view_suppliers', 'description' => 'View suppliers', 'sort_order' => 7],
            ['name' => 'manage_timber_suppliers', 'resource' => 'timber_purchases', 'action' => 'manage_suppliers', 'description' => 'Manage suppliers', 'sort_order' => 8],

            // ============================================
            // DOCUMENTS (Admin, Org, Company)
            // ============================================
            ['name' => 'view_documents', 'resource' => 'documents', 'action' => 'view', 'description' => 'View documents', 'sort_order' => 1],
            ['name' => 'create_documents', 'resource' => 'documents', 'action' => 'create', 'description' => 'Create documents', 'sort_order' => 2],
            ['name' => 'edit_documents', 'resource' => 'documents', 'action' => 'edit', 'description' => 'Edit documents', 'sort_order' => 3],
            ['name' => 'delete_documents', 'resource' => 'documents', 'action' => 'delete', 'description' => 'Delete documents', 'sort_order' => 4],

            // ============================================
            // REPORTS (Admin, Org, Company)
            // ============================================
            ['name' => 'view_reports', 'resource' => 'reports', 'action' => 'view', 'description' => 'View reports', 'sort_order' => 1],
            ['name' => 'export_reports', 'resource' => 'reports', 'action' => 'export', 'description' => 'Export reports', 'sort_order' => 2],
            ['name' => 'view_admin_dashboard', 'resource' => 'reports', 'action' => 'view_admin_dashboard', 'description' => 'View admin dashboard', 'sort_order' => 3],

            // ============================================
            // SETTINGS (Admin, Org)
            // ============================================
            ['name' => 'view_settings', 'resource' => 'settings', 'action' => 'view', 'description' => 'View settings', 'sort_order' => 1],
            ['name' => 'edit_settings', 'resource' => 'settings', 'action' => 'edit', 'description' => 'Edit settings', 'sort_order' => 2],

            // ============================================
            // ROLE MANAGEMENT (Admin, Org)
            // ============================================
            ['name' => 'view_roles', 'resource' => 'roles', 'action' => 'view', 'description' => 'View roles', 'sort_order' => 1],
            ['name' => 'create_roles', 'resource' => 'roles', 'action' => 'create', 'description' => 'Create roles', 'sort_order' => 2],
            ['name' => 'edit_roles', 'resource' => 'roles', 'action' => 'edit', 'description' => 'Edit roles', 'sort_order' => 3],
            ['name' => 'delete_roles', 'resource' => 'roles', 'action' => 'delete', 'description' => 'Delete roles', 'sort_order' => 4],
            ['name' => 'assign_roles', 'resource' => 'roles', 'action' => 'assign', 'description' => 'Assign roles to users', 'sort_order' => 5],
            ['name' => 'view_users', 'resource' => 'roles', 'action' => 'view_users', 'description' => 'View users', 'sort_order' => 6],
            ['name' => 'edit_users', 'resource' => 'roles', 'action' => 'edit_users', 'description' => 'Edit users', 'sort_order' => 7],
        ];

        // Create all permissions with resource and action
        foreach ($permissionDefinitions as $permDef) {
            $permission = Permission::firstOrCreate(
                ['name' => $permDef['name']],
                ['guard_name' => 'web']
            );
            $permission->update([
                'resource' => $permDef['resource'],
                'action' => $permDef['action'],
                'description' => $permDef['description'],
                'sort_order' => $permDef['sort_order'],
            ]);
        }

        // ============================================
        // CREATE DEFAULT SYSTEM ROLES
        // ============================================
        $roleDefinitions = [
            [
                'name' => 'admin',
                'is_system' => true,
                'hierarchy_level' => 1,
                'description' => 'Full system access - can manage all data across all organizations and companies',
                'icon' => 'ShieldCheck',
            ],
            [
                'name' => 'org',
                'is_system' => true,
                'hierarchy_level' => 2,
                'description' => 'Organization-wide access - manages all companies under their organization',
                'icon' => 'Building',
            ],
            [
                'name' => 'company',
                'is_system' => true,
                'hierarchy_level' => 3,
                'description' => 'Company-level access - manages a single company',
                'icon' => 'Briefcase',
            ],
            [
                'name' => 'user',
                'is_system' => true,
                'hierarchy_level' => 4,
                'description' => 'Basic user - can view assigned data and perform limited actions',
                'icon' => 'User',
            ],
        ];

        foreach ($roleDefinitions as $roleDef) {
            $role = Role::firstOrCreate(
                ['name' => $roleDef['name']],
                ['guard_name' => 'web']
            );
            $role->update([
                'is_system' => $roleDef['is_system'],
                'hierarchy_level' => $roleDef['hierarchy_level'],
                'description' => $roleDef['description'],
                'icon' => $roleDef['icon'],
            ]);
        }

        // Remove old HR role if it exists
        $hrRole = Role::where('name', 'hr')->first();
        if ($hrRole) {
            $hrRole->syncPermissions([]);
            $hrRole->delete();
        }

        // ============================================
        // ASSIGN PERMISSIONS TO ROLES
        // ============================================

        // ADMIN: Full access (all permissions)
        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo(Permission::all());

        // ORG: Organization-wide access (everything except org management)
        $orgRole = Role::findByName('org');
        $orgRole->syncPermissions([
            // Companies
            'view_companies', 'create_companies', 'edit_companies', 'delete_companies',
            // CRM - Leads
            'view_leads', 'create_leads', 'edit_leads', 'delete_leads', 'export_leads',
            // CRM - Opportunities
            'view_opportunities', 'create_opportunities', 'edit_opportunities', 'delete_opportunities',
            // CRM - Customers
            'view_customers', 'create_customers', 'edit_customers', 'delete_customers', 'export_customers',
            // CRM - Contacts
            'view_contacts', 'create_contacts', 'edit_contacts', 'delete_contacts',
            // CRM - Products
            'view_products', 'create_products', 'edit_products', 'delete_products',
            // CRM - Campaigns
            'view_campaigns', 'create_campaigns', 'edit_campaigns', 'delete_campaigns',
            // CRM - Sales Tasks
            'view_sales_tasks', 'create_sales_tasks', 'edit_sales_tasks', 'delete_sales_tasks',
            // Timber Inventory
            'view_timber_inventory', 'create_timber_inventory', 'edit_timber_inventory', 'delete_timber_inventory',
            'adjust_timber_stock', 'view_timber_warehouses', 'manage_timber_warehouses',
            'view_timber_requisitions', 'create_timber_requisitions', 'approve_timber_requisitions',
            'view_timber_alerts', 'manage_timber_wood_types',
            // Timber Purchases
            'view_timber_purchases', 'create_timber_purchases', 'edit_timber_purchases', 'delete_timber_purchases',
            'approve_timber_purchases', 'receive_timber_goods',
            'view_timber_suppliers', 'manage_timber_suppliers',
            // Documents
            'view_documents', 'create_documents', 'edit_documents', 'delete_documents',
            // Reports
            'view_reports', 'export_reports', 'view_admin_dashboard',
            // Settings
            'view_settings', 'edit_settings',
            // Role management
            'view_roles', 'assign_roles', 'view_users', 'edit_users',
        ]);

        // COMPANY: Company-level access
        $companyRole = Role::findByName('company');
        $companyRole->syncPermissions([
            // Companies (view own)
            'view_companies',
            // CRM - Leads
            'view_leads', 'create_leads', 'edit_leads', 'delete_leads', 'export_leads',
            // CRM - Opportunities
            'view_opportunities', 'create_opportunities', 'edit_opportunities', 'delete_opportunities',
            // CRM - Customers
            'view_customers', 'create_customers', 'edit_customers', 'delete_customers', 'export_customers',
            // CRM - Contacts
            'view_contacts', 'create_contacts', 'edit_contacts', 'delete_contacts',
            // CRM - Products
            'view_products', 'create_products', 'edit_products', 'delete_products',
            // CRM - Campaigns
            'view_campaigns', 'create_campaigns', 'edit_campaigns',
            // CRM - Sales Tasks
            'view_sales_tasks', 'create_sales_tasks', 'edit_sales_tasks',
            // Timber Inventory
            'view_timber_inventory', 'create_timber_inventory', 'edit_timber_inventory',
            'adjust_timber_stock', 'view_timber_warehouses', 'manage_timber_warehouses',
            'view_timber_requisitions', 'create_timber_requisitions', 'approve_timber_requisitions',
            'view_timber_alerts', 'manage_timber_wood_types',
            // Timber Purchases
            'view_timber_purchases', 'create_timber_purchases', 'edit_timber_purchases',
            'approve_timber_purchases', 'receive_timber_goods',
            'view_timber_suppliers', 'manage_timber_suppliers',
            // Documents
            'view_documents', 'create_documents', 'edit_documents',
            // Reports
            'view_reports', 'export_reports',
            // Settings (view only)
            'view_settings',
            // Role management (view + assign)
            'view_roles', 'assign_roles', 'view_users',
        ]);

        // USER: Basic access
        $userRole = Role::findByName('user');
        $userRole->syncPermissions([
            // CRM (view only)
            'view_leads', 'view_opportunities', 'view_customers', 'view_contacts',
            'view_products', 'view_campaigns', 'view_sales_tasks',
            // Timber (view only)
            'view_timber_inventory', 'view_timber_warehouses',
            'view_timber_requisitions', 'create_timber_requisitions',
            'view_timber_alerts',
            'view_timber_purchases', 'view_timber_suppliers',
            // Documents (view)
            'view_documents',
        ]);

        $this->command->info('Roles, resources, and permissions seeded successfully!');
        $this->command->info('4 System Roles: admin, org, company, user');
    }
}
