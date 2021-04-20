<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="main")
     */
    public function index(PostRepository $repo)
    {
        try {
            if (!$post = $repo->findOneBy(['slug' => 'lorem-ipsum'])) {
                $post = new Post();
                $post->setTitle('Lorem Ipsum');
                $post->setSlug('lorem-ipsum');
                $this->getDoctrine()->getManager()->persist($post);
                $this->getDoctrine()->getManager()->flush();
            }
        } catch (Exception $e) {
            // allow this app to work w/o doctrine being configured
            $post = null;
        }

        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
            'post' => $post,
        ]);
    }
}
