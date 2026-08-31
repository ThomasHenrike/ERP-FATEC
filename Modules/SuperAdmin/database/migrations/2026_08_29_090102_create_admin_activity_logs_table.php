<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins');
            $table->string('action'); // company_created|module_toggled|invoice_created|invoice_marked_paid|company_activated|company_deactivated
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->text('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_logs');
    }
};
