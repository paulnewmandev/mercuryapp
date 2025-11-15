<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PaymentCard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentCardSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $cards = [
            'Visa',
            'Mastercard',
            'American Express',
            'Diners Club',
            'Discover',
            'Maestro',
            'UnionPay',
            'JCB',
            'Elo',
            'Cabal',
        ];

        $companies = Company::query()->get();

        foreach ($companies as $company) {
            foreach ($cards as $name) {
                $paymentCard = PaymentCard::query()->firstOrNew([
                    'company_id' => $company->id,
                    'name' => $name,
                ]);
                
                if (!$paymentCard->exists) {
                    $paymentCard->id = (string) Str::uuid();
                }
                
                $paymentCard->description = sprintf('%s (%s)', $name, Str::upper($company->name ?? 'AVI'));
                $paymentCard->status = 'A';
                $paymentCard->save();
            }
        }
    }
}
