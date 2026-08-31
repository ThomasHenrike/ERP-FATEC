<?php

declare(strict_types=1);

namespace Modules\Cadastros\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Cadastros\App\Models\Employee;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        $employees = [
            ['name' => 'Joao Silva', 'role' => 'gerente', 'commission_percentage' => 0],
            ['name' => 'Maria Santos', 'role' => 'vendedor', 'commission_percentage' => 3.5],
            ['name' => 'Pedro Costa', 'role' => 'caixa', 'commission_percentage' => 0],
            ['name' => 'Ana Oliveira', 'role' => 'vendedor', 'commission_percentage' => 3.5],
            ['name' => 'Lucas Pereira', 'role' => 'ajudante', 'commission_percentage' => 2.0],
            ['name' => 'Beatriz Almeida', 'role' => 'ajudante', 'commission_percentage' => 2.0],
            ['name' => 'Rafael Souza', 'role' => 'ajudante', 'commission_percentage' => 0],
            ['name' => 'Camila Rodrigues', 'role' => 'vendedor', 'commission_percentage' => 3.5],
            ['name' => 'Bruno Martins', 'role' => 'caixa', 'commission_percentage' => 0],
            ['name' => 'Larissa Ferreira', 'role' => 'gerente', 'commission_percentage' => 0],
            ['name' => 'Diego Carvalho', 'role' => 'ajudante', 'commission_percentage' => 2.0],
            ['name' => 'Fernanda Lima', 'role' => 'vendedor', 'commission_percentage' => 3.5],
        ];

        foreach ($employees as $index => $data) {
            $slug = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);

            Employee::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $data['name']],
                [
                    'document' => '700' . $slug . '0405060',
                    'email' => 'equipe' . $slug . '@exemplo.com.br',
                    'phone' => '1130002' . $slug,
                    'whatsapp' => '1199992' . $slug,
                    'role' => $data['role'],
                    'commission_percentage' => $data['commission_percentage'],
                    'hire_date' => now()->subMonths(random_int(1, 36))->format('Y-m-d'),
                    'is_active' => true,
                ]
            );
        }
    }
}
