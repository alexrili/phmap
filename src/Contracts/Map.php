<?php

declare(strict_types=1);

namespace Phmap\Phmap\Contracts;

use Hell\Vephar\Resource;

/**
 * @author '@alexrili'
 * @class Map
 * @package App\Helpers\Memed\Contracts
 */
class Map extends Resource
{

    /**
     * @var string
     */
    public string $from = '';
    /**
     * @var string
     */
    public string $to = '';
    /**
     * @var mixed|null
     */
    public mixed $value = null;

}