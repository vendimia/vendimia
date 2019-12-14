<?php
namespace Vendimia\Form\Control;

use Vendimia;

/**
 * Control for drawing text boxes
 */
class Text extends ControlAbstract
{
    protected $extra_properties = [
        /** Draws a hidden text tag. */
        'hidden'        => false,

        /** Draws a multiline input tag */
        'multiline'     => false, // Para TextField, true dibuja un textarea
    ];

    /**
     * Gemerate this control HTML.
     */
    public function draw($extra_props = []){
        // Propiedades del HTML por defecto
        $props = [
            'type' => 'text',
            'name' => $this->name,
            'id' => $this->id(),
        ];

        // Opciones para Html\tag
        $options = [];

        // Si no permite empty, añadimos un required
        if (!$this->empty_allowed) {
            $props['required'] = 'true';
        }

        $content = null;

        if ($this->multiline) {
            $tag = 'textarea';
            $options = [
                'escapecontent' => false,
                'closetag' => true,
            ];
            $content = $this->value;
        } else {
            $tag = 'input';
            $props['value'] = $this->value;

            // Es un hidden?
            if ( $this->hidden ) {
                // Cambiamos su tipo
                $props['type'] = 'hidden';
            }
        }

        // Le añadimos las propiedades extras, que puede venir de un
        // control heredado
        $props = array_replace($props, $extra_props);

        return $this->htmltag($tag, $props, $content, $options);
    }

    /**
	 * Dibuja el HTML para este control.
	 */
	/*function draw(array $extra_vars = []) {
        $options = [];

        $vars = [
            'type' => 'text',
            'name' => $this->name,
            'id' => $this->id(),
        ];


        // Si no puede se empty, le añadimos un required
        if (  ! $this->empty ) {
            $vars['required'] = true;
        }

        // es un multiline?
        $content = false;
        if ( $this->multiline ) {
            $tag = 'textarea';
            $options = [
                'escapecontent' => false,
                'closetag' => true,
            ];
            $content = $this->value;
        }
        else {
            $tag = 'input';
            $vars ['value'] = $this->value;
        }

        // El control está escondido
        if ( $this->hidden ) {
            // Cambiamos su tipo
            $vars['type'] = 'hidden';
        }

        // Si hay extra_vals, las mezclamos
        if ( $extra_vars ) {
            $vars = array_merge ( $vars, $extra_vars );
        }


        return $this->htmltag ( $tag, $vars, $content, $options );
	}*/


    /* Si el textbox es hidden, no dibujamos ni label, ni messages  */

    function drawLabel( $add_sufix = true ) {
        if ( $this->hidden ) {
            return '';
        }

        return parent::drawLabel();
    }

    function drawMessages() {
        // Si el control es de tipo 'hidden', entonces no dibujamos label
        if ( $this->hidden ) {
            return '';
        }

        return parent::drawMessages();
    }
}
