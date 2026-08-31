<?php

declare(strict_types=1);

namespace Modules\Cadastros\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Cadastros\App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        $customers = [
            // Pessoa Juridica
            ['person_type' => 'juridica', 'name' => 'Instalacoes Eletricas Rio Ltda', 'trade_name' => 'Eletro Rio', 'document' => '12345671000101', 'default_price_table' => 'wholesale', 'credit_limit' => 8000, 'city' => 'Rio de Janeiro', 'state' => 'RJ'],
            ['person_type' => 'juridica', 'name' => 'Condominio Edificio Central', 'trade_name' => null, 'document' => '23456782000112', 'default_price_table' => 'resale', 'credit_limit' => 3000, 'city' => 'Sao Paulo', 'state' => 'SP'],
            ['person_type' => 'juridica', 'name' => 'Construtora Horizonte Ltda', 'trade_name' => 'Horizonte Construcoes', 'document' => '34567893000123', 'default_price_table' => 'wholesale', 'credit_limit' => 15000, 'city' => 'Campinas', 'state' => 'SP'],
            ['person_type' => 'juridica', 'name' => 'Manutencao Predial ABC Ltda', 'trade_name' => 'ABC Manutencao', 'document' => '45678904000134', 'default_price_table' => 'resale', 'credit_limit' => 5000, 'city' => 'Belo Horizonte', 'state' => 'MG'],
            ['person_type' => 'juridica', 'name' => 'Incorporadora Nova Era Ltda', 'trade_name' => 'Nova Era', 'document' => '56789015000145', 'default_price_table' => 'wholesale', 'credit_limit' => 20000, 'city' => 'Curitiba', 'state' => 'PR'],
            ['person_type' => 'juridica', 'name' => 'Servicos Eletricos do Vale Ltda', 'trade_name' => 'Eletro Vale', 'document' => '67890126000156', 'default_price_table' => 'resale', 'credit_limit' => 4000, 'city' => 'Porto Alegre', 'state' => 'RS'],

            // Pessoa Fisica
            ['person_type' => 'fisica', 'name' => 'Carlos Alberto Silva', 'document' => '10120230405', 'default_price_table' => 'retail', 'credit_limit' => 500, 'city' => 'Sao Paulo', 'state' => 'SP'],
            ['person_type' => 'fisica', 'name' => 'Fernanda Souza Lima', 'document' => '20230340506', 'default_price_table' => 'retail', 'credit_limit' => 500, 'city' => 'Campinas', 'state' => 'SP'],
            ['person_type' => 'fisica', 'name' => 'Roberto Santos Almeida', 'document' => '30340450607', 'default_price_table' => 'resale', 'credit_limit' => 1500, 'city' => 'Rio de Janeiro', 'state' => 'RJ'],
            ['person_type' => 'fisica', 'name' => 'Juliana Pereira Costa', 'document' => '40450560708', 'default_price_table' => 'retail', 'credit_limit' => 500, 'city' => 'Belo Horizonte', 'state' => 'MG'],
            ['person_type' => 'fisica', 'name' => 'Marcos Vinicius Oliveira', 'document' => '50560670809', 'default_price_table' => 'resale', 'credit_limit' => 1200, 'city' => 'Curitiba', 'state' => 'PR'],
            ['person_type' => 'fisica', 'name' => 'Patricia Gomes Ferreira', 'document' => '60670780910', 'default_price_table' => 'retail', 'credit_limit' => 500, 'city' => 'Porto Alegre', 'state' => 'RS'],
        ];

        foreach ($customers as $index => $data) {
            $slug = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);

            Customer::firstOrCreate(
                ['tenant_id' => $tenantId, 'document' => $data['document']],
                [
                    'person_type' => $data['person_type'],
                    'name' => $data['name'],
                    'trade_name' => $data['trade_name'] ?? null,
                    'email' => 'cliente' . $slug . '@exemplo.com.br',
                    'phone' => '1130001' . $slug,
                    'whatsapp' => '1199991' . $slug,
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'credit_limit' => $data['credit_limit'],
                    'default_price_table' => $data['default_price_table'],
                    'is_active' => true,
                ]
            );
        }
    }
}
