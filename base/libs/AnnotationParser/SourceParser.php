<?php
namespace Vendimia\AnnotationParser;

/**
 * Parses a PHP class source file, get its class aliases
 */
class SourceParser
{
    private $source;
    private $aliases = [];

    /**
     * Reads a PHP file up to the class starting line, for analyze.
     */
    public function __construct($filename, $starting_line)
    {
        $this->source = join("\n", array_slice(file($filename), 0, $starting_line));
        $this->parse();
    }

    /**
     * Search the source code for 'use' keywords
     */
    public function parse()
    {
        $use_tokens = [];
        $in_use_line = false;

        foreach (token_get_all($this->source) as $php_token) {
            // Nos aseguramos que siempre sea un array

            if (is_array($php_token)) {
                $code = $php_token[0];
                $value = $php_token[1];
            } else {
                $code = $php_token;
                $value = null;
            }

            if ($in_use_line) {
                if ($code == ';') {
                    $in_use_line = false;
                    $this->parseUseTokens($use_tokens);
                    $use_tokens = [];
                    continue;
                }

                $use_tokens[] = [$code, $value];
            }

            if ($code == T_USE) {
                $in_use_line = true;
            }

        }
    }
    /**
     * Parses a PHP 'use' line.
     */
    public function parseUseTokens($tokens, $base_path = '')
    {
        // El primer token siempre debe ser un espacio. Lo ignoramos
        if ($tokens[0][0] == T_WHITESPACE) {
            array_shift($tokens);
        }

        $class_path = '';
        $alias = '';

        // True cuando hay un T_AS, el siguiente T_STRING es el alias
        $next_token_is_alias = false;

        // True cuando estamos agrupando con {}
        $grouping = false;
        $group_tokens = [];

        foreach ($tokens as $token)
        {
            list($code, $value) = $token;

            if ($grouping) {
                if ($code == '}') {
                    $grouping = false;
                    $this->parseUseTokens($group_tokens, $class_path);
                    $group_tokens = [];
                    $alias = '';
                    $class_path = '';
                    $next_token_is_alias = false;

                    continue;
                }
                $group_tokens[] = $token;

                continue;
            }

            if ($code == T_STRING) {
                if ($next_token_is_alias) {
                    $alias = $value;
                    $next_token_is_alias = false;

                    continue;
                } else {
                    $class_path .= $value;
                    $alias = $value;
                }
            } elseif ($code == T_NS_SEPARATOR) {
                $class_path .= '\\';
            } elseif ($code == T_AS) {
                $next_token_is_alias = true;
            } elseif ($code == '{') {
                $grouping = true;

                continue;
            } elseif ($code == ',') {
                $this->aliases[$alias] = $base_path . $class_path;
                $alias = '';
                $class_path = '';
                $next_token_is_alias = false;
            }
        }

        // Guardamos lo que queda
        if ($class_path) {
            $this->aliases[$alias] = $base_path . $class_path;
        }
    }

    /**
     * Returns the aliases
     */
    public function getAliases()
    {
        return $this->aliases;
    }
}
