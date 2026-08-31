<?php

declare(strict_types=1);

namespace Modules\Fiscal\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Core\App\Models\Tenant;

/**
 * Gera um certificado A1 (.pfx) autoassinado, so' pra testar o pipeline de
 * geracao/assinatura de XML de NF-e localmente - a SEFAZ nunca vai aceitar
 * esse certificado de verdade (ele nao tem cadeia ICP-Brasil), serve so'
 * pra provar que o parser da lib aceita um .pfx bem formado. Nunca roda em
 * producao.
 */
class GenerateTestCertificateCommand extends Command
{
    protected $signature = 'fiscal:test-cert
        {tenant? : ID do tenant pra anexar o certificado de teste}
        {--password=teste1234 : Senha do certificado gerado}';

    protected $description = 'Gera um certificado A1 autoassinado para testar o modulo Fiscal localmente (nunca use em producao)';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Este comando nao pode rodar em producao - o certificado gerado nao tem validade juridica nenhuma.');

            return self::FAILURE;
        }

        $password = (string) $this->option('password');
        $commonName = 'ERP Modular - Certificado de Teste';

        // Builds do PHP pra Windows normalmente nao acham o openssl.cnf
        // sozinhas (o Linux de producao ja resolve isso via config do
        // sistema) - aponta explicitamente quando existir, senao deixa o
        // openssl_* decidir sozinho.
        $opensslConfigArgs = [];
        $windowsOpensslCnf = PHP_BINDIR.DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf';
        if (!getenv('OPENSSL_CONF') && is_file($windowsOpensslCnf)) {
            $opensslConfigArgs = ['config' => $windowsOpensslCnf];
        }

        $privateKey = openssl_pkey_new($opensslConfigArgs + [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($privateKey === false) {
            $this->error('Falha ao gerar o par de chaves RSA: '.openssl_error_string());

            return self::FAILURE;
        }

        $csr = openssl_csr_new([
            'commonName' => $commonName,
            'organizationName' => 'ERP Modular (Teste)',
            'countryName' => 'BR',
        ], $privateKey, $opensslConfigArgs + ['digest_alg' => 'sha256']);

        $expiresInDays = 365;
        $x509 = openssl_csr_sign($csr, null, $privateKey, $expiresInDays, $opensslConfigArgs + ['digest_alg' => 'sha256']);

        if ($x509 === false) {
            $this->error('Falha ao autoassinar o certificado: '.openssl_error_string());

            return self::FAILURE;
        }

        openssl_x509_export($x509, $certPem);
        openssl_pkey_export($privateKey, $keyPem, null, $opensslConfigArgs);

        $pfxBinary = '';
        if (!openssl_pkcs12_export($certPem, $pfxBinary, $keyPem, $password)) {
            $this->error('Falha ao exportar o certificado em formato PKCS#12: '.openssl_error_string());

            return self::FAILURE;
        }

        $path = 'testing/fiscal/'.now()->format('YmdHis').'-test-cert.pfx';
        Storage::disk('local')->put($path, $pfxBinary);

        $this->info("Certificado de teste gerado em storage/app/private/{$path}");
        $this->line("Senha: {$password}");

        $tenantId = $this->argument('tenant');
        if ($tenantId) {
            $tenant = Tenant::find($tenantId);

            if (!$tenant) {
                $this->error("Tenant #{$tenantId} nao encontrado - certificado gerado mas nao anexado.");

                return self::FAILURE;
            }

            $tenant->update([
                'certificate_path' => $path,
                'certificate_password' => $password,
                'certificate_expires_at' => now()->addDays($expiresInDays),
            ]);

            $this->info("Certificado anexado ao tenant #{$tenant->id} ({$tenant->name}).");
        }

        return self::SUCCESS;
    }
}
