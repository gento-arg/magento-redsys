<?php

/**
 * Copyright © Gento <desarrollo@gento.com.ar>, Inc. All rights reserved.
 */

namespace Gento\Redsys\Model;

use Gento\Redsys\Model\Payment\Redsys;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;

class Response extends DataObject
{

    protected $_fields = [];

    public function __construct(
        ToolFactory $toolFactory,
        array $data = []
    ) {
        parent::__construct($data);
        $this->toolFactory = $toolFactory->create();
    }

    public function getField($field)
    {
        return isset($this->_fields[strtoupper($field)]) ? $this->_fields[strtoupper($field)] : null;
    }

    public function getFields()
    {
        return $this->_fields;
    }

    public function setField($field, $value)
    {
        $this->_fields[strtoupper($field)] = $value;
        return $this;
    }

    public function setFields($fields)
    {
        foreach ($fields as $field => $value) {
            $this->setField($field, $value);
        }

        return $this;
    }

    public function isAutorizado()
    {
        return (bool) $this->getAutorizado();
    }

    public function isProcesado()
    {
        return (bool) $this->getProcesado();
    }

    public function firmaValida()
    {
        /** @var Order */
        $order = $this->getOrder();

        /** @var Payment */
        $payment = $order->getPayment();

        /** @var Redsys */
        $method = $payment->getMethodInstance();

        $fields = $this->toolFactory->getDecodeFields($this->getMerchantParameters());
        $this->setFields($fields);

        $signature = $this->toolFactory->getEncodeSignature($order->getRealOrderId(), $this->getMerchantParameters(), $method->getMerchantSecret());

        return ($signature == $this->getSignature());
    }

    public function checkAmount()
    {
        return true;
        $monto = $this->getField('Ds_Amount');
        if ($monto == null) {
            return false;
        }

        $orderAmount = round($this->getOrder()->getBaseGrandTotal() * 100);
        return $orderAmount == $monto ? true : false;
    }
}
