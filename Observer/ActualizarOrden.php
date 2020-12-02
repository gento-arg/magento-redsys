<?php

/**
 * Copyright © Gento <desarrollo@gento.com.ar>, Inc. All rights reserved.
 */

namespace Gento\Redsys\Observer;

use Gento\Redsys\Model\Payment\Redsys;
use Gento\Redsys\Model\Response;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSenderFactory;
use Magento\Sales\Model\Order\Payment;

class ActualizarOrden implements ObserverInterface
{

    /** @var OrderCommentSender */
    protected $orderCommentSender;

    public function __construct(
        OrderCommentSenderFactory $orderCommentSender
    ) {
        $this->orderCommentSender = $orderCommentSender;
    }

    public function execute(Observer $observer)
    {
        try {
            /** @var Response */
            $response_data = $observer->getData('response_data');

            /** @var Order */
            $order = $observer->getData('order');

            /** @var Payment */
            $payment = $order->getPayment();

            /** @var Redsys */
            $method = $payment->getMethodInstance();

            $trId = $response_data->getField('Ds_AuthorisationCode', __('No Recibido'));
            $comentario = __($response_data->getComentario(), $response_data->getCodigo());
            $comentario .= '<br />' . __('Numero de transaccion: %1', $trId);
            $comentario .= '<br />' . __('Monto: %1', $response_data->getData('monto'));
            $comentario .= '<br />' . __('Orden: %1', $response_data->getField('DS_ORDER'));
            $comentario .= '<br />' . __('Protect Code: %1', $order->getProtectCode());

            $notified = (bool) $method->getSendEmailOrderConfirmation();
            $notified = false;

            $order->addStatusToHistory('redsys_ok_payment', $comentario, $notified);
            $order->setTransactionId($trId);
            $order->save();

            if ($notified) {
                try {
                    $orderCommentSender = $this->orderCommentSender->create();
                    $orderCommentSender->send($order, $notified, $comentario);
                } catch (\Exception $e) {
                    $order->addStatusHistoryComment(__('No se pudo enviar el aviso %1', $e->getMessage()));
                    $order->save();
                }
            }
        } catch (\Exception $e) {
        }

        return $this;
    }
}
