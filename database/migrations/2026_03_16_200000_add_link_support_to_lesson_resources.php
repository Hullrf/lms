<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('lesson_resources', function (Blueprint $table) {
            $table->enum('type', ['file', 'link'])->default('file')->after('name');
            $table->string('url')->nullable()->after('type');
        });
    }
    public function down(): void {
        Schema::table('lesson_resources', function (Blueprint $table) {
            $table->dropColumn(['type', 'url']);
        });
    }
};
