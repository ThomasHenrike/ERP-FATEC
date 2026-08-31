<?php

declare(strict_types=1);

namespace Modules\Core\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\SuperAdmin\App\Models\Invoice;
use Modules\SuperAdmin\App\Models\Ticket;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'uuid',
        'name',
        'trade_name',
        'document',
        'state_registration',
        'email',
        'phone',
        'whatsapp',
        'zip_code',
        'street',
        'number',
        'neighborhood',
        'city',
        'state',
        'logo_path',
        'primary_color',
        'print_format',
        'print_behavior',
        'receipt_footer',
        'certificate_path',
        'certificate_password',
        'certificate_expires_at',
        'sefaz_environment',
        'allow_negative_stock',
        'max_discount_percentage',
        'require_seller_on_pos',
        'allow_price_edit_pos',
        'quote_show_ncm',
        'quote_show_icms',
        'quote_show_icms_st',
        'quote_show_lead_time',
        'quote_show_payment_condition',
        'quote_show_delivery_term',
        'quote_show_freight',
        'quote_default_payment_condition',
        'quote_default_delivery_term',
        'quote_default_freight_info',
        'quote_default_lead_time_days',
        'quote_default_validity_days',
        'is_active',
        'billing_due_day',
        'billing_amount',
        'max_users',
        'crt',
        'ibge_city_code',
        'nfe_series',
        'nfe_next_number',
        'nfce_series',
        'nfce_next_number',
        'fiscal_default_observation',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_negative_stock' => 'boolean',
        'require_seller_on_pos' => 'boolean',
        'allow_price_edit_pos' => 'boolean',
        'quote_show_ncm' => 'boolean',
        'quote_show_icms' => 'boolean',
        'quote_show_icms_st' => 'boolean',
        'quote_show_lead_time' => 'boolean',
        'quote_show_payment_condition' => 'boolean',
        'quote_show_delivery_term' => 'boolean',
        'quote_show_freight' => 'boolean',
        'certificate_expires_at' => 'datetime',
        'max_discount_percentage' => 'decimal:2',
        'quote_default_lead_time_days' => 'integer',
        'quote_default_validity_days' => 'integer',
        'billing_due_day' => 'integer',
        'billing_amount' => 'decimal:2',
        'max_users' => 'integer',
        'crt' => 'integer',
        'nfe_series' => 'integer',
        'nfe_next_number' => 'integer',
        'nfce_series' => 'integer',
        'nfce_next_number' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $tenant): void {
            if (empty($tenant->uuid)) {
                $tenant->uuid = (string) Str::uuid();
            }
        });
    }

    public function setCertificatePasswordAttribute(?string $value): void
    {
        $this->attributes['certificate_password'] = !empty($value) 
            ? Crypt::encryptString($value) 
            : null;
    }

    public function getCertificatePasswordDecrypted(): ?string
    {
        if (empty($this->attributes['certificate_password'])) {
            return null;
        }

        try {
            return Crypt::decryptString($this->attributes['certificate_password']);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Entrega a imagem da logomarca em Base64 Data URI com resolucao de dupla camada.
     * Funciona em 100% dos ambientes (Windows, Linux, HostGator, impressoras A4 e PDFs).
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo_path)) {
            return null;
        }

        try {
            // Camada 1: Via Storage do Laravel
            if (Storage::disk('public')->exists($this->logo_path)) {
                $mime = Storage::disk('public')->mimeType($this->logo_path) ?: 'image/png';
                $data = Storage::disk('public')->get($this->logo_path);
                return 'data:' . $mime . ';base64,' . base64_encode($data);
            }

            // Camada 2: Via caminho absoluto do disco
            $fullPath = storage_path('app/public/' . ltrim($this->logo_path, '/'));
            if (file_exists($fullPath)) {
                $mime = mime_content_type($fullPath) ?: 'image/png';
                $data = file_get_contents($fullPath);
                return 'data:' . $mime . ';base64,' . base64_encode($data);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    public function modules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Sem linha na tabela = modulo habilitado (evita travar empresas que
     * ja existiam antes dessa funcionalidade). Core nunca passa por aqui,
     * so os modulos de negocio ativaveis (cadastros, estoque, vendas,
     * orcamento).
     */
    public function hasModule(string $module): bool
    {
        // Versao de trabalho academico: Fiscal e Orcamento ficam sempre
        // travados aqui, independente de qualquer registro em TenantModule
        // - o codigo continua completo, so essas 2 areas nao sao
        // alcancaveis (module.enabled:* bloqueia com aviso "em
        // desenvolvimento" antes de chegar no controller). Ajuda/Suporte
        // ficam liberados normalmente, por fazerem parte do Core.
        if (in_array($module, ['fiscal', 'orcamento'], true)) {
            return false;
        }

        $row = $this->relationLoaded('modules')
            ? $this->modules->firstWhere('module', $module)
            : $this->modules()->where('module', $module)->first();

        return $row === null ? true : $row->is_enabled;
    }
}