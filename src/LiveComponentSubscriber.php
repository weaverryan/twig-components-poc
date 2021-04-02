<?php

namespace App;

use App\Twig\Component;
use App\Twig\ComponentExtension;
use App\Twig\ComponentRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Twig\Environment;

class LiveComponentSubscriber implements EventSubscriberInterface
{
    private ComponentRegistry $componentRegistry;
    private ComponentExtension $componentExtension;
    private Environment $twigEnvironment;

    public function __construct(ComponentRegistry $componentRegistry, ComponentExtension $componentExtension, Environment $twigEnvironment)
    {
        $this->componentRegistry = $componentRegistry;
        $this->componentExtension = $componentExtension;
        $this->twigEnvironment = $twigEnvironment;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        // todo - we might rely on some magic "defaults" on the route
        // and/or a special Accept header sent on the request
        if ($request->attributes->get('_route') !== 'live_component') {
            return;
        }

        // TODO - the other side of the transformer system would go here,
        // which could transform ids back to entities or date strings to objects
        // parse_str reads in a query param format... need to think about the
        // way that data is passed in the URL, etc
        parse_str($request->query->get('state'), $initialState);
        // extra variables to be made available to the controller
        parse_str($request->query->get('values'), $values);

        $component = $this->componentRegistry
            ->get($request->query->get('component'));
        ComponentExtension::addContextToComponent($component, $initialState);

        $action = $request->query->get('action');
        $request->attributes->set(
            '_controller',
            [$component, $action]
        );
        $request->attributes->set('_component', $component);
        $request->attributes->add($values);
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        $request = $event->getRequest();
        // todo - we might rely on some magic "defaults" on the route
        // and/or a special Accept header sent on the request
        if ($request->attributes->get('_route') !== 'live_component') {
            return;
        }

        /** @var Component $component */
        $component = $request->attributes->get('_component');

        // see MainController where we also do this silliness,
        $newState = get_object_vars($component);
        $html = $this->componentExtension->renderComponentObject(
            $this->twigEnvironment,
            $component
        );

        $response = new JsonResponse([
            'html' => $html,
            'state' => $newState,
        ]);
        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => 'onKernelRequest',
            ResponseEvent::class => 'onKernelResponse',
        ];
    }
}
