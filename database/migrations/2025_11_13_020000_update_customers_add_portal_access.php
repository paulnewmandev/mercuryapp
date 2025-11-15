<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'portal_password')) {
                $table->string('portal_password', 255)->nullable()->after('address');
            }

            if (! Schema::hasColumn('customers', 'portal_password_changed_at')) {
                $table->timestamp('portal_password_changed_at')->nullable()->after('portal_password');
            }

            if (! Schema::hasColumn('customers', 'b2b_access')) {
                $table->boolean('b2b_access')->default(false)->after('portal_password_changed_at');
            }

            if (! Schema::hasColumn('customers', 'b2c_access')) {
                $table->boolean('b2c_access')->default(false)->after('b2b_access');
            }

            $table->index(['company_id', 'b2b_access', 'b2c_access'], 'customers_company_portal_access_index');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (Schema::hasColumn('customers', 'company_id') && Schema::hasColumn('customers', 'b2b_access') && Schema::hasColumn('customers', 'b2c_access')) {
                $table->dropIndex('customers_company_portal_access_index');
            }

            if (Schema::hasColumn('customers', 'portal_password')) {
                $table->dropColumn('portal_password');
            }

            if (Schema::hasColumn('customers', 'portal_password_changed_at')) {
                $table->dropColumn('portal_password_changed_at');
            }

            if (Schema::hasColumn('customers', 'b2b_access')) {
                $table->dropColumn('b2b_access');
            }

            if (Schema::hasColumn('customers', 'b2c_access')) {
                $table->dropColumn('b2c_access');
            }
        });
    }
};

