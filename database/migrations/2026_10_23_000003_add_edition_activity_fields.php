<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_phase_suphases', function (Blueprint $table) {
            $table->string('follow_up', 500)->nullable();
            $table->date('started_on')->nullable();
            $table->date('due_on')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->boolean('is_legacy')->default(false)->index();
        });
        Schema::table('catalog_subphases', fn (Blueprint $table) => $table->boolean('is_active')->default(true));
        Schema::table('manual_phase_states', fn (Blueprint $table) => $table->unsignedSmallInteger('catalog_version')->default(1));
    }

    public function down(): void
    {
        Schema::table('manual_phase_suphases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsible_user_id');
            $table->dropIndex(['is_legacy']);
            $table->dropColumn(['follow_up', 'started_on', 'due_on', 'is_legacy']);
        });
        Schema::table('catalog_subphases', fn (Blueprint $table) => $table->dropColumn('is_active'));
        Schema::table('manual_phase_states', fn (Blueprint $table) => $table->dropColumn('catalog_version'));
    }
};
