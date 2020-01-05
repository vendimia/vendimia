<?php
namespace Vendimia\ORM\Parser;

use Generator;

class PHPAliasesParser
{

    /**
     * Parsed class classes aliases
     */
    private $aliases = [];

    /**
     * Parse PHP code and yields only useful tokens
     * 
     * @param string $php_string Valid PHP code for parsing
     * @return Generator
     */
    private function getTokens($php_string)
    {
        $php_string = trim($php_string, '{; ');
        $raw_tokens = token_get_all('<?php ' . $php_string);

        foreach ($raw_tokens as $t) {
            $value = null;
            if (is_array($t)) {
                $token = $t[0];
                $value = $t[1];
            } else {
                $token = $t;
            }

            // Ignoramos algunos tokens
            if ($token == T_OPEN_TAG || $token == T_WHITESPACE) {
                continue;
            }

            yield [$token, $value];
        }
    }

    /**
     * Parses $filename up to $linecount line
     */
    public function __construct($filename, $linecount)
    {
        $f = fopen($filename, 'r');
        $count = 0;
        $file = '';
        while ($count < $linecount && $line = fgets($f)) {
            $file .= $line;
            $count++;
        }

        // Buscamos los keywords 'use'
        $re = '/use .*? *\;/is';

        $matches = [];
        preg_match_all($re, $file, $matches);

        // Analizamos cada ocurrencia tokenizando la línea
        foreach ($matches[0] as $tokens) {
            $this->parseUse($this->getTokens($tokens));
        }
    }

 
    private function parseUse(Generator $tokens)
    {
        $alias_name = '';
        $alias_path = '';

        $grouping = false;

        $next_is_alias_name = false;

        foreach ($tokens as $t) {
            list($token, $value) = $t;

            if ($next_is_alias_name && $token == T_STRING) {
                $alias_name = $value;
                continue;
            }

            if ($token == T_USE) {
                continue;
            } elseif ($token == T_STRING) {
                $alias_path .= $value;
                $alias_name = $value;
            } elseif ($token == T_NS_SEPARATOR) {
                $alias_path .= '\\';
            } elseif ($token == T_AS) {
                $next_is_alias_name = true;
            } elseif ($token == ',') {
                // Guardamos
                $this->aliases[$alias_name] = $alias_path;
                $alias_name = '';
                $alias_path = '';
                $next_is_alias_name = false;
            }
        }

        // Guardamos lo que queda
        $this->aliases[$alias_name] = $alias_path;
    }

    public function asArray()
    {
        return $this->aliases;
    }
}