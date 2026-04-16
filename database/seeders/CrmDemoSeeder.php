<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerBankDetail;
use App\Models\CustomerContactDetail;
use App\Models\CustomerContactEmail;
use App\Models\CustomerContactPhone;
use App\Models\CustomerGroup;
use App\Models\IndustryType;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\OpportunityType;
use App\Models\Organization;
// use App\Models\PaymentTerm;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\Project;
use App\Models\Prospect;
use App\Models\Source;
use App\Models\Status;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Database\Seeder;

class CrmDemoSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::first();
        $company = Company::first();

        if (!$org || !$company) {
            $this->command->warn('No organization or company found. Skipping CRM demo data.');
            return;
        }

        $admin = User::where('email', 'admin@crm.local')->first();
        $salesManager = User::where('email', 'sales@crm.local')->first();
        $salesRep = User::where('email', 'rep@crm.local')->first();

        // ============================================
        // Territories
        // ============================================
        $territories = [
            ['territory_name' => 'North India'],
            ['territory_name' => 'South India'],
            ['territory_name' => 'West India'],
            ['territory_name' => 'East India'],
            ['territory_name' => 'Central India'],
        ];

        $createdTerritories = [];
        foreach ($territories as $t) {
            $createdTerritories[] = Territory::firstOrCreate(
                ['territory_name' => $t['territory_name']],
                $t
            );
        }

        // ============================================
        // Customer Groups
        // ============================================
        $groups = [
            ['name' => 'Retail'],
            ['name' => 'Wholesale'],
            ['name' => 'Corporate'],
            ['name' => 'Government'],
        ];

        $createdGroups = [];
        foreach ($groups as $g) {
            $createdGroups[] = CustomerGroup::firstOrCreate(['name' => $g['name']], $g);
        }

        // ============================================
        // Payment Terms
        // ============================================
        // $paymentTerms = [
        //     ['name' => 'Net 15', 'days' => 15],
        //     ['name' => 'Net 30', 'days' => 30],
        //     ['name' => 'Net 45', 'days' => 45],
        //     ['name' => 'Advance', 'days' => 0],
        //     ['name' => '50% Advance', 'days' => 15],
        // ];

        // $createdPaymentTerms = [];
        // foreach ($paymentTerms as $pt) {
        //     $createdPaymentTerms[] = PaymentTerm::firstOrCreate(['name' => $pt['name']], $pt);
        // }

        // ============================================
        // Price Lists
        // ============================================
        $priceLists = [
            ['name' => 'Standard Retail', 'currency' => 'INR'],
            ['name' => 'Wholesale Discount', 'currency' => 'INR'],
            ['name' => 'Corporate Rate', 'currency' => 'INR'],
        ];

        foreach ($priceLists as $pl) {
            PriceList::firstOrCreate(['name' => $pl['name']], $pl);
        }

        // ============================================
        // Projects & Products
        // ============================================
        $projects = [
            ['name' => 'Timber - Raw', 'description' => 'Raw timber logs and planks'],
            ['name' => 'Timber - Processed', 'description' => 'Processed and treated wood'],
            ['name' => 'Plywood & Boards', 'description' => 'Plywood, MDF, and particle boards'],
            ['name' => 'Furniture', 'description' => 'Ready-made furniture items'],
            ['name' => 'Services', 'description' => 'Cutting, treatment, and delivery services'],
        ];

        $createdProjects = [];
        foreach ($projects as $p) {
            $createdProjects[] = Project::firstOrCreate(['name' => $p['name']], $p);
        }

        $products = [
            [ 'name' => 'Teak Log (Grade A)', 'description' => 'Premium quality teak logs'],
            [ 'name' => 'Sal Wood Plank', 'description' => 'Durable sal wood planks'],
            [ 'name' => 'Sheesham Log', 'description' => 'Indian rosewood logs'],
            [ 'name' => 'Treated Pine Beam', 'description' => 'Pressure-treated pine beams'],
            [ 'name' => 'Polished Deodar Panel', 'description' => 'Polished Himalayan cedar panels'],
            [ 'name' => 'Commercial Plywood 8x4', 'description' => 'Standard commercial grade plywood sheets'],
            [ 'name' => 'Marine Plywood 8x4', 'description' => 'Water-resistant marine plywood'],
            [ 'name' => 'MDF Board 6mm', 'description' => 'Medium density fibreboard'],
            [ 'name' => 'Teak Dining Table Set', 'description' => '6-seater dining table with chairs'],
            [ 'name' => 'Sheesham Bookshelf', 'description' => 'Solid wood bookshelf unit'],
            [ 'name' => 'Custom Cutting Service', 'description' => 'Per-piece custom cutting and sizing'],
            [ 'name' => 'Wood Treatment Service', 'description' => 'Anti-termite and weather treatment'],
        ];

        foreach ($products as $p) {
            Product::firstOrCreate(
    ['name' => $p['name']],
    [
        'description' => $p['description'],
    ]
);
        }

        // ============================================
        // Contacts (stored in customer_contacts table)
        // ============================================
        $contacts = [
            ['salutation' => 'Mr', 'first_name' => 'Rajesh', 'last_name' => 'Mehta', 'designation' => 'Procurement Manager', 'company_name' => 'Mehta Constructions', 'gender' => 'male', 'phone' => '+91-9876501234', 'email' => 'rajesh.mehta@mehtaconstructions.in'],
            ['salutation' => 'Ms', 'first_name' => 'Priya', 'last_name' => 'Sharma', 'designation' => 'Purchase Head', 'company_name' => 'Sharma Interiors', 'gender' => 'female', 'phone' => '+91-9876502345', 'email' => 'priya@sharmainteriors.com'],
            ['salutation' => 'Mr', 'first_name' => 'Amit', 'last_name' => 'Gupta', 'designation' => 'CEO', 'company_name' => 'Gupta Wood Works', 'gender' => 'male', 'phone' => '+91-9876503456', 'email' => 'amit@guptawoodworks.com'],
            ['salutation' => 'Mrs', 'first_name' => 'Sunita', 'last_name' => 'Patel', 'designation' => 'Director', 'company_name' => 'Patel Furniture House', 'gender' => 'female', 'phone' => '+91-9876504567', 'email' => 'sunita@patelfurniture.in'],
            ['salutation' => 'Mr', 'first_name' => 'Vikram', 'last_name' => 'Reddy', 'designation' => 'Project Manager', 'company_name' => 'Reddy Builders', 'gender' => 'male', 'phone' => '+91-9876505678', 'email' => 'vikram@reddybuilders.co.in'],
            ['salutation' => 'Mr', 'first_name' => 'Suresh', 'last_name' => 'Agarwal', 'designation' => 'Owner', 'company_name' => 'Agarwal Traders', 'gender' => 'male', 'phone' => '+91-9876506789', 'email' => 'suresh@agarwaltraders.com'],
        ];

        $createdContacts = [];
        foreach ($contacts as $c) {
            $phone = $c['phone'];
            $email = $c['email'];
            unset($c['phone'], $c['email']);

            // $contact = Contact::firstOrCreate(
            //     ['first_name' => $c['first_name'], 'last_name' => $c['last_name']],
            //     array_merge($c, ['status' => 'active'])
            // );

            // CustomerContactPhone::firstOrCreate(
            //     ['contact_id' => $contact->id, 'phone_no' => $phone],
            //     ['contact_id' => $contact->id, 'phone_no' => $phone, 'is_primary' => true]
            // );

            // CustomerContactEmail::firstOrCreate(
            //     ['contact_id' => $contact->id, 'email' => $email],
            //     ['contact_id' => $contact->id, 'email' => $email, 'is_primary' => true]
            // );

            // $createdContacts[] = $contact;
        }

        // ============================================
        // Customers
        // ============================================
        $industryIT = IndustryType::first();

        $customers = [
            ['name' => 'Mehta Constructions Pvt Ltd', 'customer_type' => 'Company', 'group_idx' => 2, 'territory_idx' => 0, 'contact_idx' => 0, 'email' => 'info@mehtaconstructions.in', 'phone' => '+91-172-4567890'],
            ['name' => 'Sharma Interior Solutions', 'customer_type' => 'Company', 'group_idx' => 0, 'territory_idx' => 0, 'contact_idx' => 1, 'email' => 'contact@sharmainteriors.com', 'phone' => '+91-11-23456789'],
            ['name' => 'Gupta Wood Works', 'customer_type' => 'Company', 'group_idx' => 1, 'territory_idx' => 2, 'contact_idx' => 2, 'email' => 'sales@guptawoodworks.com', 'phone' => '+91-79-34567890'],
            ['name' => 'Patel Furniture House', 'customer_type' => 'Company', 'group_idx' => 0, 'territory_idx' => 2, 'contact_idx' => 3, 'email' => 'info@patelfurniture.in', 'phone' => '+91-265-4567890'],
            ['name' => 'Reddy Builders & Developers', 'customer_type' => 'Company', 'group_idx' => 2, 'territory_idx' => 1, 'contact_idx' => 4, 'email' => 'projects@reddybuilders.co.in', 'phone' => '+91-80-45678901'],
            ['name' => 'Agarwal Timber Traders', 'customer_type' => 'Company', 'group_idx' => 1, 'territory_idx' => 4, 'contact_idx' => 5, 'email' => 'trade@agarwaltraders.com', 'phone' => '+91-731-5678901'],
        ];

        $createdCustomers = [];
        foreach ($customers as $c) {
            $customer = Customer::create([
                    'name' => $c['name'],
                    'customer_type' => $c['customer_type'],
                    'customer_group_id' => $createdGroups[$c['group_idx']]->id,
                ]);
            
            // Create contact detail for the customer
            $customer->contactDetails()->create([
                'personal_email' => $c['email'],
                'phone_no' => $c['phone'],
                'org_id' => $customer->org_id,
                'company_id' => $customer->company_id,
            ]);

            $createdCustomers[] = $customer;
        }

        // ============================================
        // Leads
        // ============================================
        $sources = Source::all();
        $statuses = Status::all();

        $leads = [
            ['first_name' => 'Rajan', 'last_name' => 'Kapoor', 'email' => 'rajan.kapoor@kapoorgroup.com', 'phone' => '+91-9812345001', 'company_name' => 'Kapoor Group', 'city' => 'Delhi', 'state' => 'Delhi', 'country' => 'India', 'qualification_status' => 'Unqualified'],
            ['first_name' => 'Neha', 'last_name' => 'Singh', 'email' => 'neha.singh@singhbuilders.in', 'phone' => '+91-9812345002', 'company_name' => 'Singh Builders', 'city' => 'Lucknow', 'state' => 'Uttar Pradesh', 'country' => 'India', 'qualification_status' => 'In Progress'],
            ['first_name' => 'Arun', 'last_name' => 'Jain', 'email' => 'arun@jaininteriors.com', 'phone' => '+91-9812345003', 'company_name' => 'Jain Interiors', 'city' => 'Jaipur', 'state' => 'Rajasthan', 'country' => 'India', 'qualification_status' => 'Qualified'],
            ['first_name' => 'Deepak', 'last_name' => 'Verma', 'email' => 'deepak@vermafurniture.co.in', 'phone' => '+91-9812345004', 'company_name' => 'Verma Furniture', 'city' => 'Chandigarh', 'state' => 'Punjab', 'country' => 'India', 'qualification_status' => 'Unqualified'],
            ['first_name' => 'Kavita', 'last_name' => 'Nair', 'email' => 'kavita@nairwood.com', 'phone' => '+91-9812345005', 'company_name' => 'Nair Woodcraft', 'city' => 'Kochi', 'state' => 'Kerala', 'country' => 'India', 'qualification_status' => 'In Progress'],
            ['first_name' => 'Manish', 'last_name' => 'Tiwari', 'email' => 'manish@tiwariconstruction.in', 'phone' => '+91-9812345006', 'company_name' => 'Tiwari Construction', 'city' => 'Bhopal', 'state' => 'Madhya Pradesh', 'country' => 'India', 'qualification_status' => 'Qualified'],
            ['first_name' => 'Anita', 'last_name' => 'Desai', 'email' => 'anita@desaiproperties.com', 'phone' => '+91-9812345007', 'company_name' => 'Desai Properties', 'city' => 'Pune', 'state' => 'Maharashtra', 'country' => 'India', 'qualification_status' => 'In Progress'],
            ['first_name' => 'Sanjay', 'last_name' => 'Rao', 'email' => 'sanjay@raoenterprises.co.in', 'phone' => '+91-9812345008', 'company_name' => 'Rao Enterprises', 'city' => 'Bangalore', 'state' => 'Karnataka', 'country' => 'India', 'qualification_status' => 'Unqualified'],
        ];

        foreach ($leads as $l) {
            Lead::firstOrCreate(
                ['email' => $l['email']],
                array_merge($l, [
                    'salutation' => 'Mr',
                    'source_id' => $sources->isNotEmpty() ? $sources->random()->id : null,
                    'status_id' => $statuses->isNotEmpty() ? $statuses->random()->id : null,
                    'industry_id' => $industryIT?->id,
                    'annual_revenue' => rand(500000, 50000000),
                ])
            );
        }

        // ============================================
        // Prospects
        // ============================================
        $prospects = [
            ['company_name' => 'Kapoor Group of Companies', 'industry' => 'Construction', 'territory' => 'North India', 'annual_revenue' => 25000000, 'no_of_employees' => 150, 'city' => 'Delhi', 'state' => 'Delhi', 'country' => 'India', 'email' => 'info@kapoorgroup.com', 'phone' => '+91-11-45678900'],
            ['company_name' => 'Singh Builders & Developers', 'industry' => 'Real Estate', 'territory' => 'Central India', 'annual_revenue' => 15000000, 'no_of_employees' => 80, 'city' => 'Lucknow', 'state' => 'UP', 'country' => 'India', 'email' => 'contact@singhbuilders.in', 'phone' => '+91-522-3456789'],
            ['company_name' => 'Desai Properties & Construction', 'industry' => 'Real Estate', 'territory' => 'West India', 'annual_revenue' => 35000000, 'no_of_employees' => 200, 'city' => 'Pune', 'state' => 'Maharashtra', 'country' => 'India', 'email' => 'info@desaiproperties.com', 'phone' => '+91-20-56789012'],
        ];

        foreach ($prospects as $p) {
            Prospect::firstOrCreate(
                ['company_name' => $p['company_name']],
                array_merge($p, [
                    'prospect_owner_id' => $salesManager?->id ?? $admin?->id,
                    'status' => 'active',
                    'source' => 'Referral',
                ])
            );
        }

        // ============================================
        // Opportunities
        // ============================================
        $stages = OpportunityStage::all();
        $oppTypes = OpportunityType::all();

        $opportunities = [
            ['name' => 'Mehta Constructions - Bulk Teak Order', 'customer_idx' => 0, 'amount' => 750000, 'probability' => 80, 'stage' => 'Negotiation'],
            ['name' => 'Sharma Interiors - Office Renovation', 'customer_idx' => 1, 'amount' => 320000, 'probability' => 60, 'stage' => 'Proposal'],
            ['name' => 'Reddy Builders - Housing Project Supply', 'customer_idx' => 4, 'amount' => 1500000, 'probability' => 45, 'stage' => 'Qualification'],
            ['name' => 'Patel Furniture - Monthly Wholesale', 'customer_idx' => 3, 'amount' => 180000, 'probability' => 90, 'stage' => 'Closed Won'],
            ['name' => 'Agarwal Traders - Plywood Bulk Deal', 'customer_idx' => 5, 'amount' => 420000, 'probability' => 70, 'stage' => 'Negotiation'],
            ['name' => 'Gupta Wood Works - Custom Order', 'customer_idx' => 2, 'amount' => 250000, 'probability' => 55, 'stage' => 'Proposal'],
        ];

        foreach ($opportunities as $o) {
            $stage = $stages->where('name', $o['stage'])->first();
            $customer = $createdCustomers[$o['customer_idx']];

            Opportunity::firstOrCreate(
                ['name' => $o['name']],
                [
                    'name' => $o['name'],
                    'opportunity_from' => 'Customer',
                    'customer_id' => $customer->id,
                    'party_name' => $customer->name,
                    'opportunity_amount' => $o['amount'],
                    'probability' => $o['probability'],
                    'opportunity_stage_id' => $stage?->id,
                    'opportunity_type_id' => $oppTypes->isNotEmpty() ? $oppTypes->first()->id : null,
                    'source_id' => $sources->isNotEmpty() ? $sources->random()->id : null,
                    'status_id' => $statuses->isNotEmpty() ? $statuses->random()->id : null,
                    'expected_closing' => now()->addDays(rand(7, 60)),
                    'opportunity_owner' => $salesManager?->id ?? $admin?->id,
                    'currency' => 'INR',
                    'city' => 'Chandigarh',
                    'country' => 'India',
                ]
            );
        }

        // ============================================
        // Campaigns
        // ============================================
        $campaigns = [
            ['name' => 'Q1 2026 Timber Sale', 'campaign_code' => 'CAMP-Q1-2026'],
            ['name' => 'Monsoon Clearance Offer', 'campaign_code' => 'CAMP-MONSOON'],
            ['name' => 'New Customer Welcome', 'campaign_code' => 'CAMP-WELCOME'],
            ['name' => 'Diwali Festival Discount', 'campaign_code' => 'CAMP-DIWALI'],
        ];

        foreach ($campaigns as $c) {
            Campaign::firstOrCreate(['name' => $c['name']], $c);
        }

        $this->command->info('CRM demo data seeded successfully!');
        $this->command->info('  - ' . count($territories) . ' territories');
        $this->command->info('  - ' . count($groups) . ' customer groups');
        // $this->command->info('  - ' . count($paymentTerms) . ' payment terms');
        $this->command->info('  - ' . count($products) . ' products');
        $this->command->info('  - ' . count($contacts) . ' contacts');
        $this->command->info('  - ' . count($customers) . ' customers');
        $this->command->info('  - ' . count($leads) . ' leads');
        $this->command->info('  - ' . count($prospects) . ' prospects');
        $this->command->info('  - ' . count($opportunities) . ' opportunities');
        $this->command->info('  - ' . count($campaigns) . ' campaigns');
    }
}
