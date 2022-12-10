<?php

namespace Phmap\Phmap\Contracts;

use Hell\Vephar\Resource;

/**
 * @author '@alexrili'
 * @class InputMap
 * @package Phmap\Phmap\Contracts
 */
class InputMap extends Resource
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
     * @var string
     */
    public string $nullable = 'false';
}