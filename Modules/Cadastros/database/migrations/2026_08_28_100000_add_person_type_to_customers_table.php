<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'person_type')) {
                $table->string('person_type', 10)->default('fisica')->after('name');
            }
        });

        // Backfill: classifica clientes ja cadastrados pela quantidade de digitos do documento.
        DB::table('customers')->select('id', 'document')->orderBy('id')->each(function ($customer): void {
            $digits = preg_replace('/\D/', '', (string) $customer->document);
            $personType = strlen($digits) === 14 ? 'juridica' : 'fisica';

            DB::table('customers')->where('id', $customer->id)->update(['person_type' => $personType]);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('person_type');
        });
    }
};
