<?php
namespace Vendimia\Form\Validator;

/**
 * Executes a method inside the form class
 */
class SameValue extends ValidatorAbstract
{
    protected $args = [
        'as' => null,
        'messages' => [
            'different' => "Values for '%control%' and '%as%' are not the same."
        ]
    ];

    public function validate()
    {
        // Hmmm...
        $control = $this->args['as'];
        $target = $this->control->getForm()->$control;
        if ($this->control->getValue() !== $target->getValue()) {
            $this->addMessage('different');
            return false;
        }

        return true;
    }
}