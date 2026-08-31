<?php

declare(strict_types=1);

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Modules\Core\App\Models\Tenant;

class TenantRegistrationController extends Controller
{
    public function create(): View
    {
        return view('core::auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'document' => ['required', 'string', 'max:18', 'unique:tenants,document'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($validated, &$user, &$tenant) {
            $tenant = Tenant::create([
                'name' => $validated['company_name'],
                'trade_name' => $validated['trade_name'] ?? null,
                'document' => $validated['document'],
                'is_active' => true,
            ]);

            $user = User::create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ]);

            $tenant->users()->attach($user->id, [
                'role' => 'admin',
                'is_active' => true,
            ]);

            session(['active_tenant_id' => $tenant->id]);
        });

        Auth::login($user);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Empresa criada com sucesso! Bem-vindo ao ERP Modular.');
    }
}