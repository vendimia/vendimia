<?php
namespace Vendimia\Form\Control;

class TelNumber extends Text
{
    function draw($extra_props = []) {
        return parent::draw([
            'type' => 'tel',
            'value' => $this->value,
        ]);
    }

}
