<?php

declare(strict_types=1);

namespace Modules\Estoque\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\App\Models\Tenant;
use Modules\Estoque\App\Models\Category;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantWithUser(string $document, string $role = 'admin'): array
    {
        $tenant = Tenant::create([
            'name' => "Empresa {$document}",
            'document' => $document,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => $role, 'is_active' => true]);

        return [$tenant, $user];
    }

    public function test_operator_cannot_reach_categories_screen(): void
    {
        [, $operator] = $this->createTenantWithUser('11111111000191', 'operator');

        $response = $this->actingAs($operator)->get(route('estoque.categorias.index'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_manager_can_create_a_category_and_slug_is_derived_from_name(): void
    {
        [, $manager] = $this->createTenantWithUser('22222222000191', 'manager');

        $response = $this->actingAs($manager)->post(route('estoque.categorias.store'), ['name' => 'Materiais Eletricos']);

        $response->assertRedirect(route('estoque.categorias.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Materiais Eletricos', 'slug' => 'materiais-eletricos']);
    }

    public function test_manager_can_create_a_subcategory_linked_to_a_parent(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('33333333000191', 'manager');
        $parent = Category::create(['tenant_id' => $tenant->id, 'name' => 'Fios e Cabos', 'slug' => 'fios-e-cabos', 'is_active' => true]);

        $response = $this->actingAs($manager)->post(route('estoque.categorias.store'), [
            'name' => 'Cabo Flexivel',
            'parent_id' => $parent->id,
        ]);

        $response->assertRedirect(route('estoque.categorias.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Cabo Flexivel', 'parent_id' => $parent->id]);
    }

    public function test_user_from_one_tenant_cannot_edit_category_from_another_tenant(): void
    {
        [, $managerA] = $this->createTenantWithUser('44444444000191', 'manager');
        [$tenantB] = $this->createTenantWithUser('55555555000191', 'manager');

        $categoryB = Category::create(['tenant_id' => $tenantB->id, 'name' => 'Categoria B', 'slug' => 'categoria-b', 'is_active' => true]);

        $response = $this->actingAs($managerA)->get(route('estoque.categorias.edit', $categoryB));

        $response->assertNotFound();
    }
}
