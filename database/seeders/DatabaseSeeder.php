<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder maestro que inicializa datos mínimos para MercuryApp.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Ejecuta las siembras primarias de la base de datos.
     *
     * @return void
     */
    public function run(): void
    {
        $company = Company::query()->firstOrNew([
            'email' => 'contacto@mercuryapp.io',
        ]);
        
        if (!$company->exists) {
            $company->id = (string) Str::uuid();
        }
        
        $company->name = 'MercuryApp Demo';
        $company->legal_name = 'MercuryApp Demo S.A.';
        $company->type_tax = 'Regimen General';
        $company->number_tax = '0999999999001';
        $company->address = 'Av. Innovación 123, Quito, Ecuador';
        $company->website = 'https://mercuryapp.io';
        $company->phone_number = '+593 02 555 0000';
        $company->theme_color = '#001A35';
        $company->logo_url = '/theme-images/logo/icon-256x256.png';
        $company->digital_url = null;
        $company->digital_signature = null;
        $company->status = 'A';
        $company->status_detail = 'active';
        $company->save();

        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(BranchSeeder::class);
        $this->call(DocumentSequenceSeeder::class);
        $this->call(ProductLineSeeder::class);
        $this->call(ProductCategorySeeder::class);
        $this->call(WarehouseSeeder::class);
        $this->call(PriceListSeeder::class);
        $this->call(ReceivableCategorySeeder::class);
        $this->call(PayableCategorySeeder::class);
        $this->call(ReceivableEntrySeeder::class);
        $this->call(PayableEntrySeeder::class);
        $this->call(ProviderSeeder::class);
        $this->call(CustomerCategorySeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(CustomerCategoryAssignmentSeeder::class);
        $this->call(IncomeTypeSeeder::class);
        $this->call(ExpenseTypeSeeder::class);
        $this->call(PaymentCardSeeder::class);
        $this->call(BankAccountSeeder::class);
        $this->call(ServiceCategorySeeder::class);
        $this->call(ServiceSeeder::class);
        $this->call(IncomeSeeder::class);
        $this->call(AppleProductSeeder::class);
        $this->call(ExpenseSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(ProductTransferSeeder::class);
        $this->call(WorkshopCategorySeeder::class);
        $this->call(WorkshopStateSeeder::class);
        $this->call(WorkshopBrandModelManualSeeder::class);
        $this->call(WorkshopEquipmentSeeder::class);
        $this->call(WorkshopAccessorySeeder::class);
        $this->call(WorkshopOrderSeeder::class);
        $this->call(WorkshopOrderAdvanceSeeder::class);
        $this->call(InvoiceSeeder::class);
    }
}