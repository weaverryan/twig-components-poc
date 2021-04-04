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
        parse_str($request->query->get('data'), $initialData);

        $component = $this->componentRegistry
            ->get($request->query->get('component'));
        ComponentExtension::addContextToComponent($component, $initialData);

        // extra variables to be made available to the controller
        // (for "actions" only)
        parse_str($request->query->get('values'), $values);
        $request->attributes->add($values);

        $action = $request->query->get('action');
        // the default "action" is get, which does nothing
        if (!$action) {
            $action = 'get';
        }

        $request->attributes->set(
            '_controller',
            [$component, $action]
        );
        $request->attributes->set('_component', $component);
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
        $newData = get_object_vars($component);
        $html = $this->componentExtension->renderComponentObject(
            $this->twigEnvironment,
            $component
        );

        $response = new JsonResponse([
            'html' => $html,
            'data' => $newData,
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
