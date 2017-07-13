<?php
namespace Vendimia;

use Vendimia;

/**
 * Build URLs from arrays.
 */
class Url
{
    /** Each part of the URL */
    private $parts = [];

    /** Each URL GET arguments */
    private $args = [];

    /** Schema. false for relative urls */
    private $schema = false;

    public function __construct(...$parts)
    {
        // Buscamos la primera parte, para determinar si es relativo o
        // absoluto
        if (is_array($parts[0])) {
            $first_part = &$parts[0][0];
        } else {
            $first_part = &$parts[0];
        }

        $matches = [];
        if (preg_match ('<^.+://|^//>', $first_part, $matches)) {
            
            // Removemos el último slash, por que será añadido
            // al unir todas las partes
            $this->schema = substr($matches[0], 0, strlen($matches[0]) - 1);
            
            $first_part = substr($first_part, strlen($matches[0]));
        }

        $this->processParts(...$parts);
    }

    /**
     * Analize each part, returns a simple array of string parts.
     */
    public function processParts(...$parts)
    {
        // Esto es necesario para poder pasar un array asociativo
        if (count($parts) == 1 && is_array($parts[0])) {
            $parts = $parts[0];
        }

        foreach ($parts as $key => $data) {
            $value = null;

            // Si $key es numérico, es un segmento. No lo usamos.
            if (is_numeric($key)) {
                $key = null;
            }

            // Lo que queda debe ser partes de la URL
            if (is_object($data)) {
                if ($data instanceof Vendimia\ORM\Entity) {
                    $value = $data->pk();
                } else {
                    $value = (string)$data;
                }
            } elseif (is_array($data)) {
                // Recursión, solo si es un segmento
                if (is_null($key)) {
                    $this->processParts($data);
                    continue;
                }
                else {
                    // De lo contrario, lo usamos como parámetros
                    $value = $data;
                }
            } else {
                // Aqui deberían llegar solo strings

                // Si estamos definiendo un parámetro, entonces
                // no procesamos el string
                if (!is_null($key)) {
                    $value = $data;
                }
                else {
                    $value = [];
                    foreach (explode('/', $data) as $part) {
                        $colonpos = strpos($part, ':');
                        $prepart = null;

                        if ($colonpos !== false) {
                            $app = substr($part, 0, $colonpos);
                            if (!$app) { 
                                $app = Vendimia::$application;
                            }
                            $value[] = $app;

                            $part = substr($part, ++$colonpos);
                            if ($part == "") {
                                continue;
                            }
                        } 
                        $value[] = urlencode($part);
                    }
                }
            }

            if ($value) {
                // Si está definido $key, entonces en un parámetro
                // en la URL
                if ($key) {
                    $this->args[$key] = $value;
                } else {
                    if (is_array($value)) {
                        $this->parts = array_merge (
                            $this->parts,
                            $value
                        );
                    } else {
                        $this->parts[] = $value;
                    }
                }
            }
        }
    }

    /**
     * Builds the path using $this->parts[] and $this->args[]
     */
    public function get()
    {
        if ($this->schema) {
            // Absoluto
            array_unshift($this->parts, $this->schema);
        } else {
            // Relativo
            array_unshift($this->parts, rtrim(Vendimia::$base_url, '/.'));
        }


        $url = join('/', $this->parts);
        if ($this->args) {
            $url .= '?' . http_build_query($this->args);
        }

        return $url;
    }

    /**
     * Static shortcut method
     */
    public static function parse(...$params) 
    {
        return (new self(...$params))->get();
    }

    /**
     * Returns a single-quoted URL
     */
    public static function q(...$params)
    {
        return "'" . new self(...$params) . "'";
    }

    /**
     * Returns a double-quoted URL
     */
    public static function qq(...$params)
    {
        return '"' . new self(...$params) . '"';
    }

    /**
     * Magic method for conver to string
     */
    public function __toString()
    {
        return $this->get();
    }
}
