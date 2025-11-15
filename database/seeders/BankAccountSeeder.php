<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Company;
use Illuminate\Database\Seeder;

class BankAccountSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            [
                'bank_name' => 'Banco Pichincha',
                'account_number' => '220045678901',
                'account_type' => 'corriente',
            ],
            [
                'bank_name' => 'Produbanco',
                'account_number' => '334455667788',
                'account_type' => 'ahorros',
            ],
            [
                'bank_name' => 'Banco Guayaquil',
                'account_number' => '556677889900',
                'account_type' => 'corriente',
            ],
            [
                'bank_name' => 'Banco del Austro',
                'account_number' => '778899001122',
                'account_type' => 'ahorros',
            ],
        ];

        $companies = Company::query()->get();

        foreach ($companies as $company) {
            $holderName = $company->legal_name ?? $company->name ?? 'AVI';
            $aliasSuffix = $company->name ?? 'AVI';
            foreach ($banks as $bank) {
                $bankAccount = BankAccount::query()->firstOrNew([
                    'company_id' => $company->id,
                    'bank_name' => $bank['bank_name'],
                    'account_number' => $bank['account_number'],
                ]);
                
                if (!$bankAccount->exists) {
                    $bankAccount->id = (string) \Illuminate\Support\Str::uuid();
                }
                
                $bankAccount->account_type = $bank['account_type'];
                $bankAccount->account_holder_name = $holderName;
                $bankAccount->alias = sprintf('%s - %s', $bank['bank_name'], $aliasSuffix);
                $bankAccount->status = 'A';
                $bankAccount->save();
            }
        }
    }
}
