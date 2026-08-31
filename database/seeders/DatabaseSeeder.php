<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Cadastros\Database\Seeders\CadastrosDatabaseSeeder;
use Modules\Estoque\Database\Seeders\EstoqueDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with complete multi-tenant hierarchy.
     */
    public function run(): void
    {
        // 1. Criacao da Empresa / Tenant Principal com CNPJ e UUID obrigatorios
        $tenantData = [
            'name' => 'Empresa Matriz Servicos e Comercio LTDA',
            'uuid' => (string) Str::uuid(),
            'document' => '12.345.678/0001-90',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Mapeamento defensivo de colunas adicionais caso existam na tabela tenants
        if (Schema::hasColumn('tenants', 'trade_name')) {
            $tenantData['trade_name'] = 'Matriz Materiais Eletricos';
        }

        if (Schema::hasColumn('tenants', 'slug')) {
            $tenantData['slug'] = 'empresa-matriz';
        }

        if (Schema::hasColumn('tenants', 'email')) {
            $tenantData['email'] = 'contato@empresamatriz.local';
        }

        if (Schema::hasColumn('tenants', 'phone')) {
            $tenantData['phone'] = '11999998888';
        }

        if (Schema::hasColumn('tenants', 'is_active')) {
            $tenantData['is_active'] = true;
        }

        DB::table('tenants')->updateOrInsert(
            ['id' => 1],
            $tenantData
        );

        // 2. Criacao do Usuario Administrador Padrao
        $user = User::firstOrCreate(
            ['email' => 'admin@erp.local'],
            [
                'name' => 'Administrador do Sistema',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        // Vinculo direto na tabela users se a coluna tenant_id existir
        if (Schema::hasColumn('users', 'tenant_id')) {
            $user->update(['tenant_id' => 1]);
        }

        // Vinculo na tabela pivo de usuarios por empresa (tenant_user)
        if (Schema::hasTable('tenant_user')) {
            DB::table('tenant_user')->updateOrInsert(
                ['tenant_id' => 1, 'user_id' => $user->id],
                [
                    'role' => 'admin',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 3. Execucao dos Seeders do Modulo de Estoque (Categorias, Produtos e Kardex)
        $this->call(EstoqueDatabaseSeeder::class);

        // 4. Execucao dos Seeders do Modulo de Cadastros (Clientes, Fornecedores e Equipe)
        $this->call(CadastrosDatabaseSeeder::class);

        // 5. Uma empresa + login de teste por integrante do grupo
        $this->call(GroupMembersSeeder::class);
    }
}