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

    /**
     * Returns a map of the name=>value of the public, modifiable data.
     *
     * This will be the data that is passed to the frontend, and should
     * match the data (e.g. public properties) that are modifiable.
     */
    public function getData(): array
    {
        $reflectionObject = new \ReflectionObject($this);
        $data = [];
        foreach ($reflectionObject->getProperties() as $property) {
            if ($property->isPublic()) {
                $data[$property->getName()] = $this->{$property->getName()};
            }
        }

        return $data;
    }
}
