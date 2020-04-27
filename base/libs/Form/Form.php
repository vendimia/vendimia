<?php

namespace Vendimia\Form;

use Vendimia;
use Vendimia\Html;
use Vendimia\CsrfInterface;
use Vendimia\AsArrayInterface;
use ReflectionClass;
use ReflectionProperty;

/**
 * Class for manipulate, validate and draw a HTML <FORM> tag, and all its
 * related data HTML elements.
 *
 * Using the Form::draw() method, all the form controls are rendered in this
 * fashion:
 *
 * /-------control_block----------\
 * | /-------label_block--------\ |
 * | |     [label <label>]      | |
 * | |      [info (info)]       | |
 * | \--------------------------/ |
 * | /-------widget_block-------\ |
 * | |     [widget (widget)]    | |
 * | |                          | |
 * | | /-----message_block----\ | |
 * | | | [message (message1)] | | |
 * | | | [message (message2)] | | |
 * | | \----------------------/ | |
 * | \--------------------------/ |
 * \------------------------------/
 *
 * @author Oliver Etchebarne
 */
class Form implements AsArrayInterface
{
    /** This form ID */
    private $id;

    /** This form controls list */
    private $controls = [];

    /** Generic value holder, used for some controllers */
    private $value_holders = [];

    /** Javascript scripts, drawn at the end of the form */
    private $scripts = '';

    /** Iterator control name */
    private $current_control_name;

    /** Iterator control value */
    private $current_control_obj;

    /** Validation status */
    private $valid = null;

    /** All the control error menssages */
    private $messages = [];

    /** CSRF object */
    private $csrf;

    /** This form CSRF token */
    private $form_csrf;

    /** Array with drawn controls by the draw() method */
    private $drawn_controls;

    /** Validating status */
    private $validated = false;

    /** Default property values */
    private $properties = [
        /** HTML properties */
        'html' => [
            /** Tag surrounding all the control block. Only used in self::draw() */
            'control_block' => ['div'],

            /** Tag surrounding the label block */
            'label_block' => [],

            /** Tag surrounding the label tag*/
            'label' => [],

            /** Tag surrounding the extra information */
            'info' => ['div', 'class' => 'vendimia_form_info'],

            /** Tag surrounding the widget block. Only used in control::drawWidget() */
            'widget_block' => [],

            /** Tag surrounding the widget */
            'widget' => [],

            /** Tag surrounding the message block */
            'message_block' => ['div', 'class' => 'vendimia_form_messageblock'],

            /** Tag surrouding each message */
            'message' => ['div'],

            /** Prefix for the control's id tag value */
            'id_prefix' => '',

            /** Prefix for the message block ID */
            'message_block_id_prefix' => 'messageblock_'
        ],

        /**
         * Prefijo para el ID del tag del bloque de mensajes.
         *
         * Se concatenará con el ID del control.
         */
        'html_prefix_messageblock_id' => 'vendimia_form_messages_',

        /** Sufix to append to the label */
        'label_sufix' => ':',

        /** Method in this form to extra validation */
        'validation_callback' => null,

        /** Disable CSRF verification */
        'disable_csrf' => false,

        /** Flag to send the 'html' properties to the browser */
        'ajax_sendproperties' => false,

    ];

    /**
     * Constructor.
     */
    public function __construct(CsrfInterface $csrf, $id = null)
    {
        $this->csrf = $csrf;
        $this->id = $id;

        $refl = new ReflectionClass($this);
        $props = $refl->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($props as $prop) {
            $control = $prop->getName();
            $value = $prop->getValue($this);

            // Las propiedades son las propiedades estáticas
            if ($prop->isStatic()) {

                if (is_array($value)) {
                    if (!isset($this->properties[$control])) {
                        throw new \RuntimeException( "Unknow '$control' property." );
                    }
                    $this->properties[$control] = array_replace_recursive(
                        $this->properties[$control],
                        $value
                    );
                } else {
                    $this->properties[$control] = $value;
                }
                continue;
            }

            if (is_string($value)) {
                $value = [$value];
            }

            if (!isset($value[0])) {
                throw new \RuntimeException( "Field '$control' has no type." );
            }

            $class_name = $value[0];
            unset ($value[0]);

            if (!class_exists ($class_name)) {
                throw new Vendimia\Exception("Form field class '$class_name' unknow.");
            }

            // Creamos un nuevo objeto
            $this->$control = new $class_name(
                $control,
                $value,
                $this
            );
            $this->controls[] = $control;
        }
    }
    /*public function __construct(array $controls, $csrf_token, $id = null)
    {
        $this->system_csrf = $csrf_token;
        $this->id = $id;

        foreach ($controls as $control => $fielddef) {
            // Si empieza con @, es una propiedad.
            if ($control[0] == '@') {
                $property_name = $substr($control, 1);
            }

            // Si $data es un string, lo convertimos a array. Esto es
            // para un shortcut si quieres definir un field, pero sin
            // parámetros,

            if (is_string($fielddef)) {
                $fielddef = [$fielddef];
            }
            // No puede ser vacío
            if (!isset ($fielddef[0])) {
                throw new DefinitionError ( "'$name' field has no type." );
            }

            // El elemento 0 de $data es el tipo, que es una clase
            $class = $fielddef[0];
            unset ($fielddef[0]);

            // Existe la clase? Probamos para enviar una excepcion si no.
            if (!class_exists ($class)) {
                throw new Vendimia\Exception ( "Form field class '$class' unknow for '$name'.");
            }

            // Creamos un nuevo objeto
            $this->controls[$control] = new $class (
                $control,
                $fielddef,
                $this
            );
        }

        // Mezclamos las propiedades
        $this->properties = array_replace_recursive($this->base_properties, $this->properties);
    }*/

    /**
     * Sets values to all controls
     *
     * @param array|Vendimia\AsArrayInterface $values Associative array with
     * the control's values.
     */
    public function setValues($values)
    {
        // Veriricamos si $values es un model
        if (is_object($values)) {
            if ($values instanceof Vendimia\AsArrayInterface) {
                $values = $values->asArray();
            } else {
                throw new \RuntimeException("Object can't be used as form values.");
            }
        }

        // El campo __VENDIMIA_TOKEN es especial
        $key = '__VENDIMIA_CSRF_TOKEN';
        if (key_exists($key, $values)) {
            $this->form_csrf = $values[$key];
            unset ($values[$key]);
        }

        // Buscamos valores para cada control
        foreach ($this->controls as $control) {
            if (key_exists($control, $values)) {
                $value = $values[$control];
            } else {
                $value = null;
            }
            $this->{$control}->setValue($value);
        }
    }

    /**
     * Returns an array with the form control values.
     *
     * @return array
     */
    public function getValues()
    {
        $res = [];
        foreach ($this->controls as $control) {
            $control = $this->$control;

            if (!$control->enabled) {
                continue;
            }

            // Si el control tiene field_name, lo usamos en vez de su nombre
            if ($control->field_name) {
                $fieldname = $control->field_name;
            } else {
                $fieldname = $control->name;
            }

            if ($control->return_value) {
                $res[$fieldname] = $control->getValue();
            }
        }

        return $res;
    }

    /**
     * Draws the entire form, using the HTML properties
     *
     * This function takes arguments, in which case it will draw
     * only the specified controls.
     *
     * With no arguments, draws the surrounding control block, the
     * csrf token tag, and the related scripts.
     *
     * @return string HTML of the form.
     */
    public function draw(...$control_list)
    {
        $html = '';

        // Si no especificamos qué controles dibujar, dibujamos todo
        // y activamos otros mecanismos
        if (!$control_list) {
            $control_list = $this->controls;
            $draw_scripts = true;

            // Empezamos con el CSRF
            $html .= $this->drawCsrf();
        }
        $draw_control_block = count($control_list) > 1;

        foreach ($control_list as $cname) {
            if (!in_array($cname, $this->controls)) {
                throw new \RuntimeException("Control '$cname' undefined.");
            }

            // Si ya dibujamos, no redibujamos.
            if (isset($this->drawn_controls[$cname])) {
                continue;
            }

            $control = $this->$cname;

            if (!$control->enabled) {
                continue;
            }
            if (!$control->draw_control) {
                continue;
            }

            //  Tiene un callback para dibujar?
            if (is_callable($control->drawer_callback)) {
                $callback = $control->drawer_callback;
                $html .= $callback($control);
                continue;
            }


            // Dibujamos las partes
            $widget = $control->draw();
            $info = $control->drawInfo();
            $messages = $control->drawMessages();
            $label = $control->drawLabel();

            // Verificamos el tag para rodear del widget
            foreach(['widget'] as $type) {
                if ($this->properties['html'][$type]) {

                    // No imprimimos partes vacías
                    if ($$type == '') {
                        continue;
                    }

                    $tag = $this->properties['html'][$type];
                    if (is_string($tag)) {
                        $tag = [$tag];
                    }
                    // Colocamos el contenido
                    $tag[2] = $$type;

                    // Y lo convertimos en un tag HTML
                    $$type = Html\Tag::create($tag)
                        ->noEscapeContent()
                        ->get();
                }
            }

            $label_block = $label . $info;
            $widget_block = $widget . $messages;

            // Verificamos los tags HTML para encerrar a cada bloque
            foreach (['label', 'widget'] as $type) {
                $type = "{$type}_block";
                if ($this->properties['html'][$type]) {
                    $tag = $this->properties['html'][$type];
                    if (is_string($tag)) {
                        $tag = [$tag];
                    }
                    // Colocamos el contenido
                    $tag[2] = $$type;

                    // Y lo convertimos en un tag HTML
                    $$type = Html\Tag::create($tag)
                        ->noEscapeContent()
                        ->get();
                }
            }

            // Unimos todo
            $html_control = $label_block . "\n" . $widget_block;
            if ($draw_control_block) {
                $tag = $this->properties['html']['control_block'];
                $tag[2] = $html_control;
                $html_control = Html\Tag::create($tag)
                    ->noEscapeContent()
                    ->get();
            }

            $html .= $html_control . "\n\n";

            // Registramos el control como dibujado
            $this->drawn_controls[$cname] = true;
        }

        return $html;
    }

    /**
     * Draw this form with <tr>, <th> y <td>
     */
    public function drawAsTable(...$control_list)
    {
        $this->properties['html']['control_block'] = ['tr'];
        $this->properties['html']['label_block'] = ['th'];
        $this->properties['html']['widget_block'] = ['td'];

        return $this->draw(...$control_list);
    }

    /**
     * Draws the <FORM> open tag with POST method
     */
    public function begin($method = 'post', $extra = [])
    {
        if (is_array($method)) {
            $extra = $method;
            $method = 'post';
        }
        $tag = Html\Tag::form($extra)
            ->onlyOpenTag();

        $tag['method'] = $method;
        return $tag->get() . "\n";
    }

    /**
     * Draws the </FORM> tag
     */
    public function end()
    {
        return "</form>\n";
    }

    /**
     * Draws a <BUTTON type 'submit'
     */
    public function drawSubmit($content = 'Submit form', $extra = [])
    {

        $extra['type'] = 'submit';
        return Html\tag::button($extra, $content)->get();
    }

    /**
     * Draws the complete HTML form
     */
    public function drawFull($submit_label = 'Submit form')
    {
        return $this->begin()
                . $this->draw()
                . $this->drawSubmit($submit_label)
                . $this->end();
    }


    /**
     * Properties getter
     */
    public function getProperty($property)
    {
        if (key_exists($property, $this->properties)) {
            return $this->properties[$property];
        } else {
            return $property;
        }
    }
    /**
     *  Get HTML Properties
     */
    public function getHtmlProperty($property)
    {
        if (key_exists($property, $this->properties['html'])) {
            return $this->properties['html'][$property];
        } else {
            return $property;
        }
    }

    /**
     * Returns the <INPUT tag with the CSRF token
     */
    public function drawCsrf()
    {
        return Html\Tag::input([
            'type' => 'hidden',
            'name' => '__VENDIMIA_CSRF_TOKEN',
            'value' => $this->csrf->getToken(),
        ]) . PHP_EOL;
    }

    /**
     * Valids all the controls and returns.
     */
    public function isValid()
    {
        // Si ya está validado, no validamos de nuevo.
        if ($this->validated) {
            return $this->valid;
        }

        // Validamos el CSRF
        if (!$this->getProperty('disable_csrf')) {
            if (!isset($this->form_csrf) ||
                $this->form_csrf != $this->csrf->getToken()) {

                return false;
            }
        }


        // Por defecto, pensamos que el formulario es valido
        $form_valid = true;

        foreach ($this->controls as $cname) {
            $control = $this->$cname;

            if (!$control->enabled) {
                continue;
            }

            if (!$control->validate) {
                continue;
            }

            // Hay una callback de validación?
            $callback_validator = $control->validator_callback;
            if (is_callable($callback_validator)) {
                $result = $callback_validator($control);

                if ($result === true) {
                    $control->valid = true;
                } else {
                    // Falló el callback. Si viene un string, lo usamos como
                    // mensaje de error
                    if (!$result) {
                        $result = 'Validator function for control "%C" failed';
                    }
                    $control->addMessage($result);
                    $form_valid = false;
                }
            } else {
                // Usamos el validador por defecto.
                $result = $control->validate();
                if (!$result) {
                    $form_valid = false;
                }
            }

            // Marcamos el control como validado
            $control->validated = true;
        }

        // Llamamos al validador global
        if ($form_valid) {
            $callback_validator = $this->getProperty('validation_callback');
            if ($callback_validator) {
                if (method_exists($this, $callback_validator)) {
                    $form_valid = $this->$callback_validator();
                } else {
                    throw new \RuntimeException("Validation method '$callback_validator' is missing from this form definition class.");
                }
            }
        }
        $this->valid = $form_valid;

        return $form_valid;
    }

    /**
     * Adds a message to the global message list
     */
    public function addMessage ($control, $message)
    {
        $this->messages[$control][] = $message;
    }

    /**
     * Gets all the controls' messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Getter: Returns a control
     *
     * @return object
     */
    /*function __get ( $control ) {
        if (!isset($this->controls[$control])) {
            throw new UnknowControl ("Unexistent control '$control'.");
        }

        return $this->controls[$control];
    }*/

    /**
     * Setter: Assing $value to the control value.
     */
    /*function __set ( $control, $value ) {
        // Existe como value_holder?
        if ( isset ( $this->value_holders [ $control ] ) ){
            $this->value_holders [ $control ] = $value;
            return;
        }

        if ( !isset ( $this->controls [ $control ])) {
            throw new UnknowControl ("Unexistent control '$control'.");
        }

        $this->controls [ $control ]->set ( $value );
    }*/

    public function asArray(): array
    {
        return $this->getValues();
    }
}
