<?php
namespace Vendimia\Form\Control;

use Vendimia;
use Vendimia\DateTime;

/**
 * Campo de Hora. Dibuja un tag INPUT con type="time".
 */
class Time extends Text
{
    public function draw($extra_props = [])
    {
        return parent::draw([
            'type' => 'time',
        ]);
    }

    /**
     * This controls returns a Vendimia\Time object
     */
    public function getValue()
    {
        $value = parent::getValue();
        if ($value) {
            return new DateTime\Time($value);
        } else {
            return null;
        }
    }

}
