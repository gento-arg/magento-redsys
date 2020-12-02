<?php
/**
 * Copyright © Gento <desarrollo@gento.com.ar>, Inc. All rights reserved.
 */

namespace Gento\Redsys\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class InstallData implements InstallDataInterface
{
    /**
     * Sales setup factory
     *
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * @param SalesSetupFactory $salesSetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /**
         * Install order statuses from config
         */
        $data = [];
        $statuses = [
            'redsys_pending_payment' => __('Redsys - Pendiente de pago'),
            'redsys_ok_payment' => __('Redsys - Pago realizado'),
            'redsys_ko_payment' => __('Redsys - Pago invalido'),
        ];
        foreach ($statuses as $code => $info) {
            $data[] = ['status' => $code, 'label' => $info];
        }
        $setup->getConnection()->insertArray($setup->getTable('sales_order_status'), ['status', 'label'], $data);

        /**
         * Install order states from config
         */
        $data = [];
        $states = [
            'pending_payment' => [
                'label' => __('Redsys - Pendiente de pago'),
                'statuses' => ['redsys_pending_payment' => ['default' => '0']],
            ],
            'processing' => [
                'label' => __('Redsys - Pago realizado'),
                'statuses' => ['redsys_ok_payment' => ['default' => '0']],
            ],
            'canceled' => [
                'label' => __('Redsys - Pago invalido'),
                'statuses' => ['redsys_ko_payment' => ['default' => '0']],
            ],
        ];

        foreach ($states as $code => $info) {
            if (isset($info['statuses'])) {
                foreach ($info['statuses'] as $status => $statusInfo) {
                    $data[] = [
                        'status' => $status,
                        'state' => $code,
                        'is_default' => is_array($statusInfo) && isset($statusInfo['default']) ? 1 : 0,
                    ];
                }
            }
        }
        $setup->getConnection()->insertArray(
            $setup->getTable('sales_order_status_state'),
            ['status', 'state', 'is_default'],
            $data
        );

        /** Update visibility for states */
        $states = ['redsys_pending_payment', 'redsys_ok_payment', 'redsys_ko_payment'];
        foreach ($states as $state) {
            $setup->getConnection()->update(
                $setup->getTable('sales_order_status_state'),
                ['visible_on_front' => 1],
                ['state = ?' => $state]
            );
        }
    }
}
