<?php

namespace App\Twig\Hydrator;

use App\Twig\UnsupportedHydrationException;
use App\Twig\PropertyHydrator;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NormalizerBridgePropertyHydrator implements PropertyHydrator
{
    /** @var NormalizerInterface|DenormalizerInterface */
    private NormalizerInterface $normalizer;

    public function __construct(NormalizerInterface $normalizer)
    {
        if (!$normalizer instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException('Normalizer must also be a Denormalizer.');
        }

        $this->normalizer = $normalizer;
    }

    public function dehydrate($value)
    {
        if (!$this->normalizer->supportsNormalization($value)) {
            throw new UnsupportedHydrationException();
        }

        return $this->normalizer->normalize($value);
    }

    public function hydrate(string $type, $value)
    {
        if (!$this->normalizer->supportsDenormalization($value, $type)) {
            throw new UnsupportedHydrationException();
        }

        return $this->normalizer->denormalize($value, $type);
    }
}
