<?php

declare(strict_types=1);

namespace Modules\Fiscal\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Core\App\Models\Tenant;
use Modules\Fiscal\App\Models\FiscalDocument;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Tools;
use Throwable;

/**
 * Orquestra a emissao de um FiscalDocument ja revisado (itens/pagamentos/
 * observacao ja ajustados na tela de revisao - ver FiscalDocumentController):
 * aloca o numero sequencial so' agora (com lock, pra nunca repetir sob
 * concorrencia, e so' na hora de emitir de verdade - nao queima numeracao
 * em rascunhos abandonados), monta e assina o XML, tenta transmitir pra
 * SEFAZ, e registra o resultado - autorizado, rejeitado ou erro (falta de
 * certificado real, rede fora, etc.). Nunca deixa uma tentativa sem
 * rastro: mesmo falhando antes de transmitir, o FiscalDocument fica salvo
 * com status='error' e o motivo.
 */
class NFeEmissionService
{
    public function __construct(
        private readonly NFeBuilderService $builder,
        private readonly CertificateService $certificateService,
    ) {}

    public function emit(FiscalDocument $fiscalDocument): FiscalDocument
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::findOrFail($fiscalDocument->tenant_id);

        // Reaproveita o numero se essa nota ja tentou emitir antes (retry
        // depois de um erro local, ex: certificado invalido) - so' aloca
        // um numero novo na primeira tentativa de verdade.
        $number = $fiscalDocument->number ?? $this->allocateNumber($tenant);

        $fiscalDocument->update([
            'series' => $tenant->nfe_series,
            'number' => $number,
            'environment' => $tenant->sefaz_environment,
        ]);

        try {
            $signedXml = $this->builder->buildAndSign($fiscalDocument, $tenant, $number);
            $chave = $this->extractChave($signedXml);
            $path = $this->storagePath($fiscalDocument, $number, $chave, 'signed');

            Storage::disk('fiscal')->put($path, $signedXml);

            $fiscalDocument->update([
                'status' => FiscalDocument::STATUS_SIGNED,
                'chave_acesso' => $chave,
                'signed_xml_path' => $path,
                'emitted_at' => now(),
            ]);
        } catch (Throwable $e) {
            $fiscalDocument->update([
                'status' => FiscalDocument::STATUS_ERROR,
                'rejection_reason' => 'Falha ao montar/assinar o XML: '.$e->getMessage(),
            ]);

            return $fiscalDocument;
        }

        // A partir daqui, transmissao real pra SEFAZ - depende de um certificado
        // A1 valido (ICP-Brasil). Sem ele, essa etapa sempre termina em erro,
        // por design: o FiscalDocument fica com o XML assinado guardado
        // (reaproveitavel) e uma mensagem clara, sem quebrar a tela.
        try {
            $this->transmit($signedXml, $tenant, $fiscalDocument, $number, $chave);
        } catch (Throwable $e) {
            $fiscalDocument->update([
                'status' => FiscalDocument::STATUS_ERROR,
                'rejection_reason' => 'Falha na transmissao para a SEFAZ: '.$e->getMessage(),
            ]);
        }

        return $fiscalDocument->fresh();
    }

    /**
     * Incrementa o numero da NF-e do tenant dentro de uma transacao com
     * lock pessimista - garante que duas emissoes simultaneas nunca
     * recebam o mesmo numero.
     */
    private function allocateNumber(Tenant $tenant): int
    {
        return DB::transaction(function () use ($tenant): int {
            /** @var Tenant $locked */
            $locked = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);
            $number = $locked->nfe_next_number;
            $locked->increment('nfe_next_number');

            return $number;
        });
    }

    private function extractChave(string $signedXml): string
    {
        $xml = simplexml_load_string($signedXml);
        $id = (string) $xml->infNFe->attributes()->Id;

        return str_starts_with($id, 'NFe') ? substr($id, 3) : $id;
    }

    /**
     * {tenant}/{cliente|sem-cliente}/{ano}-{mes}/nfe/{modelo}-{serie}-{numero}-{chave}.xml
     * - organizado do jeito que Thomas pretende consultar depois; o disco
     * 'fiscal' e' configuravel (local hoje, S3-compativel no futuro) sem
     * mudar nenhum codigo daqui.
     */
    private function storagePath(FiscalDocument $fiscalDocument, int $number, string $chave, string $suffix): string
    {
        $customerSegment = $fiscalDocument->customer_id ? (string) $fiscalDocument->customer_id : 'sem-cliente';
        $period = now()->format('Y-m');

        return "{$fiscalDocument->tenant_id}/{$customerSegment}/{$period}/nfe/55-{$fiscalDocument->series}-{$number}-{$chave}-{$suffix}.xml";
    }

    /**
     * Monta o config exigido pela lib e tenta transmitir o lote pra SEFAZ
     * em modo sincrono. Sem um certificado A1 valido (ICP-Brasil), a
     * propria SEFAZ rejeita a conexao - esperado ate Thomas conseguir um
     * certificado real.
     */
    private function transmit(string $signedXml, Tenant $tenant, FiscalDocument $fiscalDocument, int $number, string $chave): void
    {
        $certificate = $this->certificateService->loadForTenant($tenant);

        $configJson = json_encode([
            'tpAmb' => $tenant->sefaz_environment === 'production' ? 1 : 2,
            'razaosocial' => $tenant->name,
            'cnpj' => preg_replace('/\D/', '', (string) $tenant->document),
            'siglaUF' => strtoupper((string) $tenant->state),
            'schemes' => 'PL_009_V4',
            'versao' => '4.00',
        ]);

        $tools = new Tools($configJson, $certificate);
        $tools->model(55);

        $idLote = (string) random_int(100000000, 999999999);
        $response = $tools->sefazEnviaLote([$signedXml], $idLote, 1);

        $std = (new Standardize())->toStd($response);

        // Layout tipico de retorno sincrono: protNFe.infProt tem o
        // resultado por NF-e (cStat 100 = autorizado). Se vier so' o
        // cStat do lote (ex: em contingencia/assincrono), fica registrado
        // como erro com o motivo devolvido pela SEFAZ, pra investigar
        // manualmente - essa parte so' vai ser validada de verdade contra
        // um certificado real.
        $infProt = $std->protNFe->infProt ?? null;

        if ($infProt && (string) $infProt->cStat === '100') {
            $path = $this->storagePath($fiscalDocument, $number, $chave, 'authorized');
            Storage::disk('fiscal')->put($path, $signedXml);

            $fiscalDocument->update([
                'status' => FiscalDocument::STATUS_AUTHORIZED,
                'protocol' => (string) $infProt->nProt,
                'authorized_xml_path' => $path,
                'authorized_at' => now(),
            ]);

            return;
        }

        $motivo = $infProt->xMotivo ?? ($std->xMotivo ?? 'SEFAZ nao retornou motivo.');
        $cStat = $infProt->cStat ?? ($std->cStat ?? '?');

        $fiscalDocument->update([
            'status' => FiscalDocument::STATUS_REJECTED,
            'rejection_reason' => "[{$cStat}] {$motivo}",
        ]);
    }
}
