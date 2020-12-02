<?php

/**
 * Copyright © Gento <desarrollo@gento.com.ar>, Inc. All rights reserved.
 */

namespace Gento\Redsys\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Entorno implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 2, 'label' => __('Pruebas'), 'url' => 'https://sis-t.redsys.es:25443/sis/'],
            ['value' => 3, 'label' => __('Real'), 'url' => 'https://sis.redsys.es/sis/'],
        ];
    }

    public function toArray()
    {
        return [2 => __('Pruebas'), 3 => __('Real')];
    }

    public function getById($id)
    {
        foreach ($this->toOptionArray() as $data) {
            if ($data['value'] == $id) {
                return (object) $data;
            }

        }
        return null;
    }
}
