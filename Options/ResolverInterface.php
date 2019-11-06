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

interface ResolverInterface
{
    /**
     * Returns CORS options for $path
     *
     * @internal param string $path
     */
    public function getOptions(Request $request): array;
}
