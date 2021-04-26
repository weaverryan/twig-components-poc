<?php

namespace App\Twig\Components;

use App\Entity\Post;
use App\Twig\Attribute\LiveProp;
use App\Twig\LiveComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EditPost extends LiveComponent
{
    /**
     * @LiveProp(readonly=true)
     */
    public Post $post;
}
