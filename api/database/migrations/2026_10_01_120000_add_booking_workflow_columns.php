<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Later emails (payment link, voucher, review) need the "My booking" link, so the token is also kept
            // encrypted with APP_KEY. Lookups still go through public_token_hash.
            $table->text('public_token_encrypted')->nullable()->after('public_token_hash');
            // Each automatic email is sent once (step 4.9).
            $table->timestamp('payment_reminder_sent_at')->nullable()->after('payment_link_expires_at');
            $table->timestamp('pre_trip_reminder_sent_at')->nullable()->after('voucher_sent_at');
            $table->timestamp('review_requested_at')->nullable()->after('pre_trip_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['public_token_encrypted', 'payment_reminder_sent_at', 'pre_trip_reminder_sent_at', 'review_requested_at']);
        });
    }
};
