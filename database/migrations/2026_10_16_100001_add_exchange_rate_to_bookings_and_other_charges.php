<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every booking keeps the SAR/USD rate that was valid when it was saved,
     * so changing the rate in Settings never changes old accounts.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('exchange_rate', 8, 4)->default(3.75)->after('price_sar');
        });

        Schema::table('other_charges', function (Blueprint $table) {
            $table->decimal('exchange_rate', 8, 4)->default(3.75)->after('price_sar');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });

        Schema::table('other_charges', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });
    }
};
