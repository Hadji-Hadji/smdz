<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // إضافة الحقل الجديد مع القيمة الافتراضية unverified
            $table->enum('verification_status', ['unverified', 'pending', 'verified', 'rejected'])
                  ->default('unverified')
                  ->after('type');
        });

        // نقل البيانات الحالية من is_verified إلى verification_status
        // true -> verified
        // false -> unverified
        DB::table('users')->where('is_verified', true)->update(['verification_status' => 'verified']);
        DB::table('users')->where('is_verified', false)->update(['verification_status' => 'unverified']);

        Schema::table('users', function (Blueprint $table) {
            // إزالة الحقل القديم
            $table->dropColumn('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('type');
        });

        // استعادة البيانات
        DB::table('users')->where('verification_status', 'verified')->update(['is_verified' => true]);
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('verification_status');
        });
    }
};