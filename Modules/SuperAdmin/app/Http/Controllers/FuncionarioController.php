<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Modules\SuperAdmin\App\Models\Admin;
use Modules\SuperAdmin\App\Models\AdminActivityLog;
use Modules\SuperAdmin\App\Support\AdminRole;

class FuncionarioController extends Controller
{
    public function index(): View
    {
        $admins = Admin::orderBy('name')->get();

        return view('superadmin::funcionarios.index', [
            'admins' => $admins,
        ]);
    }

    public function create(): View
    {
        return view('superadmin::funcionarios.create', [
            'roleOptions' => AdminRole::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'in:owner,support'],
        ]);

        $admin = Admin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        AdminActivityLog::record(
            $request->user('admin'),
            'funcionario_created',
            null,
            "Funcionario \"{$admin->name}\" ({$admin->email}) criado com papel \"" . AdminRole::label($admin->role) . '".'
        );

        return redirect()
            ->route('admin.funcionarios.index')
            ->with('success', 'Funcionario criado com sucesso.');
    }

    public function edit(Admin $admin): View
    {
        return view('superadmin::funcionarios.edit', [
            'admin' => $admin,
            'roleOptions' => AdminRole::options(),
        ]);
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email,' . $admin->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'in:owner,support'],
        ]);

        if ($admin->id === $request->user('admin')->id && $validated['role'] !== $admin->role) {
            return back()->with('error', 'Voce nao pode alterar o proprio papel.');
        }

        $wasRole = $admin->role;

        $admin->name = $validated['name'];
        $admin->email = $validated['email'];
        $admin->role = $validated['role'];

        if (!empty($validated['password'])) {
            $admin->password = Hash::make($validated['password']);
        }

        $admin->save();

        AdminActivityLog::record(
            $request->user('admin'),
            'funcionario_updated',
            null,
            "Funcionario \"{$admin->name}\" ({$admin->email}) atualizado."
        );

        if ($wasRole !== $admin->role) {
            AdminActivityLog::record(
                $request->user('admin'),
                'funcionario_role_changed',
                null,
                "Papel de \"{$admin->name}\" alterado de \"" . AdminRole::label($wasRole) . '" para "' . AdminRole::label($admin->role) . '".'
            );
        }

        return redirect()
            ->route('admin.funcionarios.index')
            ->with('success', 'Funcionario atualizado com sucesso.');
    }

    public function toggleActive(Request $request, Admin $admin): RedirectResponse
    {
        if ($admin->id === $request->user('admin')->id) {
            return back()->with('error', 'Voce nao pode desativar a propria conta.');
        }

        $admin->is_active = !$admin->is_active;
        $admin->save();

        AdminActivityLog::record(
            $request->user('admin'),
            $admin->is_active ? 'funcionario_activated' : 'funcionario_deactivated',
            null,
            "Funcionario \"{$admin->name}\" " . ($admin->is_active ? 'ativado' : 'desativado') . '.'
        );

        return redirect()
            ->route('admin.funcionarios.index')
            ->with('success', 'Funcionario ' . ($admin->is_active ? 'ativado' : 'desativado') . '.');
    }
}
