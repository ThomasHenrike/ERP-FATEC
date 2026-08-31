<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\App\Models\Tenant;

/**
 * Uma empresa + login de teste pra cada integrante do grupo poder acessar
 * o sistema com a propria conta, sem depender do admin@erp.local padrao.
 */
class GroupMembersSeeder extends Seeder
{
    private const MEMBROS = [
        ['empresa' => 'Empresa Teste - Thiago', 'nome' => 'Thiago', 'email' => 'thiago@teste.erp', 'senha' => 'thiago123', 'documento' => '11111111000101'],
        ['empresa' => 'Empresa Teste - Matheus Felipe', 'nome' => 'Matheus', 'email' => 'matheusfelipe@teste.erp', 'senha' => 'matheus123', 'documento' => '22222222000102'],
        ['empresa' => 'Empresa Teste - Matheus Henrique', 'nome' => 'Matheus', 'email' => 'matheushenrique@teste.erp', 'senha' => 'matheus123', 'documento' => '33333333000103'],
        ['empresa' => 'Empresa Teste - Leonardo', 'nome' => 'Leonardo', 'email' => 'leonardo@teste.erp', 'senha' => 'leonardo123', 'documento' => '44444444000104'],
    ];

    public function run(): void
    {
        foreach (self::MEMBROS as $membro) {
            $tenant = Tenant::firstOrCreate(
                ['document' => $membro['documento']],
                ['name' => $membro['empresa'], 'is_active' => true]
            );

            $user = User::updateOrCreate(
                ['email' => $membro['email']],
                ['name' => $membro['nome'], 'password' => Hash::make($membro['senha'])]
            );

            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
                $user->save();
            }

            $tenant->users()->syncWithoutDetaching([$user->id => ['role' => 'admin', 'is_active' => true]]);
        }
    }
}
