<?php

namespace App\Live;

use App\Twig\Component;
use App\Twig\ComponentFactory;
use App\Twig\LiveComponent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class LiveComponentSubscriber implements EventSubscriberInterface
{
    private ComponentFactory $componentFactory;
    private Environment $twigEnvironment;

    public function __construct(ComponentFactory $componentFactory, Environment $twigEnvironment)
    {
        $this->componentFactory = $componentFactory;
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

        // TODO - we might read the Content-Type header to see if the input
        // is JSON or form-encoded data
        $props = \json_decode($request->query->get('props'), true, 512, \JSON_THROW_ON_ERROR);
        $data = \json_decode($request->query->get('data'), true, 512, \JSON_THROW_ON_ERROR);

        // TODO - the other side of the transformer system would go here,
        // which could transform ids back to entities or date strings to objects

        $component = $this->componentFactory->create(
            $request->query->get('component'),
            $props,
            $data
        );

        if (!$component instanceof LiveComponent) {
            throw new NotFoundHttpException('this is not a live component!');
        }

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

    public function onKernelView(ViewEvent $event)
    {
        $request = $event->getRequest();
        // todo - we might rely on some magic "defaults" on the route
        // and/or a special Accept header sent on the request
        if ($request->attributes->get('_route') !== 'live_component') {
            return;
        }

        /** @var LiveComponent $component */
        $component = $request->attributes->get('_component');

        // in case an exception was thrown... and this was never set
        if (!$component) {
            return;
        }

        // get all the public properties

        $response = new JsonResponse([
            'html' => $component->render($this->twigEnvironment),
            // TODO - need transformer system here, the same that's
            // mentioned in ComponentExtension
            'data' => $component->getData(),
            'props' => $component->getProps(),
        ]);
        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => 'onKernelRequest',
            ViewEvent::class => 'onKernelView',
        ];
    }
}
