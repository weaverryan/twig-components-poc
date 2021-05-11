<?php

namespace App\Live;

use App\Twig\ComponentFactory;
use App\Twig\ComponentHydrator;
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

        // TODO - we might read the Content-Type header to see if the input
        // is JSON or form-encoded data
        if ($request->isMethod('GET')) {
            $data = \json_decode($request->query->get('data'), true, 512, \JSON_THROW_ON_ERROR);
        } else {
            $data = \json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        }
        $component = $this->componentFactory->create($request->query->get('component'));

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

        $action = $request->query->get('action');
        // the default "action" is get, which does nothing
        if (!$action) {
            $action = 'get';
        }

        // TODO: TOTAL hack: to work with the service argument resolver, we need
        // the _controller to be the ServiceId::methodName() format...
        // this works because the service is stateful, so when the controller
        // resolver fetches the service, it gets our modified version
        $componentServiceId = get_class($component);
        $request->attributes->set(
            '_controller',
            sprintf('%s::%s', $componentServiceId, $action)
        );
        $request->attributes->set('_component', $component);
        // to make things more fun, sleep randomly from 100-1000ms
        usleep(rand(100000, 1000000));
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

        $response = new JsonResponse([
            'html' => $component->render($this->twigEnvironment),
            'data' => $this->hydrator->dehydrate($component),
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
