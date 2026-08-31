<?php

declare(strict_types=1);

namespace Modules\Estoque\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Estoque\App\Models\Category;
use Modules\Estoque\App\Models\Product;
use Modules\Estoque\App\Services\StockMovementService;

class ProductSeeder extends Seeder
{
    public function run(StockMovementService $stockService): void
    {
        $tenantId = 1;

        $categoryBySlug = Category::where('tenant_id', $tenantId)
            ->get(['id', 'slug'])
            ->keyBy('slug');

        $items = [
            [
                'name' => 'Cabo Flexivel 2,5mm² 750V (Rolo 100m)',
                'category_id' => $categoryBySlug->get('fios-e-cabos')?->id,
                'sku' => 'CABO-25-100M',
                'barcode' => '7896001000011',
                'unit' => 'RL',
                'cost_price' => 145.00,
                'margin_retail' => 45.00, 'price_retail' => 210.25,
                'margin_resale' => 25.00, 'price_resale' => 181.25,
                'margin_wholesale' => 14.00, 'price_wholesale' => 165.30,
                'min_stock' => 10.000, 'initial_stock' => 35.000,
                'ncm' => '85444900', 'cest' => '0104100', 'origin' => 0,
            ],
            [
                'name' => 'Cabo Flexivel 4,0mm² 750V (Rolo 100m)',
                'category_id' => $categoryBySlug->get('fios-e-cabos')?->id,
                'sku' => 'CABO-40-100M',
                'barcode' => '7896001000028',
                'unit' => 'RL',
                'cost_price' => 220.00,
                'margin_retail' => 42.00, 'price_retail' => 312.40,
                'margin_resale' => 24.00, 'price_resale' => 272.80,
                'margin_wholesale' => 13.00, 'price_wholesale' => 248.60,
                'min_stock' => 8.000, 'initial_stock' => 22.000,
                'ncm' => '85444900', 'cest' => '0104100', 'origin' => 0,
            ],
            [
                'name' => 'Disjuntor Bipolar DIN 40A',
                'category_id' => $categoryBySlug->get('disjuntores-quadros')?->id,
                'sku' => 'DISJ-BIP-40A',
                'barcode' => '7896001000035',
                'unit' => 'UN',
                'cost_price' => 22.00,
                'margin_retail' => 55.00, 'price_retail' => 34.10,
                'margin_resale' => 30.00, 'price_resale' => 28.60,
                'margin_wholesale' => 16.00, 'price_wholesale' => 25.52,
                'min_stock' => 15.000, 'initial_stock' => 60.000,
                'ncm' => '85362000', 'cest' => '0900200', 'origin' => 0,
            ],
            [
                'name' => 'Disjuntor Monopolar DIN 20A',
                'category_id' => $categoryBySlug->get('disjuntores-quadros')?->id,
                'sku' => 'DISJ-MONO-20A',
                'barcode' => '7896001000042',
                'unit' => 'UN',
                'cost_price' => 12.00,
                'margin_retail' => 55.00, 'price_retail' => 18.60,
                'margin_resale' => 30.00, 'price_resale' => 15.60,
                'margin_wholesale' => 16.00, 'price_wholesale' => 13.92,
                'min_stock' => 20.000, 'initial_stock' => 4.000, // Nivel critico (abaixo do minimo)
                'ncm' => '85362000', 'cest' => '0900200', 'origin' => 0,
            ],
            [
                'name' => 'Quadro de Distribuicao 12 Disjuntores de Embutir',
                'category_id' => $categoryBySlug->get('disjuntores-quadros')?->id,
                'sku' => 'QDR-DIST-12',
                'barcode' => '7896001000059',
                'unit' => 'UN',
                'cost_price' => 68.00,
                'margin_retail' => 48.00, 'price_retail' => 100.64,
                'margin_resale' => 26.00, 'price_resale' => 85.68,
                'margin_wholesale' => 14.00, 'price_wholesale' => 77.52,
                'min_stock' => 5.000, 'initial_stock' => 14.000,
                'ncm' => '85371090', 'cest' => null, 'origin' => 0,
            ],
            [
                'name' => 'Tomada 2P+T 10A Branca',
                'category_id' => $categoryBySlug->get('tomadas-interruptores')?->id,
                'sku' => 'TOM-2PT-10A',
                'barcode' => '7896001000066',
                'unit' => 'UN',
                'cost_price' => 6.50,
                'margin_retail' => 60.00, 'price_retail' => 10.40,
                'margin_resale' => 32.00, 'price_resale' => 8.58,
                'margin_wholesale' => 18.00, 'price_wholesale' => 7.67,
                'min_stock' => 30.000, 'initial_stock' => 80.000,
                'ncm' => '85366900', 'cest' => '0900700', 'origin' => 0,
            ],
            [
                'name' => 'Interruptor Simples 10A Branco',
                'category_id' => $categoryBySlug->get('tomadas-interruptores')?->id,
                'sku' => 'INT-SIMP-10A',
                'barcode' => '7896001000073',
                'unit' => 'UN',
                'cost_price' => 5.80,
                'margin_retail' => 60.00, 'price_retail' => 9.28,
                'margin_resale' => 32.00, 'price_resale' => 7.66,
                'margin_wholesale' => 18.00, 'price_wholesale' => 6.84,
                'min_stock' => 30.000, 'initial_stock' => 75.000,
                'ncm' => '85365090', 'cest' => '0900700', 'origin' => 0,
            ],
            [
                'name' => 'Lampada LED Bulbo 9W 6500K',
                'category_id' => $categoryBySlug->get('iluminacao-lampadas')?->id,
                'sku' => 'LED-BULBO-9W',
                'barcode' => '7896001000080',
                'unit' => 'UN',
                'cost_price' => 6.20,
                'margin_retail' => 70.00, 'price_retail' => 10.54,
                'margin_resale' => 38.00, 'price_resale' => 8.56,
                'margin_wholesale' => 20.00, 'price_wholesale' => 7.44,
                'min_stock' => 40.000, 'initial_stock' => 0.000, // Item zerado para teste de semaforo
                'ncm' => '85395000', 'cest' => '1301700', 'origin' => 0,
            ],
            [
                'name' => 'Luminaria LED Painel de Embutir 24W',
                'category_id' => $categoryBySlug->get('iluminacao-lampadas')?->id,
                'sku' => 'LED-PAINEL-24W',
                'barcode' => '7896001000097',
                'unit' => 'UN',
                'cost_price' => 38.00,
                'margin_retail' => 50.00, 'price_retail' => 57.00,
                'margin_resale' => 28.00, 'price_resale' => 48.64,
                'margin_wholesale' => 15.00, 'price_wholesale' => 43.70,
                'min_stock' => 12.000, 'initial_stock' => 30.000,
                'ncm' => '85395000', 'cest' => '1301700', 'origin' => 0,
            ],
            [
                'name' => 'Eletroduto PVC Rigido 3/4" (Barra 3m)',
                'category_id' => $categoryBySlug->get('eletrodutos-conexoes')?->id,
                'sku' => 'ELDT-PVC-34',
                'barcode' => '7896001000103',
                'unit' => 'PC',
                'cost_price' => 9.50,
                'margin_retail' => 42.00, 'price_retail' => 13.49,
                'margin_resale' => 24.00, 'price_resale' => 11.78,
                'margin_wholesale' => 13.00, 'price_wholesale' => 10.74,
                'min_stock' => 25.000, 'initial_stock' => 50.000,
                'ncm' => '39172300', 'cest' => null, 'origin' => 0,
            ],
            [
                'name' => 'Fita Isolante 19mm x 20m',
                'category_id' => $categoryBySlug->get('fitas-isolantes')?->id,
                'sku' => 'FITA-ISOL-19',
                'barcode' => '7896001000110',
                'unit' => 'UN',
                'cost_price' => 4.20,
                'margin_retail' => 65.00, 'price_retail' => 6.93,
                'margin_resale' => 35.00, 'price_resale' => 5.67,
                'margin_wholesale' => 20.00, 'price_wholesale' => 5.04,
                'min_stock' => 40.000, 'initial_stock' => 90.000,
                'ncm' => '39191000', 'cest' => null, 'origin' => 0,
            ],
            [
                'name' => 'Conector Wago 3 Vias (Pacote com 10un)',
                'category_id' => $categoryBySlug->get('conectores-terminais')?->id,
                'sku' => 'WAGO-3V-PCT10',
                'barcode' => '7896001000127',
                'unit' => 'PCT',
                'cost_price' => 18.00,
                'margin_retail' => 48.00, 'price_retail' => 26.64,
                'margin_resale' => 26.00, 'price_resale' => 22.68,
                'margin_wholesale' => 14.00, 'price_wholesale' => 20.52,
                'min_stock' => 10.000, 'initial_stock' => 26.000,
                'ncm' => '85369090', 'cest' => null, 'origin' => 0,
            ],
            [
                'name' => 'Alicate Amperimetro Digital',
                'category_id' => $categoryBySlug->get('ferramentas-eletricas')?->id,
                'sku' => 'ALIC-AMP-DIG',
                'barcode' => '7896001000134',
                'unit' => 'UN',
                'cost_price' => 95.00,
                'margin_retail' => 45.00, 'price_retail' => 137.75,
                'margin_resale' => 25.00, 'price_resale' => 118.75,
                'margin_wholesale' => 14.00, 'price_wholesale' => 108.30,
                'min_stock' => 3.000, 'initial_stock' => 8.000,
                'ncm' => '90303390', 'cest' => null, 'origin' => 0,
            ],
            [
                'name' => 'Capacete de Seguranca Classe B',
                'category_id' => $categoryBySlug->get('epi')?->id,
                'sku' => 'EPI-CAPACETE-B',
                'barcode' => '7896001000141',
                'unit' => 'UN',
                'cost_price' => 21.00,
                'margin_retail' => 50.00, 'price_retail' => 31.50,
                'margin_resale' => 28.00, 'price_resale' => 26.88,
                'margin_wholesale' => 15.00, 'price_wholesale' => 24.15,
                'min_stock' => 10.000, 'initial_stock' => 20.000,
                'ncm' => '65061000', 'cest' => null, 'origin' => 0,
            ],
            [
                'name' => 'Sensor de Presenca para Iluminacao Bivolt',
                'category_id' => $categoryBySlug->get('automacao-sensores')?->id,
                'sku' => 'SENSOR-PRES-BIV',
                'barcode' => '7896001000158',
                'unit' => 'UN',
                'cost_price' => 32.00,
                'margin_retail' => 48.00, 'price_retail' => 47.36,
                'margin_resale' => 26.00, 'price_resale' => 40.32,
                'margin_wholesale' => 14.00, 'price_wholesale' => 36.48,
                'min_stock' => 8.000, 'initial_stock' => 16.000,
                'ncm' => '85365090', 'cest' => null, 'origin' => 0,
            ],
        ];

        foreach ($items as $data) {
            $initialStock = $data['initial_stock'];
            unset($data['initial_stock']);
            $data['tenant_id'] = $tenantId;
            $data['cfop_default'] = $data['cfop_default'] ?? '5102';
            $data['csosn'] = $data['csosn'] ?? '102';
            $data['cst_pis'] = $data['cst_pis'] ?? '07';
            $data['cst_cofins'] = $data['cst_cofins'] ?? '07';

            $product = Product::firstOrCreate(
                ['tenant_id' => $tenantId, 'sku' => $data['sku']],
                $data
            );

            if ($initialStock > 0 && $product->wasRecentlyCreated) {
                $stockService->recordEntry(
                    tenantId: $tenantId,
                    productId: $product->id,
                    quantity: (float) $initialStock,
                    unitPrice: (float) $product->cost_price,
                    userId: 1,
                    referenceType: null,
                    referenceId: null,
                    notes: 'Carga inicial de estoque via Seeder.'
                );
            }
        }
    }
}
