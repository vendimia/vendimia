<?php
namespace Vendimia\Form\Control;

use Vendimia;

/**
 * Control for drawing radio buttons
 */
class Radio extends ControlAbstract
{

    protected $extra_properties = [
        'html_item' => []      // Tag que rodeará cada item
    ];

    public function draw()
    {
        $html = '';
        foreach ($this->list as $id => $value) {

            $optid = $this->id() . "_" . $id;

            // El label no tiene que tener el html_extra
            $label = Vendimia\Html\Tag::label([
                'for' => $optid,
            ], $value);

            $params = [
                'type' => 'radio',
                'name' => $this->name, 
                'value' => $id,
                'id' => $optid,
            ];


            if (!is_null($this->value) && $id == $this->value) {
                $params['checked'] = 'true';
            }

            $ctrl = $this->htmltag ( 'input', $params );

            $all = $ctrl . $label;

            if ( $this->html_item ) {

                $tag = $this->html_item;
                $tag[2] = $all;    // Con esto evitamos que escape.

                $html .= Vendimia\Html\Tag::create ($tag)->noEscapeContent() . "\n";
            }
            else {
                $html .= $all . "\n";
            }
        }

        return $html;
    }
}