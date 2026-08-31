<?php

declare(strict_types=1);

namespace Modules\Cadastros\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Cadastros\App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        $suppliers = [
            ['name' => 'Eletrica Distribuidora Sul Ltda', 'trade_name' => 'EletroSul', 'document' => '11222333000181', 'city' => 'Porto Alegre', 'state' => 'RS'],
            ['name' => 'Comercial Ilumina Materiais Eletricos Ltda', 'trade_name' => 'Ilumina', 'document' => '22333444000192', 'city' => 'Sao Paulo', 'state' => 'SP'],
            ['name' => 'Fios & Cia Distribuidora Ltda', 'trade_name' => 'Fios & Cia', 'document' => '33444555000103', 'city' => 'Campinas', 'state' => 'SP'],
            ['name' => 'MegaVolt Materiais Eletricos Ltda', 'trade_name' => 'MegaVolt', 'document' => '44555666000114', 'city' => 'Belo Horizonte', 'state' => 'MG'],
            ['name' => 'Conecta Eletrica Atacado Ltda', 'trade_name' => 'Conecta Eletrica', 'document' => '55666777000125', 'city' => 'Curitiba', 'state' => 'PR'],
            ['name' => 'Grupo Ampere Distribuicao Ltda', 'trade_name' => 'Grupo Ampere', 'document' => '66777888000136', 'city' => 'Rio de Janeiro', 'state' => 'RJ'],
            ['name' => 'Rede Eletrica Comercial Ltda', 'trade_name' => 'Rede Eletrica', 'document' => '77888999000147', 'city' => 'Salvador', 'state' => 'BA'],
            ['name' => 'Central de Materiais Eletricos Brasil Ltda', 'trade_name' => 'CME Brasil', 'document' => '88999000000158', 'city' => 'Recife', 'state' => 'PE'],
            ['name' => 'Voltz Distribuidora Ltda', 'trade_name' => 'Voltz', 'document' => '99000111000169', 'city' => 'Fortaleza', 'state' => 'CE'],
            ['name' => 'Eletro Prime Comercio Ltda', 'trade_name' => 'Eletro Prime', 'document' => '10111222000170', 'city' => 'Goiania', 'state' => 'GO'],
            ['name' => 'Luz & Cia Materiais Eletricos Ltda', 'trade_name' => 'Luz & Cia', 'document' => '21222333000181', 'city' => 'Florianopolis', 'state' => 'SC'],
            ['name' => 'TotalVolt Distribuidora Ltda', 'trade_name' => 'TotalVolt', 'document' => '32333444000192', 'city' => 'Brasilia', 'state' => 'DF'],
        ];

        foreach ($suppliers as $index => $data) {
            $slug = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);

            Supplier::firstOrCreate(
                ['tenant_id' => $tenantId, 'document' => $data['document']],
                [
                    'name' => $data['name'],
                    'trade_name' => $data['trade_name'],
                    'state_registration' => '11122233' . $slug,
                    'email' => 'contato@' . str_replace(' ', '', strtolower($data['trade_name'])) . '.com.br',
                    'phone' => '1130000' . $slug,
                    'whatsapp' => '1199990' . $slug,
                    'contact_name' => 'Atendimento Comercial',
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'is_active' => true,
                ]
            );
        }
    }
}
