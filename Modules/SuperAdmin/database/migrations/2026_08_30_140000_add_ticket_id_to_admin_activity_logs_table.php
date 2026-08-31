<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->foreignId('ticket_id')->nullable()->after('tenant_id')->constrained('tickets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_id');
        });
    }
};
