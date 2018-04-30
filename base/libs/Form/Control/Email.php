<?php
namespace Vendimia\Form\Control;

use Vendimia;
use Vendimia\Form;
use Vendimia\Form\Validator;

class Email extends Text
{
    const REGEXP_EMAIL = '^[0-9A-Za-z\-_\!\.]+@[0-9A-Za-z\-]+\.[0-9A-Za-z\.\-]*[0-9A-Za-z]$';

	function __construct( $name, $properties, $form ) {
		parent::__construct( $name, $properties, $form );

		// Activamos el filtro de regexp
		$this->validators = array_merge($this->validators, [
            [Validator\RegExp::class, 'regexp' => self::REGEXP_EMAIL, 'case_insensitive' => true]
        ]);
	}

	public function draw($extra_props = []) {
        return parent::draw([
            'type' => 'email',
        ]);
    }
}
