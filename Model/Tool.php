<?php

/**
 * Copyright © Gento <desarrollo@gento.com.ar>, Inc. All rights reserved.
 */

namespace Gento\Redsys\Model;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;

class Tool extends DataObject
{
    const TRANSACTION_TYPE_AUTHORIZATION = 0;
    const TRANSACTION_TYPE_PREAUTHORIZATION = 1;
    const TRANSACTION_TYPE_PREAUTHORIZATION_CONFIRMATION = 2;
    const TRANSACTION_TYPE_PREAUTHORIZATION_VOID = 9;
    const TRANSACTION_TYPE_AUTOMATIC_REFUND = 3;
    const TRANSACTION_TYPE_REFERENCE_PAYMENT = 4;

    private $_redsys_mensajes = [
        'SIS0007' => 'Error al desmontar el XML de entrada',
        'SIS0008' => 'Error falta Ds_Merchant_MerchantCode',
        'SIS0009' => 'Error de formato en Ds_Merchant_MerchantCode',
        'SIS0010' => 'Error falta Ds_Merchant_Terminal',
        'SIS0011' => 'Error de formato en Ds_Merchant_Terminal',
        'SIS0014' => 'Error de formato en Ds_Merchant_Order',
        'SIS0015' => 'Error falta Ds_Merchant_Currency',
        'SIS0016' => 'Error de formato en Ds_Merchant_Currency',
        'SIS0017' => 'Error no se admiten operaciones en pesetas',
        'SIS0018' => 'Error falta Ds_Merchant_Amount',
        'SIS0019' => 'Error de formato en Ds_Merchant_Amount',
        'SIS0020' => 'Error falta Ds_Merchant_MerchantSignature',
        'SIS0021' => 'Error la Ds_Merchant_MerchantSignature viene vacia',
        'SIS0022' => 'Error de formato en Ds_Merchant_TransactionType',
        'SIS0023' => 'Error Ds_Merchant_TransactionType desconocido',
        'SIS0024' => 'Error Ds_Merchant_ConsumerLanguage tiene mas de 3 posiciones',
        'SIS0025' => 'Error de formato en Ds_Merchant_ConsumerLanguage',
        'SIS0026' => 'Error No existe el comercio / terminal enviado',
        'SIS0027' => 'Error Moneda enviada por el comercio es diferente a la que tiene asignada para ese terminal',
        'SIS0028' => 'Error Comercio / terminal esta dado de baja',
        'SIS0030' => 'Error en un pago con tarjeta ha llegado un tipo de operacion que no es ni pago ni preautorizacion',
        'SIS0031' => 'Metodo de pago no definido',
        'SIS0033' => 'Error en un pago con movil ha llegado un tipo de operacion que no es ni pago ni preautorizacion',
        'SIS0034' => 'Error de acceso a la Base de Datos',
        'SIS0037' => 'El numero de telefono no es valido',
        'SIS0038' => 'Error en java',
        'SIS0040' => 'Error el comercio / terminal no tiene ningun metodo de pago asignado',
        'SIS0041' => 'Error en el calculo de la HASH de datos del comercio.',
        'SIS0042' => 'La firma enviada no es correcta',
        'SIS0043' => 'Error al realizar la notificacion on-line',
        'SIS0046' => 'El bin de la tarjeta no esta dado de alta',
        'SIS0051' => 'Error numero de pedido repetido',
        'SIS0054' => 'Error no existe operacion sobre la que realizar la devolucion',
        'SIS0055' => 'Error existe mas de un pago con el mismo nomero de pedido',
        'SIS0056' => 'La operacion sobre la que se desea devolver no esta autorizada',
        'SIS0057' => 'El importe a devolver supera el permitido',
        'SIS0058' => 'Inconsistencia de datos, en la validacion de una confirmacion',
        'SIS0059' => 'Error no existe operacion sobre la que realizar la confirmacion',
        'SIS0060' => 'Ya existe una confirmacion asociada a la preautorizacion',
        'SIS0061' => 'La preautorizacion sobre la que se desea confirmar no esta autorizada ',
        'SIS0062' => 'El importe a confirmar supera el permitido',
        'SIS0063' => 'Error. Numero de tarjeta no disponible',
        'SIS0064' => 'Error. El numero de tarjeta no puede tener mas de 19 posiciones',
        'SIS0065' => 'Error. El numero de tarjeta no es numerico',
        'SIS0066' => 'Error. Mes de caducidad no disponible',
        'SIS0067' => 'Error. El mes de la caducidad no es numerico',
        'SIS0068' => 'Error. El mes de la caducidad no es valido',
        'SIS0069' => 'Error. Anyo de caducidad no disponible',
        'SIS0070' => 'Error. El Anyo de la caducidad no es numerico',
        'SIS0071' => 'Tarjeta caducada',
        'SIS0072' => 'Operacion no anulable',
        'SIS0074' => 'Error falta Ds_Merchant_Order',
        'SIS0075' => 'Error el Ds_Merchant_Order tiene menos de 4 posiciones o mas de 12',
        'SIS0076' => 'Error el Ds_Merchant_Order no tiene las cuatro primeras posiciones numericas ',
        'SIS0077' => 'Error el Ds_Merchant_Order no tiene las cuatro primeras posiciones numericas. No se utiliza',
        'SIS0078' => 'etodo de pago no disponible',
        'SIS0079' => 'Error al realizar el pago con tarjeta',
        'SIS0081' => 'La sesion es nueva, se han perdido los datos almacenados',
        'SIS0084' => 'El valor de Ds_Merchant_Conciliation es nulo',
        'SIS0085' => 'El valor de Ds_Merchant_Conciliation no es numerico',
        'SIS0086' => 'El valor de Ds_Merchant_Conciliation no ocupa 6 posiciones',
        'SIS0089' => 'El valor de Ds_Merchant_ExpiryDate no ocupa 4 posiciones',
        'SIS0092' => 'El valor de Ds_Merchant_ExpiryDate es nulo',
        'SIS0093' => 'Tarjeta no encontrada en la tabla de rangos',
        'SIS0094' => 'La tarjeta no fue autenticada como 3D Secure',
        'SIS0097' => 'Valor del campo Ds_Merchant_CComercio no valido',
        'SIS0098' => 'Valor del campo Ds_Merchant_CVentana no valido',
        'SIS0112' => 'Error El tipo de transaccion especificado en Ds_Merchant_Transaction_Type no esta permitido ',
        'SIS0113' => 'Excepcion producida en el servlet de operaciones',
        'SIS0114' => 'Error, se ha llamado con un GET en lugar de un POST',
        'SIS0115' => 'Error no existe operacion sobre la que realizar el pago de la cuota',
        'SIS0116' => 'La operacion sobre la que se desea pagar una cuota no es una operacion valida',
        'SIS0117' => 'La operacion sobre la que se desea pagar una cuota no esta autorizada',
        'SIS0118' => 'Se ha excedido el importe total de las cuotas',
        'SIS0119' => 'Valor del campo Ds_Merchant_DateFrecuency no valido',
        'SIS0120' => 'Valor del campo Ds_Merchant_ChargeExpiryDate no valido',
        'SIS0121' => 'Valor del campo Ds_Merchant_SumTotal no valido',
        'SIS0122' => 'Valor del campo Ds_Merchant_DateFrecuency Ds_Merchant_SumTotal tiene formato incorrecto',
        'SIS0123' => 'Se ha excedido la fecha tope para realizar transacciones',
        'SIS0124' => 'No ha transcurrido la frecuencia minima en un pago recurrente sucesivo',
        'SIS0132' => 'La fecha de Confirmacion de Autorizacion no puede superar en mas de 7 dias a la de Preautorizacion.',
        'SIS0133' => 'La fecha de Confirmacion de Autenticacion no puede superar en mas de 45 dias a la de Autenticacion Previa.',
        'SIS0139' => 'Error el pago recurrente inicial esta duplicado',
        'SIS0142' => 'Tiempo excedido para el pago',
        'SIS0197' => 'Error al obtener los datos de cesta de la compra en operacion tipo pasarela',
        'SIS0198' => 'Error el importe supera el limite permitido para el comercio',
        'SIS0199' => 'Error el numero de operaciones supera el limite permitido para el comercio',
        'SIS0200' => 'Error el importe acumulado supera el limite permitido para el comercio',
        'SIS0214' => 'El comercio no admite devoluciones',
        'SIS0216' => 'Error Ds_Merchant_CVV2 tiene mas de 3 posiciones',
        'SIS0217' => 'Error de formato en Ds_Merchant_CVV2',
        'SIS0218' => 'El comercio no permite operaciones seguras por la entrada/operaciones',
        'SIS0219' => 'Error el numero de operaciones de la tarjeta supera el limite',
        'SIS0220' => 'Error el importe acumulado de la tarjeta supera el limite permitido para el comercio',
        'SIS0221' => 'Error el CVV2 es obligatorio',
        'SIS0222' => 'Ya existe una anulacion asociada a la preautorizacion',
        'SIS0223' => 'La preautorizacion que se desea anular no esta autorizada',
        'SIS0224' => 'El comercio no permite anulaciones por no tener firma ampliada',
        'SIS0225' => 'Error no existe operacion sobre la que realizar la anulacion',
        'SIS0226' => 'Inconsistencia de datos, en la validacion de una anulacion',
        'SIS0227' => 'Valor del campo Ds_Merchant_TransactionDate no valido',
        'SIS0229' => 'No existe el codigo de pago aplazado solicitado',
        'SIS0252' => 'El comercio no permite el envio de tarjeta',
        'SIS0253' => 'La tarjeta no cumple el check-digit',
        'SIS0254' => 'El numero de operaciones de la IP supera el limite permitido por el comercio',
        'SIS0255' => 'El importe acumulado por la IP supera el limite permitido por el comercio',
        'SIS0256' => 'El comercio no puede realizar preautorizaciones',
        'SIS0257' => 'Esta tarjeta no permite operativa de preautorizaciones',
        'SIS0258' => 'Inconsistencia de datos, en la validacion de una confirmacion',
        'SIS0261' => 'Operacion detenida por superar el control de restricciones en la entrada al SIS',
        'SIS0270' => 'El comercio no puede realizar autorizaciones en diferido',
        'SIS0274' => 'Tipo de operacion desconocida o no permitida por esta entrada al SIS',
        // Errores XML
        'XML0000' => 'Errores varios en el proceso del XML-String recibido.',
        'XML0001' => 'Error en la generacion del DOM a partir del XML-String recibido y la DTD definida.',
        'XML0002' => 'No existe el elemento "Message" en el XML-String recibido.',
        'XML0003' => 'El tipo de "Message" en el XML-String recibido tiene un valor desconocido o invalido en la peticion.',
        'XML0004' => 'No existe el elemento "Ds_MerchantCode" en el XMLString recibido.',
        'XML0005' => 'El elemento "Ds_MerchantCode" viene vacio en el XMLString recibido.',
        'XML0006' => 'El elemento "Ds_MerchantCode" tiene una longitud incorrecta en el XML-String recibido.',
        'XML0007' => 'El elemento "Ds_MerchantCode" no tiene formato numerico en el XML-String recibido.',
        'XML0008' => 'No existe el elemento "Ds_Terminal" en el XML-String recibido.',
        'XML0023' => 'La firma no es correcta.',
        'XML0024' => 'No existen operaciones en TZE para los datos solicitados.',
        'XML0025' => 'El XML de respuesta esta mal formado.',
        'XML0026' => 'No existe el elemento "Ds_fecha_inicio" en el XML-String recibido.',
        'XML0027' => 'No existe el elemento "Ds_fecha_fin" en el XML-String recibido.',
    ];

    public function getRedsysMessage($code)
    {
        return isset($this->_redsys_mensajes[$code]) ? __($this->_redsys_mensajes[$code]) : __('Error no definido %1', $code);
    }

    public function getEncodeFields($fields)
    {
        return base64_encode(json_encode($fields));
    }

    public function getDecodeFields($fields)
    {
        $array = [];
        foreach (json_decode(base64_decode($fields)) as $k => $v) {
            $array[$k] = urldecode(strtr($v, '-_', '+/'));
        }
        return $array;
    }

    public function getEncodeSignature($order_id, $campos, $key)
    {
        $key = base64_decode($key);

        $cifrado = $this->encrypt_3DES($order_id, $key);

        if (is_array($campos) || is_object($campos)) {
            $campos = $this->getEncodeFields($campos);
        }
        $mac = hash_hmac('sha256', $campos, $cifrado, true);

        return base64_encode($mac);
    }

    public function encrypt_3DES($message, $key)
    {
        $l = ceil(strlen($message) / 8) * 8;
        return substr(openssl_encrypt($message . str_repeat("\0", $l - strlen($message)), 'des-ede3-cbc', $key, OPENSSL_RAW_DATA, "\0\0\0\0\0\0\0\0"), 0, $l);
        // return mcrypt_encrypt(MCRYPT_3DES, $key, $message, MCRYPT_MODE_CBC, implode(array_map("chr", [0, 0, 0, 0, 0, 0, 0, 0]))); // Deprecated php 7.0
    }

    public function getConvertedCurrency($cur)
    {
        $monedas = [
            'ADP' => '020', 'AED' => '784', 'AFA' => '004', 'ALL' => '008',
            'AMD' => '051', 'ANG' => '532', 'AOA' => '973', 'ARS' => '032',
            'AUD' => '036', 'AWG' => '533', 'AZM' => '031', 'BAM' => '977',
            'BBD' => '052', 'BDT' => '050', 'BGL' => '100', 'BGN' => '975',
            'BHD' => '048', 'BIF' => '108', 'BMD' => '060', 'BND' => '096',
            'BOB' => '068', 'BOV' => '984', 'BRL' => '986', 'BSD' => '044',
            'BTN' => '064', 'BWP' => '072', 'BYR' => '974', 'BZD' => '084',
            'CAD' => '124', 'CDF' => '976', 'CHF' => '756', 'CLF' => '990',
            'CLP' => '152', 'CNY' => '156', 'COP' => '170', 'CRC' => '188',
            'CUP' => '192', 'CVE' => '132', 'CYP' => '196', 'CZK' => '203',
            'DJF' => '262', 'DKK' => '208', 'DOP' => '214', 'DZD' => '012',
            'ECS' => '218', 'ECV' => '983', 'EEK' => '233', 'EGP' => '818',
            'ERN' => '232', 'ETB' => '230', 'EUR' => '978', 'FJD' => '242',
            'FKP' => '238', 'GBP' => '826', 'GEL' => '981', 'GHC' => '288',
            'GIP' => '292', 'GMD' => '270', 'GNF' => '324', 'GTQ' => '320',
            'GWP' => '624', 'GYD' => '328', 'HKD' => '344', 'HNL' => '340',
            'HRK' => '191', 'HTG' => '332', 'HUF' => '348', 'IDR' => '360',
            'ILS' => '376', 'INR' => '356', 'IQD' => '368', 'IRR' => '364',
            'ISK' => '352', 'JMD' => '388', 'JOD' => '400', 'JPY' => '392',
            'KES' => '404', 'KGS' => '417', 'KHR' => '116', 'KMF' => '174',
            'KPW' => '408', 'KRW' => '410', 'KWD' => '414', 'KYD' => '136',
            'KZT' => '398', 'LAK' => '418', 'LBP' => '422', 'LKR' => '144',
            'LRD' => '430', 'LSL' => '426', 'LTL' => '440', 'LVL' => '428',
            'LYD' => '434', 'MAD' => '504', 'MDL' => '498', 'MGF' => '450',
            'MKD' => '807', 'MMK' => '104', 'MNT' => '496', 'MOP' => '446',
            'MRO' => '478', 'MTL' => '470', 'MUR' => '480', 'MVR' => '462',
            'MWK' => '454', 'MXN' => '484', 'MXV' => '979', 'MYR' => '458',
            'MZM' => '508', 'NAD' => '516', 'NGN' => '566', 'NIO' => '558',
            'NOK' => '578', 'NPR' => '524', 'NZD' => '554', 'OMR' => '512',
            'PAB' => '590', 'PEN' => '604', 'PGK' => '598', 'PHP' => '608',
            'PKR' => '586', 'PLN' => '985', 'PYG' => '600', 'QAR' => '634',
            'ROL' => '642', 'RUB' => '643', 'RUR' => '810', 'RWF' => '646',
            'SAR' => '682', 'SBD' => '090', 'SCR' => '690', 'SDD' => '736',
            'SEK' => '752', 'SGD' => '702', 'SHP' => '654', 'SIT' => '705',
            'SKK' => '703', 'SLL' => '694', 'SOS' => '706', 'SRG' => '740',
            'STD' => '678', 'SVC' => '222', 'SYP' => '760', 'SZL' => '748',
            'THB' => '764', 'TJS' => '972', 'TMM' => '795', 'TND' => '788',
            'TOP' => '776', 'TPE' => '626', 'TRL' => '792', 'TRY' => '949',
            'TTD' => '780', 'TWD' => '901', 'TZS' => '834', 'UAH' => '980',
            'UGX' => '800', 'USD' => '840', 'UYU' => '858', 'UZS' => '860',
            'VEB' => '862', 'VND' => '704', 'VUV' => '548', 'XAF' => '950',
            'XCD' => '951', 'XOF' => '952', 'XPF' => '953', 'YER' => '886',
            'YUM' => '891', 'ZAR' => '710', 'ZMK' => '894', 'ZWD' => '716',
        ];
        if (isset($monedas[$cur])) {
            return $monedas[$cur];
        }
        return '';
    }

    public function getExcludeFieldsByTransactionType($transType)
    {
        switch ($transType) {
            case self::TRANSACTION_TYPE_AUTOMATIC_REFUND:
                return [
                    'Ds_Merchant_ProductDescription',
                    'Ds_Merchant_UrlOK', 'Ds_Merchant_UrlKO',
                    'Ds_Merchant_ConsumerLanguage', 'Ds_Merchant_SumTotal',
                    'Ds_Merchant_DateFrecuency', 'Ds_Merchant_ChargeExpiryDate',
                    'Ds_Merchant_AuthorisationCode', 'Ds_Merchant_TransactionDate',
                    'Ds_Merchant_MerchantSignature', 'Ds_SignatureVersion',
                ];
            case self::TRANSACTION_TYPE_PREAUTHORIZATION:
            case self::TRANSACTION_TYPE_AUTHORIZATION:
                return [
                    'Ds_Merchant_SumTotal',
                    'Ds_Merchant_DateFrecuency', 'Ds_Merchant_ChargeExpiryDate',
                    'Ds_Merchant_AuthorisationCode', 'Ds_Merchant_TransactionDate',
                    'Ds_Merchant_MerchantSignature', 'Ds_SignatureVersion',
                ];
        }
        return ['Ds_Merchant_MerchantSignature', 'Ds_SignatureVersion'];
    }

    public function getConvertedLocale($lan)
    {
        $langs = [
            'es_ES' => '001',
            'en_US' => '002', 'en_GB' => '002', 'en_AU' => '002',
            'ca_ES' => '003',
            'fr_FR' => '004',
            'de_DE' => '005',
        ];
        if (isset($langs[$lan])) {
            return $langs[$lan];
        }
        return '001';
    }

    public function getMaxString($string, $length = 0)
    {
        if ($length == 0) {
            return '';
        }

        if (strlen($string) > $length) {
            $string = substr($string, 0, $length);
        }

        return $string;
    }

    public function setRedsysResponse($object, $params)
    {
        try {
            $this->setPaymentAdditionalInfo($object, $params);
        } catch (\Exception $ex) {
            false;
        }
        return true;
    }

    public function getPaymentObject($object)
    {
        $payment = null;

        if ($object instanceof Order || $object instanceof Quote) {
            $payment = $object->getPayment();
        }

        if ($object instanceof Payment) {
            $payment = $object;
        }

        if (!$payment instanceof Payment) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid payment'));
        }

        return $payment;
    }

    public function setPaymentAdditionalInfo($object, $params)
    {
        $payment = $this->getPaymentObject($object);

        $info = $payment->getAdditionalData();
        if (empty($info)) {
            $info = [];
        } else {
            $info = unserialize($info);
        }

        if (!isset($info['redsys'])) {
            $info['redsys'] = [];
        }
        foreach ($params as $key => $value) {
            if ($key == 'order') {
                continue;
            }

            $info['redsys'][$key] = $value;
        }
        $info = serialize($info);

        $payment->setAdditionalData($info)->save();
        return $this;
    }
}
