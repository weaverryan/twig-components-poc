<?php

namespace App\Twig\Hydrator;

use App\Twig\UnsupportedHydrationException;
use App\Twig\PropertyHydrator;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class DoctrineEntityPropertyHydrator implements PropertyHydrator
{
    private iterable $managerRegistries;

    /**
     * @param ManagerRegistry[] $managerRegistries
     */
    public function __construct(iterable $managerRegistries)
    {
        $this->managerRegistries = $managerRegistries;
    }

    public function dehydrate($value)
    {
        if (!\is_object($value)) {
            throw new UnsupportedHydrationException();
        }

        $id = $this
            ->objectManagerFor($class = \get_class($value))
            ->getClassMetadata($class)
            ->getIdentifierValues($value)
        ;

        // TODO: entity id is UUID
        switch (\count($id)) {
            case 0:
                // TODO: should this be allowed?
                throw new \RuntimeException("Cannot normalize unpersisted entity ({$class}).");
            case 1:
                return \array_values($id)[0];
        }

        // composite id
        return $id;
    }

    public function hydrate(string $type, $value)
    {
        return $this->objectManagerFor($type)->find($type, $value);
    }

    private function objectManagerFor(string $class): ObjectManager
    {
        foreach ($this->managerRegistries as $registry) {
            if ($om = $registry->getManagerForClass($class)) {
                return $om;
            }
        }

        throw new UnsupportedHydrationException();
    }
}
