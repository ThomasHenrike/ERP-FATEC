<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Cadastros\App\Models\Customer;
use Modules\Cadastros\App\Models\Employee;
use Modules\Cadastros\App\Models\Supplier;
use Modules\Core\App\Models\Tenant;
use Modules\Estoque\App\Models\Product;
use Modules\Orcamento\App\Models\Orcamento;
use Modules\Core\App\Support\RoleLevel;
use Modules\SuperAdmin\App\Models\AdminActivityLog;
use Modules\SuperAdmin\App\Models\Invoice;
use Modules\SuperAdmin\App\Support\DataVolumeCalculator;
use Modules\Vendas\App\Models\Sale;

class CompanyController extends Controller
{
    private const TOGGLEABLE_MODULES = ['cadastros', 'estoque', 'vendas', 'orcamento'];

    public function index(): View
    {
        $tenants = Tenant::orderBy('name')->get();

        $invoiceCounts = Invoice::query()
            ->selectRaw('tenant_id, status, count(*) as total')
            ->groupBy('tenant_id', 'status')
            ->get()
            ->groupBy('tenant_id');

        $totalDbBytes = DataVolumeCalculator::totalDatabaseBytes();
        $totalStorageBytes = DataVolumeCalculator::totalStorageBytes();

        return view('superadmin::companies.index', [
            'tenants' => $tenants,
            'toggleableModules' => self::TOGGLEABLE_MODULES,
            'invoiceCounts' => $invoiceCounts,
            'totalDbBytes' => $totalDbBytes,
            'totalStorageBytes' => $totalStorageBytes,
        ]);
    }

    public function create(): View
    {
        return view('superadmin::companies.create');
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

        $tenant = DB::transaction(function () use ($validated) {
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
            ]);
            $user->email_verified_at = now();
            $user->save();

            $tenant->users()->attach($user->id, [
                'role' => 'admin',
                'is_active' => true,
            ]);

            return $tenant;
        });

        AdminActivityLog::record(
            $request->user('admin'),
            'company_created',
            $tenant,
            "Empresa \"{$tenant->name}\" criada."
        );

        return redirect()
            ->route('admin.companies.edit', $tenant)
            ->with('success', 'Empresa criada com sucesso.');
    }

    public function edit(Tenant $tenant): View
    {
        $tenant->load('modules');

        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->orderByDesc('due_date')
            ->get();

        $users = DB::table('tenant_user')
            ->join('users', 'users.id', '=', 'tenant_user.user_id')
            ->where('tenant_user.tenant_id', $tenant->id)
            ->select('users.id', 'users.name', 'users.email', 'tenant_user.role', 'tenant_user.is_active', 'tenant_user.created_at')
            ->orderBy('users.name')
            ->get();

        $usageModels = [
            'Clientes' => Customer::class,
            'Fornecedores' => Supplier::class,
            'Equipe' => Employee::class,
            'Produtos' => Product::class,
            'Vendas' => Sale::class,
            'Orcamentos' => Orcamento::class,
        ];

        $usageCounts = [];
        $usageBytes = [];

        foreach ($usageModels as $label => $modelClass) {
            $count = $modelClass::where('tenant_id', $tenant->id)->count();
            $usageCounts[$label] = $count;
            $usageBytes[$label] = DataVolumeCalculator::estimateTenantTableBytes((new $modelClass)->getTable(), $count);
        }

        $estimatedDbBytes = collect($usageBytes)->filter(fn (?int $bytes) => $bytes !== null)->sum();
        $estimatedDbBytesIsPartial = in_array(null, $usageBytes, true);

        $files = [
            'Logomarca' => $this->fileInfo($tenant->logo_path),
            'Certificado Digital' => $this->fileInfo($tenant->certificate_path),
        ];

        return view('superadmin::companies.edit', [
            'tenant' => $tenant,
            'toggleableModules' => self::TOGGLEABLE_MODULES,
            'invoices' => $invoices,
            'users' => $users,
            'usageCounts' => $usageCounts,
            'usageBytes' => $usageBytes,
            'estimatedDbBytes' => $estimatedDbBytes,
            'estimatedDbBytesIsPartial' => $estimatedDbBytesIsPartial,
            'files' => $files,
        ]);
    }

    /**
     * @return array{exists: bool, size: int|null}
     */
    private function fileInfo(?string $path): array
    {
        if (empty($path) || !Storage::disk('public')->exists($path)) {
            return ['exists' => false, 'size' => null];
        }

        return ['exists' => true, 'size' => Storage::disk('public')->size($path)];
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'document' => ['required', 'string', 'max:18', 'unique:tenants,document,' . $tenant->id],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'billing_due_day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'billing_amount' => ['nullable', 'numeric', 'min:0'],
            'max_users' => ['nullable', 'integer', 'min:1'],
        ]);

        $wasActive = $tenant->is_active;
        $validated['is_active'] = $request->boolean('is_active');

        $tenant->update($validated);

        AdminActivityLog::record(
            $request->user('admin'),
            'company_updated',
            $tenant,
            "Dados da empresa \"{$tenant->name}\" atualizados."
        );

        if ($wasActive !== $tenant->is_active) {
            AdminActivityLog::record(
                $request->user('admin'),
                $tenant->is_active ? 'company_activated' : 'company_deactivated',
                $tenant,
                'Empresa ' . ($tenant->is_active ? 'ativada' : 'desativada') . '.'
            );
        }

        return redirect()
            ->route('admin.companies.edit', $tenant)
            ->with('success', 'Dados da empresa atualizados.');
    }

    public function updateModules(Request $request, Tenant $tenant): RedirectResponse
    {
        foreach (self::TOGGLEABLE_MODULES as $module) {
            $enabled = $request->boolean($module);
            $current = $tenant->hasModule($module);

            $tenant->modules()->updateOrCreate(
                ['module' => $module],
                ['is_enabled' => $enabled]
            );

            if ($enabled !== $current) {
                AdminActivityLog::record(
                    $request->user('admin'),
                    'module_toggled',
                    $tenant,
                    "Modulo \"{$module}\" " . ($enabled ? 'ativado' : 'desativado') . " para \"{$tenant->name}\"."
                );
            }
        }

        return redirect()
            ->route('admin.companies.edit', $tenant)
            ->with('success', 'Modulos atualizados.');
    }
}
