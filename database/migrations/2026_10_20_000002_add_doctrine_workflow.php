<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctrinal_maps', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });
        Schema::table('manual_types', function (Blueprint $table) {
            $table->foreignId('doctrinal_map_id')->nullable()->constrained('doctrinal_maps')->restrictOnDelete();
        });
        Schema::table('manual_phases', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->unsignedSmallInteger('position')->default(0);
        });
        Schema::table('catalog_subphases', function (Blueprint $table) {
            $table->string('activity_key')->nullable()->unique();
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedTinyInteger('advance_number')->nullable();
        });
        Schema::table('manuals', function (Blueprint $table) {
            $table->unsignedTinyInteger('research_advances')->default(2);
            $table->unsignedTinyInteger('experimentation_advances')->default(2);
            $table->date('process_started_on')->nullable();
            $table->date('research_started_on')->nullable();
            $table->date('published_on')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
        });
        Schema::create('manual_phase_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manual_phase_id')->constrained('manual_phases')->restrictOnDelete();
            $table->date('started_on')->nullable();
            $table->timestamps();
            $table->unique(['manual_id', 'manual_phase_id']);
        });
        Schema::create('manual_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
        Schema::table('manual_phase_suphases', function (Blueprint $table) {
            $table->unique(['manuals_id', 'catalog_subphases_id'], 'manual_activity_unique');
        });
        Schema::table('manual_committee_members', function (Blueprint $table) {
            $table->unique(['manuals_id', 'committee_type_id', 'committee_members_id'], 'manual_member_type_unique');
        });
        Schema::table('manual_military_units', function (Blueprint $table) {
            $table->unique(['manuals_id', 'committee_type_id', 'military_units_id'], 'manual_unit_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('manual_military_units', fn (Blueprint $table) => $table->dropUnique('manual_unit_type_unique'));
        Schema::table('manual_committee_members', fn (Blueprint $table) => $table->dropUnique('manual_member_type_unique'));
        Schema::table('manual_phase_suphases', fn (Blueprint $table) => $table->dropUnique('manual_activity_unique'));
        Schema::dropIfExists('manual_events');
        Schema::dropIfExists('manual_phase_states');
        Schema::table('manuals', fn (Blueprint $table) => $table->dropColumn(['research_advances', 'experimentation_advances', 'process_started_on', 'research_started_on', 'published_on', 'lock_version']));
        Schema::table('catalog_subphases', fn (Blueprint $table) => $table->dropColumn(['activity_key', 'position', 'advance_number']));
        Schema::table('manual_phases', fn (Blueprint $table) => $table->dropColumn(['slug', 'position']));
        Schema::table('manual_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('doctrinal_map_id');
        });
        Schema::dropIfExists('doctrinal_maps');
    }
};
