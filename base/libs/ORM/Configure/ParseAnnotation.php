<?php
namespace Vendimia\ORM\Configure;

use Generator;
use Vendimia\Json;

class ParseAnnotation
{
    private $summary = '';
    private $description = '';
    private $class = null;
    private $properties = [];

    /**
     * Parse the @V: tags.
     *
     * There are two kinds: Field clases and properties. Properties
     * had an '=' sign just after the tag name.
     */
    private function parseTag($tag) 
    {
        $tag = strtr(trim($tag), "\n", ' ');
        
        // SOLO nos importa los tags que empiezan con '@V:'
        if (substr($tag, 0, 3) !== '@V:') {
            return false;
        }

        $tag = substr($tag, 3);

        $re = '/([^ =]+)(?: *)(=?)(?: *)(.*)/';
        $matches = [];

        $c = preg_match($re, $tag, $matches);

        $tagname = $matches[1];
        $is_class = $matches[2] != "=";
        $tagvalue = $matches[3];

        $properties = [];
        if ($is_class) {
            if ($tagvalue) {
                $properties = (new Json($tagvalue))->decode();
            } else {
                $properties = [];
            }

            $this->class = $tagname;
        } else {
            $properties[$tagname] = $tagvalue;
        }

        $this->properties = array_merge($this->properties, $properties);
    }

    private function setSummary($summary)
    {
        $this->summary .= strtr(trim($summary), "\n", ' ');
    }
    private function addDescription($description)
    {
        $this->description .= $description;
    }

    public function __construct($annotation)
    {
        $state = 'summary';
        // Aquí se almacena todas las líneas que se van recolectando
        // antes de procesarlas
        $datachunk = '';
        $lastvalueline = false;

        foreach (explode("\n", $annotation) as $l) {
            $l = trim($l, '/* ');

            // Verificamos si ha cambiado de estado
            if ($l && $l{0} == '@') {
                if ($state == 'tags') {
                    // Si hay una @ en state == tags, es un nuevo tag.
                    $this->parseTag($datachunk);
                    $datachunk = '';
                } else {
                    // Lo que tengamos hasta ahora, lo guardamos, y cambiamos
                    // de estado
                    if ($state == 'summary') {
                        $this->setSummary($datachunk);
                    } else {
                        $this->addDescription($datachunk);
                    }
                    $datachunk = '';
                    $state = 'tags';

                }
            }

            $datachunk .= $l . "\n";

            if ($state == 'summary') {
                if (substr($l, -1) == '.' || (
                    $this->summary != '' && $l == '')) {
 
                     $this->setSummary($datachunk);

                    $datachunk = '';
                    $state = 'description';
                }
            } 
        }

        // Lo que quede, lo guardamos
        if ($datachunk) {
            if ($state == 'summary') {
                $this->setSummary($datachunk);
            } elseif ($state == 'description') {
                $this->addDescription($datachunk);
            } elseif ($state == 'tags') {
                $this->parseTag($datachunk);
            }
        }

   }

    public function getSummary()
    {
        return trim($this->summary);
    }

    public function getDescription()
    {
        return trim($this->description);
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function asArray()
    {
        return [
            'summary' => $this->getSummary(),
            'description' => $this->getDescription(),
            'class' => $this->getClass(),
            'properties' => $this->getProperties(),
        ];
    }
}