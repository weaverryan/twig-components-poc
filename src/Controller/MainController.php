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
        if (!$post = $repo->findOneBy(['slug' => 'lorem-ipsum'])) {
            $post = new Post();
            $post->setTitle('Lorem Ipsum');
            $post->setSlug('lorem-ipsum');
            $this->getDoctrine()->getManager()->persist($post);
            $this->getDoctrine()->getManager()->flush();
        }

        if (!$repo->findOneBy(['slug' => 'sed-faucibus'])) {
            // a second one, helps with changing the post data
            $post2 = new Post();
            $post2->setTitle('Sed faucibus');
            $post2->setSlug('sed-faucibus');
            $this->getDoctrine()->getManager()->persist($post2);
            $this->getDoctrine()->getManager()->flush();
        }

        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
            'post' => $post,
        ]);
    }
}
