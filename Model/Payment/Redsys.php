<?php

/**
 * Copyright © Gento <desarrollo@gento.com.ar>, Inc. All rights reserved.
 */

namespace Gento\Redsys\Model\Payment;

use Gento\Redsys\Block\Payment\Form;
use Gento\Redsys\Model\System\Config\Source\Entorno;
use Gento\Redsys\Model\ToolFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;

class Redsys extends AbstractMethod
{
    const PROTECT_CODE_QUERY_PARAM = 'pcSID';

    protected $_code = "redsys";
    protected $_isOffline = false;

    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    // protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    // protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;
    protected $_isInitializeNeeded = true;
    protected $_canReviewPayment = true;
    protected $_formBlockType = Form::class;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var ToolFactory
     */
    protected $paymentTool;

    /**
     * @var Resolver
     */
    protected $localeResolver;

    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        UrlInterface $urlBuilder,
        ToolFactory $paymentTool,
        Entorno $entornos,
        Resolver $localeResolver,
        EncryptorInterface $encryptor
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            [],
            null
        );
        $this->_urlBuilder = $urlBuilder;
        $this->paymentTool = $paymentTool->create();
        $this->localeResolver = $localeResolver;
        $this->entornos = $entornos;
        $this->encryptor = $encryptor;
    }

    public function isAvailable(CartInterface $quote = null)
    {
        $isAvailable = parent::isAvailable($quote);
        if ($isAvailable == false) {
            return false;
        }

        if ($this->getAllowspecificGroup()) {
            $groups = explode(',', $this->getSpecificGroups());

            if (!in_array($quote->getCustomer()->getGroupId(), $groups)) {
                return false;
            }
        }

        return true;
    }

    public function getCallbackUrl()
    {
        return $this->_urlBuilder->getUrl('redsys/response/callback', [
            '_secure' => (bool) $this->getConfigData('secure_url'),
            self::PROTECT_CODE_QUERY_PARAM => $this->getOrder()->getProtectCode(),
        ]);
    }

    public function getOkUrl()
    {
        return $this->_urlBuilder->getUrl('redsys/response/ok', [
            '_secure' => (bool) $this->getConfigData('secure_url'),
            self::PROTECT_CODE_QUERY_PARAM => $this->getOrder()->getProtectCode(),
        ]);
    }

    public function getKoUrl()
    {
        return $this->_urlBuilder->getUrl('redsys/response/ko', [
            '_secure' => (bool) $this->getConfigData('secure_url'),
            self::PROTECT_CODE_QUERY_PARAM => $this->getOrder()->getProtectCode(),
        ]);
    }

    public function getUrlEntorno()
    {
        return $this->entornos->getById($this->getConfigData('entorno'))->url;
    }

    public function getUrlRealizarPago()
    {
        return $this->getUrlEntorno() . 'realizarPago';
    }

    public function getUrlWs()
    {
        return $this->getUrlEntorno() . 'services/SerClsWSEntrada';
    }

    public function getEncodeCheckoutFormFields()
    {
        return $this->paymentTool->getEncodeFields($this->getCheckoutFormFields());
    }

    public function getTransactiontype()
    {
        return \Gento\Redsys\Model\Tool::TRANSACTION_TYPE_AUTHORIZATION;
    }

    public function getCheckoutFormFields()
    {
        $this->setCurrency($this->getConvertedCurrencyOrder())
            ->setTransactionType(0);

        return $this->getCheckoutFields();
    }

    public function getCheckoutFields()
    {
        $description = trim($this->getProductDescription());
        if ($description == '') {
            $description = (string) __('Orden #%1', $this->getOrderId());
        }
        $datos = [
            'DS_MERCHANT_AMOUNT' => $this->getAmount(),
            'DS_MERCHANT_ORDER' => $this->getOrder()->getRealOrderId(),
            'DS_MERCHANT_MERCHANTCODE' => $this->getConfigData('fuc'),
            'DS_MERCHANT_CURRENCY' => $this->getCurrency(),
            'DS_MERCHANT_TRANSACTIONTYPE' => $this->getTransactiontype(),
            'DS_MERCHANT_TERMINAL' => $this->getConfigData('terminal'),
            'DS_MERCHANT_MERCHANTURL' => $this->getCallbackUrl(),
            'DS_MERCHANT_URLOK' => $this->getOkUrl(),
            'DS_MERCHANT_URLKO' => $this->getKoUrl(),
            'Ds_Merchant_ConsumerLanguage' => $this->paymentTool->getConvertedLocale($this->localeResolver->getLocale()),
            'DS_MERCHANT_PRODUCTDESCRIPTION' => $description,
            'Ds_Merchant_Titular' => $this->getConfigData('titular'),
            'Ds_Merchant_MerchantData' => (string) $this->getMerchantData(),
            'Ds_Merchant_MerchantName' => $this->getMerchantName(),
            'Ds_Merchant_Module' => 'Gento_Redsys 1.0',
            //
            // Parametros SOAP
            //
            // 'Ds_Merchant_MerchantSignature' => $this->getRedsysSignature(),
            // 'Ds_Merchant_SumTotal' => $this->getTotalCuotas(),
            // 'Ds_Merchant_DateFrecuency' => $this->getDateFrecuency(),
            // 'Ds_Merchant_ChargeExpiryDate' => $this->getChargeExpiryDate(),
            // 'Ds_Merchant_AuthorisationCode' => $this->getAuthorisationCode(),
            // 'Ds_Merchant_TransactionDate' => $this->getTransactionDate(),
        ];

        $arr = [];
        foreach ($datos as $k => $v) {
            $arr[strtoupper($k)] = $v;
        }

        $notIn = $this->paymentTool->getExcludeFieldsByTransactionType($this->getTransactiontype());
        foreach ($notIn as $value) {
            unset($arr[strtoupper($value)]);
        }
        return $arr;
    }

    public function getAmount()
    {
        return (int) round(((float) $this->getOrder()->getTotalDue()) * 100);
    }

    public function getProductDescription()
    {
        return $this->paymentTool->getMaxString($this->getConfigData('descripcion'), 125);
    }

    public function getMerchantData()
    {
        return $this->paymentTool->getMaxString($this->getConfigData('nombre'), 1024);
    }

    public function getMerchantName()
    {
        $name = $this->_scopeConfig->getValue('general/store_information/name');
        if (empty($name)) {
            $name = $this->getConfigData('titular');
        }

        return $this->paymentTool->getMaxString($name, 60);
    }

    public function getConvertedCurrencyOrder($order = null)
    {
        if ($order == null) {
            $order = $this->getOrder();
        }

        return $this->paymentTool->getConvertedCurrency($order->getOrderCurrencyCode());
    }

    public function getEncodeSignature($order_id = null, $datos = null, $secret = null)
    {
        if ($order_id == null) {
            $order_id = $this->getOrder()->getRealOrderId();
        }

        if ($datos == null) {
            $datos = $this->getCheckoutFormFields();
        }

        if ($secret == null) {
            $secret = $this->getMerchantSecret();
        }

        return $this->paymentTool->getEncodeSignature($order_id, $datos, $secret);
    }

    public function getMerchantSecret()
    {
        return (string) $this->encryptor->decrypt($this->getConfigData('secret'));
    }

    public function getAutoInvoice()
    {
        return $this->getConfigData('auto_invoice');
    }

    public function getAllowspecificGroup()
    {
        return $this->getConfigData('allowspecific_group');
    }

    public function getSpecificGroups()
    {
        return $this->getConfigData('specific_groups');
    }

    public function getSendEmailOrderConfirmation()
    {
        return $this->getConfigData('email_order_confirmation');
    }

    public function refund(InfoInterface $payment, $amount)
    {
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
        }

        $error = false;
        if ($amount <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('El monto no puede ser 0'));
        }

        $rc = $this->_solicitarDevolucion($payment, $amount);
        if ($rc['Error']) {
            throw new \Magento\Framework\Exception\LocalizedException(__($rc['ErrorDescription']));
        }

        return parent::refund($payment, $amount);
    }

    protected function _solicitarDevolucion(InfoInterface $payment, $amount)
    {
        $amount = (int) ($amount * 100);

        $order = $payment->getOrder();

        $this->setOrder($order);

        $datos = [
            'DS_MERCHANT_AMOUNT' => (string) $amount,
            'DS_MERCHANT_ORDER' => $order->getRealOrderId(),
            'DS_MERCHANT_MERCHANTCODE' => $this->getConfigData('fuc'),
            'DS_MERCHANT_CURRENCY' => $this->getConvertedCurrencyOrder(),
            'DS_MERCHANT_TRANSACTIONTYPE' => (string) \Gento\Redsys\Model\Tool::TRANSACTION_TYPE_AUTOMATIC_REFUND,
            'DS_MERCHANT_TERMINAL' => $this->getConfigData('terminal'),
        ];
        $peticion = new \DOMDocument();
        $request = $peticion->appendChild($peticion->createElement('REQUEST'));
        $datosEntrada = $request->appendChild($peticion->createElement('DATOSENTRADA'));
        foreach ($datos as $key => $value) {
            $datosEntrada->appendChild($peticion->createElement($key, $value));
        }

        $tmp = new \DOMDocument();
        $cloned = $datosEntrada->cloneNode(true);
        $tmp->appendChild($tmp->importNode($cloned, true));
        $datosFirma = trim(str_replace('<?xml version="1.0"?>', '', $tmp->saveXML()));
        $signature = $this->getEncodeSignature($order->getRealOrderId(), $datosFirma);

        $request->appendChild($peticion->createElement('DS_SIGNATURE', $signature));
        $request->appendChild($peticion->createElement('DS_SIGNATUREVERSION', 'HMAC_SHA256_V1'));

        $client = new \SoapClient($this->getUrlWs() . '?wsdl');
        $r = $client->trataPeticion(['datoEntrada' => $peticion->saveXML()]);

        $rc = simplexml_load_string($r->trataPeticionReturn);
        if ($rc->CODIGO == null) {
            throw new LocalizedException(__('Error de comunicacion, comuniquese con Redsys'));
        }
        $codigo = trim($rc->CODIGO);

        if ($codigo != '0') {
            throw new LocalizedException($this->paymentTool->getRedsysMessage($codigo));
        }
        return $rc;
    }
}
