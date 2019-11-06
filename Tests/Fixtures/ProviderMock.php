<?php

declare(strict_types=1);

namespace Fixtures;

use Nelmio\CorsBundle\Options\ProviderInterface;
use Symfony\Component\HttpFoundation\Request;

final class ProviderMock implements ProviderInterface
{
    public function __construct() {}

    public function getOptions(Request $request): void {}
}
