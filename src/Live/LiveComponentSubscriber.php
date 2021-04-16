<?php

namespace App\Live;

use App\Twig\ComponentFactory;
use App\Twig\LiveComponent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Environment;

class LiveComponentSubscriber implements EventSubscriberInterface
{
    private ComponentFactory $componentFactory;
    private Environment $twigEnvironment;
    private SerializerInterface $serializer;

    public function __construct(ComponentFactory $componentFactory, Environment $twigEnvironment, SerializerInterface $serializer)
    {
        $this->componentFactory = $componentFactory;
        $this->twigEnvironment = $twigEnvironment;
        $this->serializer = $serializer;
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
        $data = $request->query->get('data');

        $component = $this->componentFactory->create($request->query->get('component'));

        if (!$component instanceof LiveComponent) {
            throw new NotFoundHttpException('this is not a live component!');
        }

        // TODO: our own deserializer/hydrator
        $this->serializer->deserialize($data, \get_class($component), 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $component]);

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

        // TODO: this serializes methods w/o properties - how to avoid
        // TODO: our own serializer/dehydrator
        $serialized = $this->serializer->normalize($component, 'json');

        $response = new JsonResponse([
            'html' => $component->render($this->twigEnvironment),
            'data' => $serialized,
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
