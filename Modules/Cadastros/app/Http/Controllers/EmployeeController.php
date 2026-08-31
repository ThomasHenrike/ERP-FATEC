<?php

declare(strict_types=1);

namespace Modules\Cadastros\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Modules\Cadastros\App\Models\Employee;
use Modules\Core\App\Models\Tenant;
use Modules\Core\App\Support\RoleLevel;

class EmployeeController extends Controller
{
    private const COMMISSION_ROLES = 'vendedor,caixa,gerente,ajudante';

    public function index(Request $request): View
    {
        $query = Employee::query()->latest('id');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('document', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $employees = $query->paginate(15)->withQueryString();

        return view('cadastros::employees.index', compact('employees'));
    }

    public function create(): View
    {
        return view('cadastros::employees.create', [
            'roleOptions' => RoleLevel::options(),
            'limitReached' => $this->limitReached(app('currentTenant')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = app('currentTenant');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', 'in:' . self::COMMISSION_ROLES],
            'commission_percentage' => ['required', 'numeric', 'between:0,100'],
            'hire_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        $validated['tenant_id'] = $tenant->id;
        $validated['is_active'] = $request->boolean('is_active', true);

        $hasSystemAccess = $request->boolean('has_system_access');

        if ($hasSystemAccess && $this->limitReached($tenant)) {
            return back()->withInput()->with('error', "Limite de {$tenant->max_users} usuarios do seu plano atingido. Fale com o suporte para aumentar.");
        }

        if ($hasSystemAccess) {
            $request->validate([
                'login_email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'login_password' => ['required', 'string', 'min:8', 'confirmed'],
                'system_role' => ['required', 'in:operator,manager,admin'],
            ]);
        }

        DB::transaction(function () use ($validated, $hasSystemAccess, $request, $tenant) {
            if ($hasSystemAccess) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $request->input('login_email'),
                    'password' => Hash::make($request->input('login_password')),
                ]);
                $user->email_verified_at = now();
                $user->save();

                $tenant->users()->attach($user->id, [
                    'role' => $request->input('system_role'),
                    'is_active' => true,
                ]);

                $validated['user_id'] = $user->id;
            }

            Employee::create($validated);
        });

        return redirect()
            ->route('cadastros.funcionarios.index')
            ->with('success', 'Funcionario cadastrado com sucesso.');
    }

    public function edit(Employee $employee): View
    {
        $pivot = $employee->user_id
            ? DB::table('tenant_user')->where('tenant_id', $employee->tenant_id)->where('user_id', $employee->user_id)->first()
            : null;

        return view('cadastros::employees.edit', [
            'employee' => $employee,
            'roleOptions' => RoleLevel::options(),
            'pivot' => $pivot,
            'isSelf' => $employee->user_id !== null && $employee->user_id === Auth::id(),
            'limitReached' => $this->limitReached(app('currentTenant')),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $tenant = app('currentTenant');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', 'in:' . self::COMMISSION_ROLES],
            'commission_percentage' => ['required', 'numeric', 'between:0,100'],
            'hire_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        // Colaborador ainda nao tem login e pediu pra conceder agora
        if (!$employee->user_id && $request->boolean('has_system_access')) {
            if ($this->limitReached($tenant)) {
                return back()->withInput()->with('error', "Limite de {$tenant->max_users} usuarios do seu plano atingido. Fale com o suporte para aumentar.");
            }

            $request->validate([
                'login_email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'login_password' => ['required', 'string', 'min:8', 'confirmed'],
                'system_role' => ['required', 'in:operator,manager,admin'],
            ]);

            DB::transaction(function () use ($request, $tenant, $employee, $validated) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $request->input('login_email'),
                    'password' => Hash::make($request->input('login_password')),
                ]);
                $user->email_verified_at = now();
                $user->save();

                $tenant->users()->attach($user->id, [
                    'role' => $request->input('system_role'),
                    'is_active' => true,
                ]);

                $validated['user_id'] = $user->id;
                $employee->update($validated);
            });

            return redirect()->route('cadastros.funcionarios.index')->with('success', 'Cadastro atualizado e acesso ao sistema criado.');
        }

        // Ja' tem login: pode trocar o papel do sistema e resetar senha
        if ($employee->user_id) {
            $isSelf = $employee->user_id === Auth::id();

            if ($request->filled('system_role') && !$isSelf) {
                $newRole = $request->input('system_role');

                if ($newRole !== 'admin' && $this->wouldRemoveLastAdmin($tenant, $employee->user_id)) {
                    return back()->withInput()->with('error', 'Nao e possivel remover o ultimo administrador da empresa.');
                }

                DB::table('tenant_user')
                    ->where('tenant_id', $tenant->id)
                    ->where('user_id', $employee->user_id)
                    ->update(['role' => $newRole]);
            }

            if ($request->filled('login_password')) {
                $request->validate(['login_password' => ['string', 'min:8', 'confirmed']]);
                User::whereKey($employee->user_id)->update(['password' => Hash::make($request->input('login_password'))]);
            }

            // Desativar a empresa tambem desativa o login (nao apaga o cadastro de RH)
            if (!$validated['is_active']) {
                if ($isSelf) {
                    return back()->withInput()->with('error', 'Voce nao pode desativar a propria conta.');
                }
                if ($this->wouldRemoveLastAdmin($tenant, $employee->user_id)) {
                    return back()->withInput()->with('error', 'Nao e possivel desativar o ultimo administrador da empresa.');
                }
                DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $employee->user_id)->update(['is_active' => false]);
            }
        }

        $employee->update($validated);

        return redirect()
            ->route('cadastros.funcionarios.index')
            ->with('success', 'Cadastro do funcionario atualizado com sucesso.');
    }

    /**
     * Revoga so o acesso ao sistema (login), mantem o cadastro de RH intacto.
     */
    public function revokeAccess(Employee $employee): RedirectResponse
    {
        $tenant = app('currentTenant');

        if (!$employee->user_id) {
            return back();
        }

        if ($employee->user_id === Auth::id()) {
            return back()->with('error', 'Voce nao pode revogar o proprio acesso.');
        }

        if ($this->wouldRemoveLastAdmin($tenant, $employee->user_id)) {
            return back()->with('error', 'Nao e possivel revogar o acesso do ultimo administrador da empresa.');
        }

        DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $employee->user_id)->update(['is_active' => false]);

        return redirect()->route('cadastros.funcionarios.edit', $employee)->with('success', 'Acesso ao sistema revogado.');
    }

    public function restoreAccess(Employee $employee): RedirectResponse
    {
        $tenant = app('currentTenant');

        if (!$employee->user_id) {
            return back();
        }

        if ($this->limitReached($tenant)) {
            return back()->with('error', "Limite de {$tenant->max_users} usuarios do seu plano atingido.");
        }

        DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $employee->user_id)->update(['is_active' => true]);

        return redirect()->route('cadastros.funcionarios.edit', $employee)->with('success', 'Acesso ao sistema reativado.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        if ($employee->user_id && $employee->user_id !== Auth::id()) {
            DB::table('tenant_user')->where('tenant_id', $employee->tenant_id)->where('user_id', $employee->user_id)->update(['is_active' => false]);
        }

        $employee->delete();

        return redirect()
            ->route('cadastros.funcionarios.index')
            ->with('success', 'Funcionario desativado com sucesso.');
    }

    private function limitReached(Tenant $tenant): bool
    {
        if ($tenant->max_users === null) {
            return false;
        }

        $activeCount = DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('is_active', true)->count();

        return $activeCount >= $tenant->max_users;
    }

    private function wouldRemoveLastAdmin(Tenant $tenant, int $excludingUserId): bool
    {
        $remainingAdmins = DB::table('tenant_user')
            ->where('tenant_id', $tenant->id)
            ->where('role', 'admin')
            ->where('is_active', true)
            ->where('user_id', '!=', $excludingUserId)
            ->count();

        return $remainingAdmins === 0;
    }
}
