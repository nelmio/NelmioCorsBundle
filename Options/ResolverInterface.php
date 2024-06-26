<?php
/*
 * This file is part of the NelmioCorsBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\CorsBundle\Options;

use Symfony\Component\HttpFoundation\Request;

/**
 * @phpstan-import-type CorsCompleteOptions from ProviderInterface
 */
interface ResolverInterface
{
    /**
     * Returns CORS options for a request's path
     *
     * @return array<string, bool|array<string>|int> CORS options
     * @phpstan-return CorsCompleteOptions
     */
    public function getOptions(Request $request): array;
}
