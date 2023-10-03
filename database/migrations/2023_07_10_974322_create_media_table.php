<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            $table->morphs('model');
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->boolean('is_folder')->default(false);
            $table->foreignId('media_id')->nullable()->constrained('media')->cascadeOnDelete();
            $table->string('mime_type')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('disk')->default(config('filesystems.default'));
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->json('manipulations')->nullable();
            $table->json('custom_properties')->nullable();
            $table->uuid()->nullable()->unique();
            $table->json('generated_conversions')->nullable();
            $table->json('responsive_images')->nullable();
            $table->unsignedInteger('order_column')->nullable()->index();

            $table->nullableTimestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
