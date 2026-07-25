<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_categories', function (Blueprint $table) {
            $table->string('code', 50)
                ->nullable()
                ->unique()
                ->after('id');
        });

        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->text('void_reason')
                ->nullable()
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->dropColumn('void_reason');
        });

        Schema::table('finance_categories', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
