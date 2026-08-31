<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Cadastros\App\Models\Customer;
use Modules\Core\App\Models\Tenant;
use Modules\SuperAdmin\App\Models\Admin;
use Modules\SuperAdmin\App\Support\DataVolumeCalculator;
use Tests\TestCase;

/**
 * Volume de dados (KB/MB/GB) na tela de Empresas do Admin Supremo - ajuda a
 * planejar upgrade de hospedagem (Modules\SuperAdmin\App\Support\DataVolumeCalculator).
 */
class DataVolumeTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): Admin
    {
        $admin = Admin::create([
            'name' => 'Admin Teste',
            'email' => 'admin-teste@erp.local',
            'password' => Hash::make('secret123'),
            'role' => 'owner',
        ]);

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_companies_index_shows_total_system_volume(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('admin.companies.index'));

        $response->assertOk();
        $response->assertSee('Volume Total do Sistema');
        $response->assertSee('Total Ocupado');
    }

    public function test_company_edit_shows_estimated_data_volume_per_module(): void
    {
        $this->actingAsAdmin();

        $tenant = Tenant::create([
            'name' => 'Empresa Volume Teste',
            'document' => '11111111000191',
            'is_active' => true,
        ]);

        Customer::create(['tenant_id' => $tenant->id, 'name' => 'Cliente 1']);
        Customer::create(['tenant_id' => $tenant->id, 'name' => 'Cliente 2']);

        $response = $this->get(route('admin.companies.edit', $tenant));

        $response->assertOk();
        $response->assertSee('Volume de Dados');
        $response->assertSee('2'); // contagem de clientes
    }

    public function test_humanize_formats_bytes_into_the_right_unit(): void
    {
        $this->assertSame('-', DataVolumeCalculator::humanize(null));
        $this->assertSame('500 B', DataVolumeCalculator::humanize(500));
        $this->assertSame('2,0 KB', DataVolumeCalculator::humanize(2048));
        $this->assertSame('3,0 MB', DataVolumeCalculator::humanize(3 * 1024 * 1024));
        $this->assertSame('1,50 GB', DataVolumeCalculator::humanize((int) (1.5 * 1024 * 1024 * 1024)));
    }
}
