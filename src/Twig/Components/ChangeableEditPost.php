<?php

namespace App\Twig\Components;

use App\Entity\Post;
use App\Repository\PostRepository;
use App\Twig\Attribute\LiveProp;
use App\Twig\LiveComponent;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ChangeableEditPost extends LiveComponent
{
    /**
     * The post itself is CHANGEABLE
     *
     * @LiveProp(readonly=false, exposed={"title"})
     */
    public Post $post;

    private PostRepository $postRepository;

    public function __construct(PostRepository $postRepository)
    {
        $this->postRepository = $postRepository;
    }

    public function save(EntityManagerInterface $entityManager)
    {
        $entityManager->flush();
    }

    /**
     * @return Post[]
     */
    public function getAllPosts(): array
    {
        return $this->postRepository->findAll();
    }
}
