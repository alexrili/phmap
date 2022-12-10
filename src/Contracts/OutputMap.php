<?php

namespace Phmap\Phmap\Contracts;

use Hell\Vephar\Resource;

/**
 * @author '@alexrili'
 * @class OutputMap
 * @package Phmap\Phmap\Contracts
 */
class OutputMap extends Resource
{
    /**
     * @var mixed
     */
    public mixed $value;
    /**
     * @var string
     */
    public string $to;
    /**
     * @var string
     */
    public string $nullable;
}