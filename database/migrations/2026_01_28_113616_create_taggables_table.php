<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignUuid('tag_id')->constrained('tags')->cascadeOnDelete();
            // $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();

            $table->uuidMorphs('taggable'); // taggable_id + taggable_type

            $table->unsignedBigInteger('status')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // one primary key only
            $table->primary(['tag_id', 'taggable_id', 'taggable_type']);

            // (optional but good) for queries like "all tags for product"
            $table->index(['taggable_id', 'taggable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
