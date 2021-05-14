<?php

namespace App\Live;

use App\Twig\ComponentFactory;
use App\Twig\ComponentHydrator;
use App\Twig\LiveComponent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class LiveComponentSubscriber implements EventSubscriberInterface
{
    private ComponentFactory $componentFactory;
    private Environment $twigEnvironment;
    private ComponentHydrator $hydrator;

    public function __construct(ComponentFactory $componentFactory, Environment $twigEnvironment, ComponentHydrator $hydrator)
    {
        $this->componentFactory = $componentFactory;
        $this->twigEnvironment = $twigEnvironment;
        $this->hydrator = $hydrator;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        // todo - we might rely on some magic "defaults" on the route
        // and/or a special Accept header sent on the request
        if ($request->attributes->get('_route') !== 'live_component') {
            return;
        }

        try {
            $componentServiceId = $this->componentFactory->serviceIdFor((string) $request->get('component'));
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException('Component not found.');
        }

        // the default "action" is get, which does nothing
        $action = $request->get('action', 'get');

        $request->attributes->set(
            '_controller',
            sprintf('%s::%s', $componentServiceId, $action)
        );

        // to make things more fun, sleep randomly from 100-1000ms
        usleep(rand(100000, 1000000));
    }

    public function onKernelController(ControllerEvent $event)
    {
        $request = $event->getRequest();

        // todo - we might rely on some magic "defaults" on the route
        // and/or a special Accept header sent on the request
        if ($request->attributes->get('_route') !== 'live_component') {
            return;
        }

        // TODO: also allow reading from $request->attributes in case
        // some data is part of the URL string - see #42
        $data = array_merge(
            $request->query->all(),
            $request->request->all(),
        );

        $controller = $event->getController();
        $component = null;

        if (\is_array($event->getController())) {
            $component = $controller[0];
        }

        if (!$component instanceof LiveComponent) {
            throw new NotFoundHttpException('this is not a live component!');
        }

        if (!\is_array($data)) {
            throw new NotFoundHttpException('invalid component data');
        }

        $this->hydrator->hydrate($component, $data);

        // extra variables to be made available to the controller
        // (for "actions" only)
        parse_str($request->query->get('values'), $values);
        $request->attributes->add($values);
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

        $html = $component->render($this->twigEnvironment);

        if ($request->getPreferredFormat() === 'json') {
            $response = new JsonResponse([
                'html' => $html,
                'data' => $this->hydrator->dehydrate($component),
            ]);
        } else {
            $response = new Response($html);
        }

        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => 'onKernelRequest',
            ControllerEvent::class => 'onKernelController',
            ViewEvent::class => 'onKernelView',
        ];
    }
}
