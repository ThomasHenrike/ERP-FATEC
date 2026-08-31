<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Identidade Visual e Contato
            $table->string('logo_path')->nullable()->after('trade_name');
            $table->string('whatsapp', 20)->nullable()->after('phone');
            $table->string('zip_code', 10)->nullable()->after('whatsapp');
            $table->string('street')->nullable()->after('zip_code');
            $table->string('number', 20)->nullable()->after('street');
            $table->string('neighborhood')->nullable()->after('number');
            $table->string('city', 100)->nullable()->after('neighborhood');
            $table->string('state', 2)->nullable()->after('city');

            // Parametrizacao de Impressao
            $table->string('print_format', 20)->default('a4')->after('state'); // a4, thermal_80, thermal_58
            $table->string('print_behavior', 20)->default('ask')->after('print_format'); // ask, auto, none
            $table->text('receipt_footer')->nullable()->after('print_behavior');

            // Parametrizacao Fiscal e Certificado A1
            $table->string('certificate_path')->nullable()->after('receipt_footer');
            $table->text('certificate_password')->nullable()->after('certificate_path');
            $table->dateTime('certificate_expires_at')->nullable()->after('certificate_password');
            $table->string('sefaz_environment', 20)->default('homologation')->after('certificate_expires_at'); // homologation, production

            // Politicas Comerciais do PDV
            $table->boolean('allow_negative_stock')->default(true)->after('sefaz_environment');
            $table->decimal('max_discount_percentage', 5, 2)->default(10.00)->after('allow_negative_stock');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path', 'whatsapp', 'zip_code', 'street', 'number',
                'neighborhood', 'city', 'state', 'print_format', 'print_behavior',
                'receipt_footer', 'certificate_path', 'certificate_password',
                'certificate_expires_at', 'sefaz_environment', 'allow_negative_stock',
                'max_discount_percentage'
            ]);
        });
    }
};