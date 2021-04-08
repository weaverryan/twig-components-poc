<?php

namespace App\Twig;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class LiveComponent extends Component
{
    public function get()
    {
        // noop
        // This is the action that's called when we are simply
        // rendering the component. We do nothing... and then
        // the component will render like normal
    }
}
