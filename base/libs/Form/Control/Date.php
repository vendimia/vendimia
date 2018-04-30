<?php
namespace Vendimia\Form\Control;

use Vendimia;

/**
 * Campo de Fecha. Dibuja un tag INPUT con type="date".
 */
class Date extends Text
{
    public function draw($extra_props = []) 
    {
        return parent::draw([
            'type' => 'date',
        ]);
    }

    /**
     * This controls returns a Vendimia\DateTime object
     */
    public function getValue()
    {
        $value = parent::getValue();
        if ($value) {
            return new Vendimia\DateTime($value);
        } else {
            return null;
        }
    }
}
