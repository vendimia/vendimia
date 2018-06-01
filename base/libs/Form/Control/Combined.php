<?php
namespace Vendimia\Form\Control;

use Vendimia;

/**
 * Combines several controls into one block.
 */
class Combined extends ControlAbstract
{

	protected $extra_properties = [
		'join' => ' ',              // Caracter(es) entre cada control
		'controls' => [],           // Nombre de los controles combinados.
    ];

	public function __construct( $name, $properties, $form ) {
		parent::__construct( $name, $properties, $form );

		// Este objeto no debe de retornar un valor
		$this->return_value = false;

        // Ocultamos todos sus controles
		foreach ( $this->controls as $ctrl ) {
			$res[] = $this->form->$ctrl->draw_control = false;
		}
	}

	public function draw()
	{
		$res = [];
		foreach ( $this->controls as $ctrl ) {
			$res[] = $this->form->$ctrl->draw();
		}

		return join ( $this->join, $res);
	}

	public function drawMessages()
	{

		// Añadimos todos los mensajes de sus controles
		foreach ($this->controls as $ctrl) {
			$this->addMessage ($this->form->$ctrl->messages);
		}

		// Llamamos al padre
		return parent::drawMessages();
	}/**/

	public function validate()
	{
		// Este control debe estar DESPUÉS que sus controles
		// combinados. De lo contrario, fallará.

		$valid = true;
		foreach ($this->controls as $ctrl) {
			// Si no está validado, sonamos
			if (!$this->form->$ctrl->validated )
				throw new DefinitionError ($this->name . ': Combined control must be after all its related controls.');

			if (!$this->form->$ctrl->valid)
				$valid = false;
		}

		return $valid;
	}
}
