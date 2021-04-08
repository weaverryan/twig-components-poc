<?php

namespace App\Controller;

use App\Twig\ComponentRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

/**
 * @Route("/components", name="live_component")
 */
final class LiveComponentController
{
    public function __invoke(Request $request, ComponentRegistry $registry, Environment $twig): JsonResponse
    {
        $data = \json_decode($request->query->get('data'), true, 512, \JSON_THROW_ON_ERROR);
        $component = $registry->get($request->query->get('component'), $data);

        return new JsonResponse([
            'html' => $component->render($twig),
            'data' => $data,
        ]);
    }
}
