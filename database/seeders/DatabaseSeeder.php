<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(100)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            WarehouseSeeder::class
        ]);

        $branchUser = User::firstOrCreate(
            ['email' => 'soban@soban.com'],
            ['name' => 'soban', 'password' => Hash::make('soban')]
        );
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            ['name' => 'admin', 'password' => Hash::make('admin')]
        );

        $permissions = [
            'Products',
            'Discount Products',
            'Category',
            'Sub Category',
            'Brands',
            'List Inwards',
            'Create Inward Gatepass',
            'Purchase',
            'Purchase Return',
            'Vendor',
            'List Warehouse',
            'Warehouse Stock',
            'Stock Transfer',
            'Sales',
            'Sale Return',
            'Bookings',
            'Customer',
            'Sales Officer',
            'Zone',
            'Char Of Accounts',
            'Narrations',
            'Receipts Voucher',
            'Payment Voucher',
            'Expense Voucher',
            'Item Stock Report',
            'Purchase Report',
            'Sale Report',
            'Customer Ledger',
            'Vendor Ledger',
            'System Reports'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
        
        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $branchRole = Role::firstOrCreate(['name' => 'branch']);

        // Assign all permissions to admin role
        $adminRole->syncPermissions($permissions);
        $branchRole->syncPermissions($permissions);

        // Optional: Assign role to admin user
        $adminUser = User::where('email', 'admin@admin.com')->first();

        if ($adminUser) {
            $adminUser->assignRole($adminRole);
        }
        if ($branchUser) {
            $branchUser->assignRole($branchRole);
        }
    }
}
