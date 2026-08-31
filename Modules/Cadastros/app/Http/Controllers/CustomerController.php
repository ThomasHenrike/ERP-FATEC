<?php

declare(strict_types=1);

namespace Modules\Cadastros\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Cadastros\App\Models\Customer;

class CustomerController extends Controller
{
    /**
     * CPF exige 11 digitos e CNPJ 14, conforme o tipo de pessoa selecionado.
     */
    private function documentDigitsRule(Request $request): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($request): void {
            if (blank($value)) {
                return;
            }

            $digits = preg_replace('/\D/', '', (string) $value);
            $expected = $request->input('person_type') === 'juridica' ? 14 : 11;
            $label = $expected === 14 ? 'CNPJ' : 'CPF';

            if (strlen($digits) !== $expected) {
                $fail("Informe um {$label} valido com {$expected} digitos.");
            }
        };
    }

    public function index(Request $request): View
    {
        $query = Customer::query()->latest('id');

        // Filtro de Status: Ativos por padrao, Inativos ou Todos
        $status = $request->input('status', 'active');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('document', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(15)->withQueryString();

        $activeCount = Customer::where('is_active', true)->count();
        $inactiveCount = Customer::where('is_active', false)->count();

        return view('cadastros::customers.index', compact('customers', 'activeCount', 'inactiveCount'));
    }

    public function create(): View
    {
        return view('cadastros::customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = app('currentTenant')->id;

        $validated = $request->validate([
            'person_type' => ['required', 'string', 'in:fisica,juridica'],
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:20', $this->documentDigitsRule($request)],
            'state_registration' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'default_price_table' => ['required', 'string', 'in:retail,resale,wholesale'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'ibge_city_code' => ['nullable', 'string', 'max:7'],
            'is_active' => ['boolean'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['is_active'] = $request->boolean('is_active', true);

        Customer::create($validated);

        return redirect()
            ->route('cadastros.clientes.index')
            ->with('success', 'Cliente cadastrado com sucesso.');
    }

    public function edit(Customer $customer): View
    {
        return view('cadastros::customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'person_type' => ['required', 'string', 'in:fisica,juridica'],
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:20', $this->documentDigitsRule($request)],
            'state_registration' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'default_price_table' => ['required', 'string', 'in:retail,resale,wholesale'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'ibge_city_code' => ['nullable', 'string', 'max:7'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $customer->update($validated);

        return redirect()
            ->route('cadastros.clientes.index')
            ->with('success', 'Cadastro do cliente atualizado com sucesso.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()
            ->route('cadastros.clientes.index')
            ->with('success', 'Cliente desativado com sucesso.');
    }
}