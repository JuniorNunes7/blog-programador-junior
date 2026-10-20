<?php

declare(strict_types=1);

namespace Blog\Web;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use Stringable;

final readonly class ScrollableBlockRenderer implements NodeRendererInterface
{
    public function __construct(private NodeRendererInterface $renderer, private string $label)
    {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): Stringable
    {
        $element = $this->renderer->render($node, $childRenderer);
        $attributes = ['tabindex' => '0', 'role' => 'region', 'aria-label' => $this->label];
        if ($element instanceof HtmlElement && $element->getTagName() === 'pre') {
            foreach ($attributes as $key => $value) {
                $element->setAttribute($key, $value);
            }

            return $element;
        }

        return new HtmlElement('div', $attributes + ['class' => 'table-scroll'], $element);
    }
}
