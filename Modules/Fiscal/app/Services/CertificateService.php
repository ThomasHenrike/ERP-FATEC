<?php

declare(strict_types=1);

namespace Modules\Fiscal\App\Services;

use Illuminate\Support\Facades\Storage;
use Modules\Core\App\Models\Tenant;
use NFePHP\Common\Certificate;
use RuntimeException;

class CertificateService
{
    /**
     * Carrega o certificado A1 do tenant a partir do disco privado, pronto
     * pra assinar XML. Lanca excecoes especificas pros 3 jeitos reais de
     * isso quebrar pra um usuario leigo: expirado, senha errada, arquivo
     * corrompido/invalido.
     */
    public function loadForTenant(Tenant $tenant): Certificate
    {
        if (empty($tenant->certificate_path)) {
            throw new RuntimeException(
                'Esta empresa ainda nao tem um certificado digital A1 cadastrado. '.
                'Envie o arquivo .pfx em Configuracoes > Fiscal & Certificado A1.'
            );
        }

        if ($tenant->certificate_expires_at && $tenant->certificate_expires_at->isPast()) {
            throw new RuntimeException(
                "O certificado digital desta empresa venceu em {$tenant->certificate_expires_at->format('d/m/Y')}. ".
                'Envie um certificado atualizado em Configuracoes > Fiscal & Certificado A1.'
            );
        }

        if (!Storage::disk('local')->exists($tenant->certificate_path)) {
            throw new RuntimeException(
                'O arquivo do certificado digital nao foi encontrado no servidor. '.
                'Envie o arquivo .pfx novamente em Configuracoes > Fiscal & Certificado A1.'
            );
        }

        $pfxContent = Storage::disk('local')->get($tenant->certificate_path);
        $password = $tenant->getCertificatePasswordDecrypted();

        if (empty($password)) {
            throw new RuntimeException(
                'A senha do certificado digital nao pode ser lida. '.
                'Reenvie o certificado com a senha em Configuracoes > Fiscal & Certificado A1.'
            );
        }

        try {
            return Certificate::readPfx($pfxContent, $password);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Nao foi possivel ler o certificado digital - o arquivo pode estar corrompido ou a senha esta incorreta. '.
                'Envie o certificado novamente em Configuracoes > Fiscal & Certificado A1.',
                previous: $e
            );
        }
    }
}
