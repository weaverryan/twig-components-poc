<?php

namespace App\Twig\Components;

use App\Twig\Component;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class WithService extends Component
{
    public ?string $state = null;

    private ManagerRegistry $service;

    public function __construct(ManagerRegistry $service)
    {
        $this->service = $service;
    }

    public function serviceId(): int
    {
        return \spl_object_id($this->service);
    }
}
