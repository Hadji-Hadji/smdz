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
        Schema::create("payments", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained("users")->onDelete("cascade");
            $table->foreignId("package_id")->constrained("packages")->onDelete("cascade");
            $table->decimal("amount", 10, 2);
            $table->string("payment_method", 50);
            $table->enum("status", ["pending_verification", "approved", "rejected"])->default("pending_verification");
            $table->string("receipt_image_path")->nullable();
            $table->text("admin_notes")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("payments");
    }
};