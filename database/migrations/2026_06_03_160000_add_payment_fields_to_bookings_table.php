<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'payment_type')) {
                // full | deposit | bank_transfer
                $table->string('payment_type', 20)->nullable()->after('total_amount');
            }
            if (! Schema::hasColumn('bookings', 'deposit_amount')) {
                $table->decimal('deposit_amount', 10, 2)->nullable()->after('payment_type');
            }
            if (! Schema::hasColumn('bookings', 'balance_amount')) {
                $table->decimal('balance_amount', 10, 2)->nullable()->after('deposit_amount');
            }
            if (! Schema::hasColumn('bookings', 'amount_paid')) {
                $table->decimal('amount_paid', 10, 2)->default(0)->after('balance_amount');
            }
            if (! Schema::hasColumn('bookings', 'balance_due_at')) {
                $table->dateTime('balance_due_at')->nullable()->after('amount_paid');
            }
            if (! Schema::hasColumn('bookings', 'penalty_amount')) {
                $table->decimal('penalty_amount', 10, 2)->nullable()->after('balance_due_at');
            }
            if (! Schema::hasColumn('bookings', 'balance_reminder_sent_at')) {
                $table->dateTime('balance_reminder_sent_at')->nullable()->after('reminder_24h_sent_at');
            }
            if (! Schema::hasColumn('bookings', 'bank_transfer_reminder_sent_at')) {
                $table->dateTime('bank_transfer_reminder_sent_at')->nullable()->after('balance_reminder_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            foreach ([
                'payment_type', 'deposit_amount', 'balance_amount', 'amount_paid',
                'balance_due_at', 'penalty_amount', 'balance_reminder_sent_at',
                'bank_transfer_reminder_sent_at',
            ] as $col) {
                if (Schema::hasColumn('bookings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
