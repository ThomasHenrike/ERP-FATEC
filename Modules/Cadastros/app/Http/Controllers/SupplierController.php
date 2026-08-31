<?php

declare(strict_types=1);

namespace Modules\Cadastros\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Cadastros\App\Models\Supplier;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $query = Supplier::query()->latest('id');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('document', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->paginate(15)->withQueryString();

        return view('cadastros::suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('cadastros::suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = app('currentTenant')->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:20'],
            'state_registration' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'is_active' => ['boolean'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['is_active'] = $request->boolean('is_active', true);

        Supplier::create($validated);

        return redirect()
            ->route('cadastros.fornecedores.index')
            ->with('success', 'Fornecedor cadastrado com sucesso.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('cadastros::suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:20'],
            'state_registration' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $supplier->update($validated);

        return redirect()
            ->route('cadastros.fornecedores.index')
            ->with('success', 'Cadastro do fornecedor atualizado com sucesso.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()
            ->route('cadastros.fornecedores.index')
            ->with('success', 'Fornecedor desativado com sucesso.');
    }
}