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
        Schema::create("apartments", function (Blueprint $table) {
            $table->id();
            $table->foreignId("landlord_id")->constrained("users")->onDelete("cascade");
            $table->string("title");
            $table->text("description");
            $table->string("wilaya");
            $table->string("municipality");
            $table->decimal("price", 10, 2);
            $table->enum("price_unit", ["day", "week", "month"]);
            $table->integer("rooms");
            $table->integer("bathrooms");
            $table->decimal("area", 10, 2);
            $table->boolean("is_active")->default(true);
            $table->boolean("is_featured")->default(false);
            $table->enum("status", ["pending", "approved", "rejected"])->default("pending");
            $table->integer("views_count")->default(0);
            $table->integer("phone_clicks")->default(0);
            $table->json("amenities")->nullable(); // Stores array of strings as JSON
            $table->json("images")->nullable();    // Stores array of image URLs as JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("apartments");
    }
};