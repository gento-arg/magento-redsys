<?php

/**
 * Copyright © Gento <desarrollo@gento.com.ar>, Inc. All rights reserved.
 */

namespace Gento\Redsys\Controller\Form;

use Magento\Checkout\Model\Session;
use Gento\Redsys\Model\Payment\Redsys;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Gento\Redsys\Model\Payment\RedsysFactory;
use Magento\Framework\Controller\Result\JsonFactory;

class Redirect extends Action
{
    public function __construct(
        Context $context,
        RedsysFactory $redsysPaymentFactory,
        Session $checkoutSession,
        JsonFactory $resultJsonFactory
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->redsysPaymentFactory = $redsysPaymentFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        if ($this->getRequest()->isAjax()) {
            /** @var Redsys $redsys */
            $redsys = $this->redsysPaymentFactory->create();
            $order = $this->_checkoutSession->getLastRealOrder();

            $redsys->setOrder($order)
                ->setOrderId($order->getRealOrderId());

            $test = [
                'url' => $redsys->getUrlRealizarPago(),
                'parametros' => $redsys->getEncodeCheckoutFormFields(),
                'firma' => $redsys->getEncodeSignature(),
            ];
            return $result->setData($test);
        }
    }

}
