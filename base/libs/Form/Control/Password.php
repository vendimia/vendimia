<?php
namespace Vendimia\Form\Control;

/**
 * Control for drawing password text boxes.
 */
class Password extends Text
{
    public function draw($extra_props = [])
    {
        return parent::draw([
            'type' => 'password',
            'value' => '',
        ]);
    }
}
