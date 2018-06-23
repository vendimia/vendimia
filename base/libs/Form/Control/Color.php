<?php
namespace Vendimia\Form\Control;

use Vendimia;

/**
 * HTML5 color picker
 */
class Color extends Text
{
    public function draw($extra_props = []) 
    {
        return parent::draw([
            'type' => 'color',
        ]);
    }

    public function getValue()
    {
        // Removemos el # inicial
        return substr($this->value, 1);
    }
}
