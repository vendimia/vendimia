<?php
namespace Vendimia\Form\Validator;

/**
 * Executes a method inside the form class
 */
class Callback extends ValidatorAbstract
{
    protected $args = [
        'callback' => null,
        'messages' => [
            'error' => "Error executing callback for '%control%'.",
            'fail' => "%rawmessage%",
        ]
    ];

    public function validate()
    {
        if (is_null($this->args['callback'])) {
            $this->addMessage('error');
            return false;
        }

        $callback = $this->args['callback'];

        if (is_string($callback)) {
            if (!method_exists($this->control->getForm(), $callback)) {
                $this->addMessage('error');
                return false;
            }

            $return = $this->control->getForm()->$callback($this->control);

            if ($return === true) {
                return true;
            } else {
                $this->args['rawmessage'] = $return;
                $this->addMessage('fail');
                return false;
            }
        }
    }
}
