<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CustomerCategoryAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()
            ->select(['id'])
            ->chunkById(100, function (Collection $companies): void {
                foreach ($companies as $company) {
                    $categories = CustomerCategory::query()
                        ->where('company_id', $company->id)
                        ->where('status', 'A')
                        ->orderBy('name')
                        ->get(['id', 'name']);

                    if ($categories->isEmpty()) {
                        continue;
                    }

                    $defaultCategory = $categories->firstWhere('name', 'Cliente normal') ?? $categories->first();
                    if (! $defaultCategory) {
                        continue;
                    }

                    Customer::query()
                        ->where('company_id', $company->id)
                        ->whereNull('category_id')
                        ->update(['category_id' => $defaultCategory->id]);
                }
            });
    }
}

