<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('ratio_componentes', function (Blueprint $table) {
            $table->integer('sentido')->default(1)->after('orden'); // +1 ó -1
        });
    }

    public function down(): void {
        Schema::table('ratio_componentes', function (Blueprint $table) {
            $table->dropColumn('sentido');
        });
    }
};

