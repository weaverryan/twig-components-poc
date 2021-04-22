<?php

namespace App\Twig\Components;

use App\Entity\Post;
use App\Twig\Attribute\State;
use App\Twig\LiveComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EditPost extends LiveComponent
{
    /**
     * @State
     */
    public Post $post;
}
