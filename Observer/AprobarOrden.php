<?php

/**
 * Copyright © Gento <desarrollo@gento.com.ar>, Inc. All rights reserved.
 */

namespace Gento\Redsys\Observer;

use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Psr\Log\LoggerInterface;

class AprobarOrden implements ObserverInterface
{
    public function __construct(
        TransactionFactory $transactionFactory,
        LoggerInterface $logger,
        InvoiceSender $invoiceSender
    ) {
        $this->logger = $logger;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceSender = $invoiceSender;
    }

    public function execute(Observer $observer)
    {
        /** @var \Gento\Redsys\Model\Response */
        $response_data = $observer->getData('response_data');

        /** @var \Magento\Sales\Model\Order */
        $order = $observer->getData('order');

        /** @var \Magento\Sales\Model\Order\Payment */
        $payment = $order->getPayment();

        /** @var \Gento\Redsys\Model\Payment\Redsys */
        $method = $payment->getMethodInstance();

        $autoinvoice = (bool) $method->getAutoInvoice();
        if ($autoinvoice) {
            try {
                /** @var \Magento\Sales\Model\Order\Invoice */
                $invoice = $order->prepareInvoice();

                $invoice->register();
                if ($method->canCapture()) {
                    $invoice->capture();
                }

                $order->addRelatedObject($invoice);

                $saveTransaction = $this->transactionFactory->create();
                $saveTransaction->addObject($invoice)->addObject($invoice->getOrder())->save();

                $order->addStatusHistoryComment(__('Factura %1 creada', $invoice->getIncrementId()));
                $invoice->setTransactionId($response_data->getField('Ds_AuthorisationCode', __('No Recibido')))
                    ->save();
                $order->save();

                $notified = (bool) $method->getSendEmailOrderConfirmation();
                if ($notified) {
                    try {
                        $this->invoiceSender->send($invoice);
                    } catch (\Exception $e) {
                        $this->logger->critical($e);
                        $order->addStatusHistoryComment(__('No se pudo enviar el aviso %1', $e->getMessage()));
                        $order->save();
                    }
                }
            } catch (\Exception $exc) {
                $order->addStatusHistoryComment(__('Ocurrio un error. Por favor, contactese con el desarrollador.'));
                $order->save();
            }
        }

        return $this;
    }
}
