<?php

namespace App\Controller;

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
    public function live()
    {
        // this endpoint mimics what a component would render

        // a component object would normally be turned into an array
        // by the component system. Done here manually to focus on
        // the "live" part of the component system
        $data = [
            'message' => 'starting message'
        ];

        return $this->render(
            'main/live.html.twig',
            // this initial data part would normally be handled by the
            // component, taking the component object and turning it into
            // an array. The important part is how it is passed into Stimulus
            $data + ['initialData' => $data ]
        );
    }
}
