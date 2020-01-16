<?php
namespace Vendimia\AnnotationParser;

use ReflectionClass;
use ReflectionMethod;

/**
 * Parse class-level and method-level annotations.
 */
class AnnotationParser
{
    /** Class annotations */
    private $class = [];

    /** Methods annotations */
    private $methods = [];

    /** DocBlock data */
    private $doc_block = [];

    public function __construct($class)
    {
        $this->class = $class;

        $this->parse($class);
    }

    public function parse($class)
    {
        $ref_class = new ReflectionClass($class);

        $source_parser = new SourceParser(
            $ref_class->getFileName(),
            $ref_class->getStartLine() - 1
        );

        $cn_builder = new ClassNameBuilder(
            $ref_class->getNamespaceName(),
            $source_parser->getAliases()
        );

        // Primero, las anotaciones de la clase
        $doc_block = new DocBlockParser($ref_class->getDocComment());
        $annotations = new TagParser($cn_builder, $doc_block->getTags());

        $this->class = [
            'annotations' => $annotations->getAnnotationTags(),
            'doc_block' => $doc_block->asArray(),
        ];

        // Ahora, por cada método
        foreach ($ref_class->getMethods() as $ref_method) {
            // Solo analizamos los métodos de esta clase
            if ($ref_method->class != $class) {
                continue;
            }
            
            $doc_block = new DocBlockParser($ref_method->getDocComment());

            $this->methods[$ref_method->getName()] = [
                'annotations' => $annotations
                    ->setTags($doc_block->getTags())->getAnnotationTags(),
                'doc_block' => $doc_block->asArray(),
            ];
        }
    }

    /**
     * Returns the class' annotations
     */
    public function getClassAnnotations(): array
    {
        return $this->class;
    }
    /**
     * Returns all the method's annotations
     */
    public function getMethodsAnnotations(): array
    {
        return $this->methods;
    }
}
