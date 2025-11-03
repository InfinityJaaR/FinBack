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
        Schema::table('ratios_definiciones', function (Blueprint $table) {
            $table->decimal('multiplicador', 15, 6)->default(1.0)->after('categoria');
            $table->boolean('is_protected')->default(false)->after('multiplicador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ratios_definiciones', function (Blueprint $table) {
            $table->dropColumn(['multiplicador', 'is_protected']);
        });
    }
};
