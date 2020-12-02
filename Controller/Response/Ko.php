<?php

/**
 * Copyright © Gento <desarrollo@gento.com.ar>, Inc. All rights reserved.
 */

namespace Gento\Redsys\Controller\Response;

use Magento\Framework\App\Action\Action;

class Ko extends Action
{
    public function execute()
    {
        $this->messageManager->addError(__('Transaccion denegada desde Redsys'));
        $this->_redirect('checkout/cart');
        return;
    }

}
