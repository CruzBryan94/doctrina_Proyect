<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false);
        });

        Schema::create('account_access_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_user_id');
            $table->unsignedBigInteger('target_user_id');
            $table->string('action', 40);
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['target_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        throw new LogicException('Account access audit history is retained; this migration cannot be rolled back automatically.');
    }
};
