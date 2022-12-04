<?php

declare(strict_types=1);

use Phmap\Phmap\PayloadMap;

if (!function_exists('payload_map')) {
    /**
     * @param array $input
     * @param array $map
     * @return array
     */
    function payload_map(array $input, array $map): array
    {
        $payloadMap = new PayloadMap($input, $map);
        $payloadMap->doMapping();
        return $payloadMap->outputData;
    }
}
