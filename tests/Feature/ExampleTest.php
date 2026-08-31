<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A raiz "/" e o dashboard, protegido por auth: visitante anonimo deve
     * ser redirecionado para o login em vez de ver a pagina diretamente.
     */
    public function test_guest_is_redirected_to_login_from_root(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
