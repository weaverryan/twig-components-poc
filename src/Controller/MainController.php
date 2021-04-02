<?php

namespace App\Controller;

use App\Twig\Component;
use App\Twig\ComponentExtension;
use App\Twig\ComponentRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="main")
     */
    public function index()
    {
        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
        ]);
    }

    /**
     * @Route("/live", name="live")
     */
    public function live(ComponentRegistry $componentRegistry)
    {
        // this endpoint mimics what a component would render in "live" mode
        $initialData = [
            'message' => 'starting message'
        ];
        /** @var Component $component */
        $component = $componentRegistry->get('comment');
        ComponentExtension::addContextToComponent($component, $initialData);

        // cheap way to convert public properties into an array
        // TODO - a transformer system to convert, for example,
        // entities to "id" and dates to  a string
        $data = get_object_vars($component);

        return $this->render(
            'main/live.html.twig',
            // this initial data part would normally be handled by the
            // component, taking the component object and turning it into
            // an array. The important part is how it is passed into Stimulus
            $data + ['initialData' => $data, 'componentName' => $component::getComponentName() ]
        );
    }
}
