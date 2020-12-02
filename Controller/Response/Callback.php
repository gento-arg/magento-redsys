<?php

/**
 * Copyright © Gento <desarrollo@gento.com.ar>, Inc. All rights reserved.
 */

namespace Gento\Redsys\Controller\Response;

use Gento\Redsys\Model\Payment\Redsys;
use Gento\Redsys\Model\Payment\RedsysFactory;
use Gento\Redsys\Model\Response;
use Gento\Redsys\Model\ResponseFactory;
use Gento\Redsys\Model\Tool;
use Gento\Redsys\Model\ToolFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Event\Manager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;

class Callback extends Action
{

    protected $_order = null;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        Context $context,
        CollectionFactory $orderCollectionFactory,
        ToolFactory $paymentTool,
        ResponseFactory $responseFactory,
        Manager $eventManager,
        LoggerInterface $logger,
        RedsysFactory $redsysPaymentFactory
    ) {
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        $this->paymentTool = $paymentTool->create();
        $this->redsysPaymentFactory = $redsysPaymentFactory;
        $this->responseFactory = $responseFactory;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        /** @todo Si no es POST deberia redireccionar o morir en un mensaje */

        /** @var Order */
        $order = $this->_loadOrder();

        /** @var Payment */
        $payment = $order->getPayment();

        /** @var Redsys */
        $method = $payment->getMethodInstance();

        $postParameters = $this->getRequest()->getPost('Ds_MerchantParameters');
        $signature = $this->getRequest()->getPost('Ds_Signature');
        $response_data = $this->_processParams($order, $postParameters, $signature);

        $params = $response_data->getData();

        if (!$response_data->checkAmount()) {
            $response_data->setAutorizado(false);
        }

        $this->paymentTool->setRedsysResponse($payment, $params);

        if ($response_data->isAutorizado()) {
            $event = 'gento_redsys_orden_autorizada';
            if ($response_data->getField('DS_TRANSACTIONTYPE') == Tool::TRANSACTION_TYPE_PREAUTHORIZATION) {
                $event = 'gento_redsys_orden_preautorizada';
            }
            $this->eventManager->dispatch($event, [
                'response_data' => $response_data,
                'order' => $order,
            ]);
        } elseif ($response_data->isProcesado()) {
            $enviarMail = (bool) $method->getSendEmailOrderConfirmation();

            $comentario = __('La orden fue cancelada desde Redsys');
            $comentario .= '<br />' . __($response_data->getComentario(), $response_data->getCodigo());
            $comentario .= '<br />' . __('Codigo: %1', $response_data->getCodigo());

            $order->cancel();
            $order->addStatusToHistory('redsys_ko_payment', $comentario, true);
            $order->save();

            if ($enviarMail) {
                $order->sendOrderUpdateEmail(true, $comentario);
            }
        } else {
            $mensaje = __('La orden #%1 no ha podido ser procesada', $order->getRealOrderId());
        }
        return $mensaje;
    }

    protected function _loadOrder()
    {
        $protect_code = $this->_getProtectCode();

        if ($this->_order == null) {
            if (!$protect_code) {
                throw new LocalizedException(__('Codigo protegido invalido'));
            }
            $this->_order = $this->_orderCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('protect_code', $protect_code)
                ->getLastItem();
        }

        if ($this->_order == null || !$this->_order->getId()) {
            throw new LocalizedException(__('Orden invalida con codigo %1', $protect_code));
        }
        return $this->_order;
    }

    protected function _getProtectCode()
    {
        return $this->getRequest()->getParam(Redsys::PROTECT_CODE_QUERY_PARAM);
    }

    /**
     * @return Response
     */
    public function _processParams(Order $order, $postParameters, $signature)
    {
        /** @var Response */
        $response_data = $this->responseFactory->create();
        $response_data->setOrder($order)
            ->setMerchantParameters($postParameters)
            ->setSignature($signature);

        if (!$response_data->firmaValida()) {
            $response_data->setData([
                'procesado' => true,
                'autorizado' => false,
                'comentario' => __('Firma invalida'),
            ]);
        } else {
            $comment = null;
            $autorizada = true;
            $response = $response_data->getField('Ds_Response');
            $payMethod = $response_data->getField('Ds_PayMethod');
            $monto = ((int) $response_data->getField('Ds_Amount')) / 100;

            if ($response >= '0000' && $response <= '0099') {
                $comment = __('Transaccion autorizada para pagos y preautorizaciones (codigo: %1)');
            } elseif ($response == '0900') {
                $comment = __('Transaccion autorizada para devoluciones y confirmaciones (codigo: %1)');
            } elseif ($response == '0930') {
                if ($payMethod == 'R') {
                    $comment = __('Pago realizado por Transferencia bancaria');
                } else {
                    $comment = __('Pago realizado por Domiciliacion bancaria');
                }
            } else {
                $autorizada = false;
                $comment = $this->comentarioReponse($response, $payMethod);
            }

            $response_data->addData([
                'procesado' => true,
                'autorizado' => $autorizada,
                'comentario' => $comment,
                'codigo' => $response,
                'monto' => $monto,
            ]);
        }
        return $response_data;
    }

    public function comentarioReponse($Ds_Response, $Ds_pay_method = '')
    {
        switch ($Ds_Response) {
            case '101':
                return __('Tarjeta caducada');
            case '102':
                return __('Tarjeta en excepcion transitoria o bajo sospecha de fraude');
            case '104':
                return __('Operacion no permitida para esa tarjeta o terminal');
            case '106':
                return __('Intentos de PIN excedidos');
            case '116':
                return __('Disponible insuficiente');
            case '118':
                return __('Tarjeta no registrada');
            case '125':
                return __('Tarjeta no efectiva');
            case '129':
                return __('Codigo de seguridad (CVV2/CVC2) incorrecto');
            case '180':
                return __('Tarjeta ajena al servicio');
            case '184':
                return __('Error en la autenticacion del titular');
            case '190':
                return __('Denegacion sin especificar Motivo');
            case '191':
                return __('Fecha de caducidad erronea');
            case '202':
                return __('Tarjeta en excepcion transitoria o bajo sospecha de fraude con retirada de tarjeta');
            case '904':
                return __('Comercio no registrado en FUC');
            case '909':
                return __('Error de sistema');
            case '912':
            case '9912':
                return __('Emisor no disponible');
            case '0930':
                if ($Ds_pay_method == 'R') {
                    return __('Realizado por Transferencia bancaria');
                } else {
                    return __('Realizado por Domiciliacion bancaria');
                }
            case '950':
                return __('Operacion de devolucion no permitida');
            case '9064':
                return __('Numero de posiciones de la tarjeta incorrecto');
            case '9078':
                return __('Tipo de operacion no permitida para esa tarjeta');
            case '9093':
                return __('Tarjeta no existente');
            case '9218':
                return __('El comercio no permite op. seguras por entrada /operaciones');
            case '9997':
                return __('Se esta procesando otra transaccion en SIS con la misma tarjeta');
            case '9998':
                return __('Operacion en proceso de solicitud de datos de tarjeta');
            case '9999':
                return __('Operacion que ha sido redirigida al emisor a autenticar');
            case '9253':
                return __('Tarjeta no cumple el check-digit');
            case '9256':
                return __('El comercio no puede realizar preautorizaciones');
            case '9257':
                return __('Esta tarjeta no permite operativa de preautorizaciones');
            case '9261':
                return __('Operacion detenida por superar el control de restricciones en la entrada al SIS');
            case '9913':
                return __('Error en la confirmacion que el comercio envia al TPV Virtual');
            case '9914':
                return __('Confirmacion "KO" del comercio');
            case '9928':
                return __('Anulacion de autorizacion en diferido realizada por el SIS (proceso batch)');
            case '9929':
                return __('Anulacion de autorizacion en diferido realizada por el comercio');
            case '9104':
                return __('Comercio con "titular seguro" y titular sin clave de compra segura');
            case '9915':
                return __('A peticion del usuario se ha cancelado el pago');
            case '9094':
                return __('Rechazo servidores internacionales');
            case '944':
                return __('Sesion Incorrecta');
            case '913':
                return __('Pedido repetido');
        }
        return __('Transaccion denegada');
    }
}
