<?php

/**
 * Copyright © Gento <desarrollo@gento.com.ar>, Inc. All rights reserved.
 */

namespace Gento\Redsys\Controller\Response;

use Magento\Framework\App\Action\Action;

class Ok extends Action
{
    public function execute()
    {
        $this->messageManager->addSuccess(__('Transaccion autorizada'));
        $this->_redirect('checkout/onepage/success/');
        return;
    }

}
