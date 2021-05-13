<?php

namespace App\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentExtension extends AbstractExtension
{
    private ComponentFactory $factory;
    private ComponentHydrator $hydrator;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(ComponentFactory $factory, ComponentHydrator $hydrator, UrlGeneratorInterface $urlGenerator)
    {
        $this->factory = $factory;
        $this->hydrator = $hydrator;
        $this->urlGenerator = $urlGenerator;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('component', [$this, 'renderComponent'], ['needs_environment' => true, 'is_safe' => ['all']]),
        ];
    }

    public function renderComponent(Environment $env, string $name, array $props = []): string
    {
        $component = $this->factory->create($name, $props);
        $rendered = $component->render($env);

        if (!$component instanceof LiveComponent) {
            return $rendered;
        }

        return $env->render('components/live_component.html.twig', [
            'component' => $component,
            'url' => $this->urlGenerator->generate('live_component', ['component' => $name]),
            'data' => $this->hydrator->dehydrate($component),
            'rendered' => $rendered,
        ]);
    }
}
