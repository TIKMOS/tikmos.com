<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('reviewable');
            $table->string('author_name');
            $table->string('author_city')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->string('source')->default('manual');
            $table->string('status')->default('pending');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'is_featured']);
            $table->unique(['user_id', 'reviewable_type', 'reviewable_id'], 'reviews_user_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
