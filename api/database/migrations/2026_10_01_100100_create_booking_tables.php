<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inquiries, offers, bookings and money (launch document, sections 02 and 07).
 * Prices and the commission are snapshotted on the booking so later tour edits don't change it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('status')->default('new')->index();
            $table->foreignId('tour_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('whatsapp')->nullable();
            $table->char('country', 2)->nullable();
            $table->text('message')->nullable();
            // Quiz answers or the assistant's conversation summary.
            $table->json('answers')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->json('utm')->nullable();
            $table->timestamps();
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('program');
            $table->date('date_from');
            $table->date('date_to');
            $table->unsignedSmallInteger('adults');
            $table->unsignedSmallInteger('children')->default(0);
            $table->unsignedInteger('total_cents');
            $table->decimal('commission_rate', 5, 2);
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            // Human code for emails and WhatsApp, e.g. TT-27-0142.
            $table->string('code', 20)->unique();
            // SHA-256 of the secret "My booking" token; the token itself is only in the email (plan, recommendation 8).
            $table->char('public_token_hash', 64)->unique();
            $table->string('status')->default('new')->index();
            $table->foreignId('tour_id')->constrained()->restrictOnDelete();
            $table->foreignId('departure_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('operator_id')->constrained()->restrictOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->unsignedSmallInteger('adults');
            $table->unsignedSmallInteger('children')->default(0);
            $table->string('pricing_source');
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('child_unit_price_cents')->nullable();
            $table->unsignedInteger('total_cents');
            $table->decimal('commission_rate', 5, 2);
            $table->unsignedInteger('deposit_cents');
            $table->unsignedInteger('balance_cents');
            $table->string('customer_name');
            $table->string('email');
            $table->string('whatsapp')->nullable();
            $table->char('country', 2)->nullable();
            $table->text('special_requests')->nullable();
            $table->timestamp('terms_accepted_at');
            $table->string('terms_version', 20);
            $table->string('terms_ip', 45)->nullable();
            $table->string('payment_link_url')->nullable();
            $table->timestamp('payment_link_expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voucher_sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            // First-visit UTM tags (launch document, section 10).
            $table->json('utm')->nullable();
            $table->timestamps();
            $table->index(['date_from', 'status']);
        });

        Schema::create('booking_travelers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_child')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('booking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            // Null for automatic transitions (timer, payment webhook).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->string('provider');
            $table->string('provider_ref')->nullable();
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3)->default('USD');
            $table->string('status')->default('pending');
            $table->json('raw')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_ref']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->string('reason');
            $table->string('provider_ref')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            // Unique per provider: a webhook delivered twice is processed once (section 07).
            $table->string('event_id');
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        foreach (['webhook_events', 'refunds', 'payments', 'booking_events', 'booking_travelers', 'bookings', 'offers', 'inquiries'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
