<?php

namespace App\Twig;

use Twig\Compiler;
use Twig\Node\EmbedNode;
use Twig\Node\Expression\ArrayExpression;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentNode extends EmbedNode
{
    public function __construct(string $component, string $template, int $index, ArrayExpression $variables, bool $only, int $lineno, string $tag)
    {
        parent::__construct($template, $index, $variables, $only, false, $lineno, $tag);

        $this->setAttribute('component', $component);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        $compiler
            ->raw('$props = $this->extensions[')
            ->string(ComponentExtension::class)
            ->raw(']->getComponentContext(')
            ->string($this->getAttribute('component'))
            ->raw(', ')
            ->raw('twig_to_array(')
            ->subcompile($this->getNode('variables'))
            ->raw('), ')
            ->raw($this->getAttribute('only') ? '[]' : '$context')
            ->raw(");\n")
        ;

        $this->addGetTemplate($compiler);
        $compiler->raw('->display($props);');
        $compiler->raw("\n");
    }
}
