<?php

declare(strict_types=1);

namespace Modules\Fiscal\App\Services;

use Modules\Cadastros\App\Models\Customer;
use Modules\Core\App\Models\Tenant;
use Modules\Fiscal\App\Models\FiscalDocument;
use Modules\Fiscal\App\Models\FiscalDocumentItem;
use Modules\Fiscal\App\Support\PaymentMethodMapper;
use NFePHP\Common\Signer;
use NFePHP\NFe\Make;
use RuntimeException;
use stdClass;

/**
 * Monta o XML da NF-e (modelo 55) a partir de um FiscalDocument ja
 * revisado - usa a copia propria de itens/pagamentos/observacao do
 * documento fiscal (editavel na tela de revisao, ver
 * Modules/Fiscal/App/Http/Controllers/FiscalDocumentController), nao lida
 * direto com a Sale nem com o Product ao vivo, exatamente pra permitir
 * corrigir algo so' na nota sem tocar na venda original.
 */
class NFeBuilderService
{
    public function __construct(
        private readonly CertificateService $certificateService
    ) {}

    /**
     * Monta o XML da NF-e e assina digitalmente com o certificado A1 do
     * tenant. Nao transmite pra SEFAZ (fase 2) - so' devolve o XML assinado,
     * pronto pra ser persistido/transmitido depois.
     */
    public function buildAndSign(FiscalDocument $fiscalDocument, Tenant $tenant, ?int $number = null): string
    {
        $make = $this->build($fiscalDocument, $tenant, $number);
        $certificate = $this->certificateService->loadForTenant($tenant);

        // O schema oficial da NF-e fixa SHA-1 pro XML-DSig (SignatureMethod
        // e DigestMethod) - SHA-256 e' mais forte, mas viola a validacao,
        // entao fica travado no algoritmo antigo por exigencia da SEFAZ.
        return Signer::sign($certificate, $make->getXML(), 'infNFe', 'Id', OPENSSL_ALGO_SHA1);
    }

    /**
     * Codigo IBGE da UF - exigido no campo cUF da NF-e.
     */
    private const UF_CODES = [
        'AC' => 12, 'AL' => 17, 'AP' => 16, 'AM' => 13, 'BA' => 29, 'CE' => 23,
        'DF' => 53, 'ES' => 32, 'GO' => 52, 'MA' => 21, 'MT' => 51, 'MS' => 50,
        'MG' => 31, 'PA' => 15, 'PB' => 25, 'PR' => 41, 'PE' => 26, 'PI' => 22,
        'RJ' => 33, 'RN' => 24, 'RS' => 43, 'RO' => 11, 'RR' => 14, 'SC' => 42,
        'SP' => 35, 'SE' => 28, 'TO' => 17,
    ];

    public function build(FiscalDocument $fiscalDocument, Tenant $tenant, ?int $number = null): Make
    {
        $fiscalDocument->loadMissing(['items.product', 'customer', 'payments']);

        $this->assertReadyForEmission($fiscalDocument, $tenant);

        $make = new Make();

        $make->taginfNFe((object) ['versao' => '4.00']);
        $make->tagide($this->buildIde($tenant, $number));
        $make->tagemit($this->buildEmit($tenant));
        $make->tagenderEmit($this->buildEnderEmit($tenant));

        if ($fiscalDocument->customer) {
            $make->tagdest($this->buildDest($fiscalDocument->customer));
            $make->tagenderDest($this->buildEnderDest($fiscalDocument->customer));
        }

        foreach ($fiscalDocument->items as $index => $item) {
            $itemNumber = $index + 1;

            $make->tagprod($this->buildProd($item, $itemNumber));
            $make->tagimposto((object) ['item' => $itemNumber, 'vTotTrib' => 0]);
            $this->buildIcms($make, $item, $itemNumber);
            $make->tagPIS($this->buildPis($item, $itemNumber));
            $make->tagCOFINS($this->buildCofins($item, $itemNumber));
        }

        $make->tagICMSTot($this->buildIcmsTot($fiscalDocument));
        $make->tagtransp((object) ['modFrete' => 9]); // 9 = Sem frete (sem dado de transporte no schema atual)
        $make->tagpag((object) ['vTroco' => 0.0]);
        $this->buildPagamentos($make, $fiscalDocument);

        if (!empty($fiscalDocument->observacao)) {
            $make->taginfAdic((object) ['infCpl' => $fiscalDocument->observacao]);
        }

        $make->montaNFe();

        $errors = $make->getErrors();
        if (!empty($errors)) {
            throw new RuntimeException('Erro ao montar o XML da NF-e: '.implode(' | ', $errors));
        }

        return $make;
    }

    /**
     * Checagens minimas antes de tentar montar o XML - falha cedo e com
     * mensagem clara em vez de deixar a lib estourar um erro generico.
     */
    private function assertReadyForEmission(FiscalDocument $fiscalDocument, Tenant $tenant): void
    {
        if (empty($tenant->ibge_city_code)) {
            throw new RuntimeException('A empresa nao tem o codigo IBGE do municipio cadastrado (Configuracoes > Dados Cadastrais).');
        }

        if (empty($tenant->state) || !isset(self::UF_CODES[strtoupper($tenant->state)])) {
            throw new RuntimeException('A empresa nao tem uma UF valida cadastrada (Configuracoes > Dados Cadastrais).');
        }

        if ($fiscalDocument->customer && empty($fiscalDocument->customer->ibge_city_code)) {
            throw new RuntimeException('O cliente desta nota nao tem o codigo IBGE do municipio cadastrado.');
        }

        if ($fiscalDocument->items->isEmpty()) {
            throw new RuntimeException('Esta NF-e nao tem nenhum item - adicione ao menos um produto antes de emitir.');
        }

        foreach ($fiscalDocument->items as $item) {
            if (empty($item->ncm) || empty($item->cfop)) {
                throw new RuntimeException(
                    "O item \"{$item->product?->name}\" desta NF-e nao tem NCM/CFOP preenchido - ".
                    'corrija o item na tela de revisao antes de emitir.'
                );
            }
        }
    }

    private function buildIde(Tenant $tenant, ?int $number = null): stdClass
    {
        $std = new stdClass();
        $std->cUF = self::UF_CODES[strtoupper($tenant->state)];
        $std->natOp = 'Venda de mercadoria';
        $std->mod = 55;
        $std->serie = $tenant->nfe_series;
        $std->nNF = $number ?? $tenant->nfe_next_number;
        $std->tpNF = 1; // saida
        $std->idDest = 1; // operacao interna (mesma UF) - refinar quando destinatario de outra UF for suportado
        $std->cMunFG = $tenant->ibge_city_code;
        $std->tpImp = 1; // DANFE normal, retrato
        $std->tpEmis = 1; // emissao normal
        $std->tpAmb = $tenant->sefaz_environment === 'production' ? 1 : 2;
        $std->finNFe = 1; // NF-e normal
        $std->indFinal = $tenant->crt == 1 ? 1 : 0; // consumidor final (simplificacao - refinar por cliente depois)
        $std->indPres = 1; // operacao presencial
        $std->procEmi = 0; // emissao de aplicativo do contribuinte
        $std->verProc = 'ERP Modular 1.0';

        return $std;
    }

    private function buildEmit(Tenant $tenant): stdClass
    {
        $document = preg_replace('/\D/', '', (string) $tenant->document);

        $std = new stdClass();
        $std->xNome = $tenant->name;
        $std->xFant = $tenant->trade_name;
        $std->IE = $tenant->state_registration ? preg_replace('/\D/', '', $tenant->state_registration) : null;
        $std->CRT = $tenant->crt;
        $std->CNPJ = $document;

        return $std;
    }

    private function buildEnderEmit(Tenant $tenant): stdClass
    {
        $std = new stdClass();
        $std->xLgr = $tenant->street;
        $std->nro = $tenant->number ?: 'S/N';
        $std->xBairro = $tenant->neighborhood;
        $std->cMun = $tenant->ibge_city_code;
        $std->xMun = $tenant->city;
        $std->UF = strtoupper((string) $tenant->state);
        $std->CEP = preg_replace('/\D/', '', (string) $tenant->zip_code);
        $std->cPais = 1058;
        $std->xPais = 'Brasil';
        $std->fone = $tenant->phone ? preg_replace('/\D/', '', $tenant->phone) : null;

        return $std;
    }

    private function buildDest(Customer $customer): stdClass
    {
        $document = preg_replace('/\D/', '', (string) $customer->document);

        $std = new stdClass();
        $std->xNome = $customer->name;
        $std->indIEDest = $customer->isJuridica() && $customer->state_registration ? 1 : 9; // 9 = Nao Contribuinte
        $std->IE = $customer->state_registration ? preg_replace('/\D/', '', $customer->state_registration) : null;
        $std->email = $customer->email;

        if ($customer->isJuridica()) {
            $std->CNPJ = $document;
        } else {
            $std->CPF = $document;
        }

        return $std;
    }

    private function buildEnderDest(Customer $customer): stdClass
    {
        $std = new stdClass();
        $std->xLgr = $customer->street;
        $std->nro = $customer->number ?: 'S/N';
        $std->xBairro = $customer->neighborhood;
        $std->cMun = $customer->ibge_city_code;
        $std->xMun = $customer->city;
        $std->UF = strtoupper((string) $customer->state);
        $std->CEP = preg_replace('/\D/', '', (string) $customer->zip_code);
        $std->cPais = 1058;
        $std->xPais = 'Brasil';
        $std->fone = $customer->phone ? preg_replace('/\D/', '', $customer->phone) : null;

        return $std;
    }

    private function buildProd(FiscalDocumentItem $item, int $itemNumber): stdClass
    {
        $unit = $item->product?->unit ?? 'UN';

        $std = new stdClass();
        $std->item = $itemNumber;
        $std->cProd = (string) $item->product_id;
        $std->cEAN = 'SEM GTIN';
        $std->xProd = $item->product?->name ?? 'Produto';
        $std->NCM = $item->ncm;
        $std->CEST = $item->cest;
        $std->CFOP = $item->cfop;
        $std->uCom = $unit;
        $std->qCom = (float) $item->quantity;
        $std->vUnCom = (float) $item->unit_price;
        $std->vProd = (float) $item->total_price;
        $std->cEANTrib = 'SEM GTIN';
        $std->uTrib = $unit;
        $std->qTrib = (float) $item->quantity;
        $std->vUnTrib = (float) $item->unit_price;
        $std->indTot = 1;

        // O schema da NF-e rejeita vDesc="0.00" (o padrao exige omitir o
        // campo por completo quando nao ha desconto, nao gravar zero).
        $discount = (float) $item->discount_amount;
        if ($discount > 0) {
            $std->vDesc = $discount;
        }

        return $std;
    }

    private function buildIcms(Make $make, FiscalDocumentItem $item, int $itemNumber): void
    {
        $orig = $item->origin ?? 0;
        $icmsRate = (float) ($item->icms_rate ?? 0);
        $vBC = $icmsRate > 0 ? (float) $item->total_price : 0;
        $vIcms = round($vBC * ($icmsRate / 100), 2);

        if (!empty($item->csosn)) {
            $make->tagICMSSN((object) [
                'item' => $itemNumber,
                'orig' => $orig,
                'CSOSN' => $item->csosn,
                'modBC' => 3,
                'vBC' => $vBC,
                'pICMS' => $icmsRate,
                'vICMS' => $vIcms,
            ]);

            return;
        }

        // Regime normal (CST) - branch preparado pra quando um tenant CRT=3 existir.
        $make->tagICMS((object) [
            'item' => $itemNumber,
            'orig' => $orig,
            'CST' => $item->cst_icms ?? '00',
            'modBC' => 3,
            'vBC' => $vBC,
            'pICMS' => $icmsRate,
            'vICMS' => $vIcms,
        ]);
    }

    private function buildPis(FiscalDocumentItem $item, int $itemNumber): stdClass
    {
        $rate = (float) ($item->pis_rate ?? 0);
        $vBC = $rate > 0 ? (float) $item->total_price : 0;

        $std = new stdClass();
        $std->item = $itemNumber;
        $std->CST = $item->cst_pis ?? '07';
        $std->vBC = $vBC;
        $std->pPIS = $rate;
        $std->vPIS = round($vBC * ($rate / 100), 2);

        return $std;
    }

    private function buildCofins(FiscalDocumentItem $item, int $itemNumber): stdClass
    {
        $rate = (float) ($item->cofins_rate ?? 0);
        $vBC = $rate > 0 ? (float) $item->total_price : 0;

        $std = new stdClass();
        $std->item = $itemNumber;
        $std->CST = $item->cst_cofins ?? '07';
        $std->vBC = $vBC;
        $std->pCOFINS = $rate;
        $std->vCOFINS = round($vBC * ($rate / 100), 2);

        return $std;
    }

    private function buildIcmsTot(FiscalDocument $fiscalDocument): stdClass
    {
        $vProd = (float) $fiscalDocument->items->sum('total_price');
        $vDesc = (float) $fiscalDocument->items->sum('discount_amount');

        $std = new stdClass();
        $std->vBC = 0;
        $std->vICMS = 0;
        $std->vICMSDeson = 0;
        $std->vBCST = 0;
        $std->vST = 0;
        $std->vProd = $vProd;
        $std->vFrete = 0;
        $std->vSeg = 0;
        $std->vDesc = $vDesc;
        $std->vII = 0;
        $std->vIPI = 0;
        $std->vPIS = 0;
        $std->vCOFINS = 0;
        $std->vOutro = 0;
        $std->vNF = round($vProd - $vDesc, 2);

        return $std;
    }

    private function buildPagamentos(Make $make, FiscalDocument $fiscalDocument): void
    {
        foreach ($fiscalDocument->payments as $payment) {
            $detPag = (object) [
                'tPag' => PaymentMethodMapper::toTPag($payment->forma_pagamento),
                'vPag' => (float) $payment->valor,
            ];

            if ($payment->forma_pagamento === 'boleto') {
                $detPag->indPag = 1; // pagamento a prazo
            }

            $make->tagdetPag($detPag);
        }
    }
}
