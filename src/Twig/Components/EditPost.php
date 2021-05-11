<?php

namespace App\Twig\Components;

use App\Entity\Post;
use App\Twig\Attribute\LiveProp;
use App\Twig\LiveComponent;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EditPost extends LiveComponent
{
    /**
     * @LiveProp(readonly=false)
     */
    public Post $post;

    /**
     * @LiveProp(readonly=true)
     */
    public bool $isSaved = false;

    public function save(EntityManagerInterface $entityManager)
    {
        $this->isSaved = true;
        $entityManager->flush();
    }
}
