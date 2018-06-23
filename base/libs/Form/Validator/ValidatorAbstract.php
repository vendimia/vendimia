<?php
namespace Vendimia\Form\Validator;

use Vendimia\Form\Control\ControlAbstract;

abstract class ValidatorAbstract
{
    /** This control */
    protected $control = null;

    /** Arguments to this validator */ 
    protected $args = [];
    
    /** 'Valid' state */ 
    protected $is_valid = false;

    /** Error messages if the control doesn't pass the validation */ 
    protected $messages = [];

    /**
     * Creates the object, and sets the variables for 
     */
    public function __construct(ControlAbstract $control, array $args) {
        $this->control = $control;

        $args['control'] = $control->caption;
        $this->args = array_replace_recursive($this->args, $args);
    }

    /**
     * Parse and adds a message from $this->args['messages'] to the message 
     * list.
     *
     * @arg string $code Message index from $this->args['messages']
     */
    protected function addMessage($code)
    {
        $message = $this->args['messages'][$code];
        $matches = '';

        $match = preg_match_all('/%(.+?)%/', $message, $matches, PREG_PATTERN_ORDER);
        if ($match) {
            foreach ($matches[1] as $id => $var) {
                $message = str_replace (
                    $matches[0][$id], 
                    $this->args[$var],
                    $message
                );
            }
        }

        $this->messages[] = $message;
    }


    /**
     * Returns the arrays messages
     */
    public function getMessages()
    {
        return $this->messages;
    }
    /**
     * Executes the validation.
     *
     * @return bool Validation status.
     */
    abstract public function validate();
}
