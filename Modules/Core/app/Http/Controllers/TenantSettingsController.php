<?php

declare(strict_types=1);

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Core\App\Models\Tenant;

class TenantSettingsController extends Controller
{
    public function edit(): View
    {
        /** @var Tenant $tenant */
        $tenant = app('currentTenant');

        return view('core::settings.index', compact('tenant'));
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('currentTenant');

        $validated = $request->validate([
            // Dados Cadastrais
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'document' => ['required', 'string', 'max:20'],
            'state_registration' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'ibge_city_code' => ['nullable', 'string', 'max:7'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            // Uploads de Arquivo
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'certificate_file' => ['nullable', 'file', 'max:5120'],
            'certificate_password' => ['nullable', 'string', 'max:255'],

            // Impressao
            'print_format' => ['required', 'string', 'in:a4,thermal_80,thermal_58'],
            'print_behavior' => ['required', 'string', 'in:ask,auto,none'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],

            // Fiscal
            'sefaz_environment' => ['required', 'string', 'in:homologation,production'],
            'crt' => ['required', 'integer', 'in:1,2,3'],
            'fiscal_default_observation' => ['nullable', 'string', 'max:1000'],

            // Regras PDV
            'allow_negative_stock' => ['boolean'],
            'max_discount_percentage' => ['required', 'numeric', 'between:0,100'],
            'require_seller_on_pos' => ['boolean'],
            'allow_price_edit_pos' => ['boolean'],

            // Personalizacao de Orcamentos
            'quote_show_ncm' => ['boolean'],
            'quote_show_icms' => ['boolean'],
            'quote_show_icms_st' => ['boolean'],
            'quote_show_lead_time' => ['boolean'],
            'quote_show_payment_condition' => ['boolean'],
            'quote_show_delivery_term' => ['boolean'],
            'quote_show_freight' => ['boolean'],

            'quote_default_payment_condition' => ['nullable', 'string', 'max:255'],
            'quote_default_delivery_term' => ['nullable', 'string', 'max:255'],
            'quote_default_freight_info' => ['nullable', 'string', 'max:255'],
            'quote_default_lead_time_days' => ['nullable', 'integer', 'min:0'],
            'quote_default_validity_days' => ['required', 'integer', 'min:0'],
        ]);

        // 1. Processamento e upload da Logomarca
        if ($request->hasFile('logo')) {
            if ($tenant->logo_path && Storage::disk('public')->exists($tenant->logo_path)) {
                Storage::disk('public')->delete($tenant->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store("tenants/{$tenant->id}", 'public');
        }

        // 2. Processamento e upload do Certificado Digital A1
        if ($request->hasFile('certificate_file')) {
            $pfxContent = file_get_contents($request->file('certificate_file')->getRealPath());
            $password = $request->input('certificate_password', '');

            $certData = [];
            if (openssl_pkcs12_read($pfxContent, $certData, $password)) {
                $certInfo = openssl_x509_parse($certData['cert']);
                if (isset($certInfo['validTo_time_t'])) {
                    $validated['certificate_expires_at'] = date('Y-m-d H:i:s', $certInfo['validTo_time_t']);
                }
            }

            $validated['certificate_path'] = $request->file('certificate_file')->store("tenants/{$tenant->id}/certs");
        }

        // 3. Remocao obrigatoria de chaves transitorias que nao sao colunas no banco
        unset($validated['logo']);
        unset($validated['certificate_file']);

        if (empty($validated['certificate_password'])) {
            unset($validated['certificate_password']);
        }

        $validated['allow_negative_stock'] = $request->boolean('allow_negative_stock');

        foreach (['quote_show_ncm', 'quote_show_icms', 'quote_show_icms_st', 'quote_show_lead_time', 'quote_show_payment_condition', 'quote_show_delivery_term', 'quote_show_freight'] as $field) {
            $validated[$field] = $request->boolean($field);
        }

        // Tratamento seguro para require_seller_on_pos
        if (Schema::hasColumn('tenants', 'require_seller_on_pos')) {
            $validated['require_seller_on_pos'] = $request->boolean('require_seller_on_pos');
        } else {
            unset($validated['require_seller_on_pos']);
        }

        $validated['allow_price_edit_pos'] = $request->boolean('allow_price_edit_pos');

        $tenant->update($validated);

        return redirect()
            ->route('core.settings.edit')
            ->with('success', 'Configuracoes da empresa atualizadas com sucesso.');
    }
}