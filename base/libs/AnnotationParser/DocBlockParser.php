<?php
namespace Vendimia\AnnotationParser;

/**
 * Parses a DocBlock into a summary, description, and tags
 */
class DocBlockParser
{
    private $doc_block;
    private $summary = '';
    private $description = [];
    private $tags = [];

    public function __construct($doc_block)
    {
        $this->doc_block = $doc_block;
        $this->parse();
    }

    /**
     * Parses a DocBlock
     */
    public function parse()
    {
        $stage = 'text';
        $paragraphs = [];
        $data = [];

        foreach (explode("\n", $this->doc_block) as $line) {
            $line = trim($line, '/* ');

            $at_line = $line && $line[0] == '@';

            if ($at_line) {
                $stage = 'tags';
            }

            if ($stage == 'text') {
                $process_condition = $line == '';
            } else {
                $process_condition = $at_line;
            }

            if ($process_condition && $data) {
                $paragraphs[] = join("\n", $data);
                $data = [];
            }


            if ($line) {
                $data[] = $line;
            }
        }

        if ($data) {
           $paragraphs[] = join("\n", $data);
        }

        // Separamos los párrafos '@'
        foreach ($paragraphs as $p) {
            if ($p[0] == '@') {
                $this->tags[] = $p;
            } else {
                if (!$this->summary) {
                    $this->summary = $p;
                } else {
                    $this->description[] = $p;
                }
            }
        }
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getDescription(): array
    {
        return $this->description;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function asArray(): array
    {
        return [
            'summary' => $this->summary,
            'description' => $this->description,
            'tags' => $this->tags,
        ];
    }

}
