<?php

namespace App\Twig;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class LiveComponent extends Component
{
    /**
     * The "initial data" that was passed to this component on creation.
     *
     * @var array
     */
    private $props = [];

    public function get()
    {
        // noop
        // This is the action that's called when we are simply
        // rendering the component. We do nothing... and then
        // the component will render like normal
    }

    public function getProps(): array
    {
        return $this->props;
    }

    public function setProps(array $props)
    {
        $this->props = $props;
    }
}
