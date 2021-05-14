<?php

namespace App\Twig;

use App\Twig\Attribute\LiveAction;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class LiveComponent extends Component
{
    /**
     * @LiveAction
     */
    public function get(): void
    {
        // noop
        // This is the action that's called when we are simply
        // rendering the component. We do nothing... and then
        // the component will render like normal
    }
}
