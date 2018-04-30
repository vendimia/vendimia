<?php
namespace Vendimia\Form\Control;

class Number extends Text
{
    function draw($extra_props = []) {
        return parent::draw([
            'type' => 'number',
            'value' => $this->value,
        ]);
    }

}
