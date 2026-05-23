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
        Schema::table('apartments', function (Blueprint $table) {
            // إضافة حقول الإحداثيات الجغرافية بدقة عالية بعد حقل البلدية (municipality)
            $table->decimal('latitude', 10, 8)->after('municipality');
            $table->decimal('longitude', 11, 8)->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            // حذف الحقول في حال التراجع عن الـ Migration
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};