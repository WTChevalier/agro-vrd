<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("traducciones_contenido", function (Blueprint $table) {
            $table->id();
            $table->string("item_type", 100)->index();      // attractions, beaches, hotels, etc.
            $table->unsignedBigInteger("item_id")->index(); // FK al record original
            $table->string("locale", 8)->index();            // en, fr, it, de, pt, ru, ja, ko, zh
            $table->string("field", 80);                     // name, description, etc.
            $table->longText("content")->nullable();
            $table->boolean("auto_translated")->default(true);
            $table->timestamp("reviewed_at")->nullable();
            $table->timestamps();

            $table->unique(["item_type", "item_id", "locale", "field"], "unq_traduccion");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("traducciones_contenido");
    }
};
