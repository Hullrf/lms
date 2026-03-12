<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('slug', 220);
            $table->longText('content')->nullable();
            $table->string('video_url')->nullable();
            $table->smallInteger('video_duration')->unsigned()->nullable();
            $table->enum('type', ['video', 'text', 'quiz', 'file'])->default('video');
            $table->boolean('is_preview')->default(false);
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('lessons');
    }
};