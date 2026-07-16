<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('sonicpesa_account_id')->nullable()->after('payment_gateway')->constrained('sonic_pesa_accounts')->nullOnDelete();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('sonicpesa_account_id')->nullable()->after('gateway')->constrained('sonic_pesa_accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sonicpesa_account_id');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sonicpesa_account_id');
        });
    }
};
