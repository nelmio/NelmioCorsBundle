<?php

declare(strict_types=1);

namespace Fixtures;

use Nelmio\CorsBundle\Options\ProviderInterface;
use Symfony\Component\HttpFoundation\Request;

final class ProviderMock implements ProviderInterface
{
    public function __construct() {}

    /**
     * @return array<string, bool|int|string[]>
     */
    public function getOptions(Request $request): array {
        return [];
    }
}
