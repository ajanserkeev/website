<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts for travelers (Google sign-in on the site) and partners (operator cabinet at /partner).
 * One users table, the role decides where a user may go.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // New users are travelers unless the super admin gives them a role; "admin" was a risky default.
            $table->string('role')->default('tourist')->change();
            $table->string('password')->nullable()->change();
            $table->foreignId('operator_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar_url')->nullable()->after('google_id');
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::table('bookings', function (Blueprint $table) {
            // The traveler's account; bookings made before signing in are linked by the verified email.
            $table->foreignId('user_id')->nullable()->after('operator_id')->constrained()->nullOnDelete();
        });

        Schema::create('saved_tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'tour_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_tours');
        Schema::table('bookings', fn (Blueprint $table) => $table->dropConstrainedForeignId('user_id'));
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operator_id');
            $table->dropColumn(['google_id', 'avatar_url', 'last_login_at']);
            $table->string('role')->default('admin')->change();
        });
    }
};
