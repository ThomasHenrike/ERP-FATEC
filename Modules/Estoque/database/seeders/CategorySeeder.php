<?php

declare(strict_types=1);

namespace Modules\Estoque\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Estoque\App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        $categories = [
            ['name' => 'Fios e Cabos', 'slug' => 'fios-e-cabos'],
            ['name' => 'Disjuntores e Quadros de Distribuicao', 'slug' => 'disjuntores-quadros'],
            ['name' => 'Tomadas e Interruptores', 'slug' => 'tomadas-interruptores'],
            ['name' => 'Iluminacao e Lampadas', 'slug' => 'iluminacao-lampadas'],
            ['name' => 'Eletrodutos e Conexoes', 'slug' => 'eletrodutos-conexoes'],
            ['name' => 'Ferramentas Eletricas', 'slug' => 'ferramentas-eletricas'],
            ['name' => 'Fitas e Isolantes', 'slug' => 'fitas-isolantes'],
            ['name' => 'Conectores e Terminais', 'slug' => 'conectores-terminais'],
            ['name' => 'Automacao e Sensores', 'slug' => 'automacao-sensores'],
            ['name' => 'Equipamentos de Protecao (EPI)', 'slug' => 'epi'],
            ['name' => 'Motores e Acionamentos', 'slug' => 'motores-acionamentos'],
            ['name' => 'Caixas e Suportes', 'slug' => 'caixas-suportes'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $cat['name']],
                ['slug' => $cat['slug'], 'is_active' => true]
            );
        }
    }
}
