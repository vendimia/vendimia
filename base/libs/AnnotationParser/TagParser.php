<?php
namespace Vendimia\AnnotationParser;

use Vendimia\LooseJson\LooseJson;

/**
 * Parses several DocBlock 'at' tag, searches for valid classes.
 */
class TagParser
{
    private $cn_builder;
    private $raw_tags;
    private $parsed_tags;

    public function __construct(ClassNameBuilder $cn_builder, array $raw_tags)
    {
        $this->cn_builder = $cn_builder;
        $this->raw_tags = $raw_tags;
        $this->parse();
    }

    public function parse()
    {
        foreach ($this->raw_tags as $tag) {
            // Removemos el arroba
            $tag = substr($tag, 1);

            // Obtenemos el nombre de la clase
            $parts = explode(' ', $tag, 2);

            $class_name = $parts[0];
            $args = $parts[1] ?? '';

            $full_class_name = $this->cn_builder->getFQCN($class_name);

            $proceed = false;

            // Existe la clase?
            if (class_exists($full_class_name)) {
                // Oh goody.
                $proceed = true;
            }

            // Quizas sea un callable
            if (!$proceed && is_callable($full_class_name)) {
                $proceed = true;
            }

            if ($proceed) {
                $tag = [
                    'class' => $full_class_name,
                    'args' => (new LooseJson($args))->decode()
                ];

                $this->parsed_tags[] = $tag;
            }
        }
    }

    /**
     * Sets a new tag list, for reusability
     */
    public function setTags(array $raw_tags)
    {
        $this->raw_tags = $raw_tags;
        $this->parsed_tags = [];
        $this->parse();
        
        return $this;
    }

    public function getAnnotationTags()
    {
        return $this->parsed_tags;
    }
}
