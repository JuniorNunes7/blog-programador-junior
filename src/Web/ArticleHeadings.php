<?php

declare(strict_types=1);

namespace Blog\Web;

use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\StringContainerInterface;

final readonly class ArticleHeadings
{
    public function __construct(private string $title)
    {
    }

    public function normalize(Document $document): void
    {
        $walker = $document->walker();
        $duplicate = null;

        while ($step = $walker->next()) {
            $node = $step->getNode();
            if (!$step->isEntering() || !$node instanceof Heading || $node->getLevel() !== 1) {
                continue;
            }

            $text = '';
            foreach ($node->iterator() as $child) {
                if ($child instanceof StringContainerInterface) {
                    $text .= $child->getLiteral();
                }
            }

            if ($node === $document->firstChild() && trim($text) === trim($this->title)) {
                $duplicate = $node;
            } else {
                $node->setLevel(2);
            }
        }

        $duplicate?->detach();
    }
}
