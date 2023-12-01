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
 * CORS configuration provider interface.
 *
 * Can override CORS options for a particular path.
 */
interface ProviderInterface
{
    /**
     * Returns CORS options for $request.
     *
     * Any valid CORS option will overwrite those of the previous ones.
     * The method must at least return an empty array.
     *
     * All keys of the bundle's semantical configuration are valid:
     * - bool allow_credentials
     * - bool allow_origin
     * - bool allow_headers
     * - bool allow_private_network
     * - bool origin_regex
     * - array allow_methods
     * - array expose_headers
     * - int max_age
     *
     * @return array CORS options
     */
    public function getOptions(Request $request): array;
}
