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
    protected $setters = true;
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

    /**
     * @param mixed $value
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * @param string $to
     */
    public function setTo(string $to): void
    {
        $this->to = $to;
    }

    /**
     * @param string $nullable
     */
    public function setNullable(string $nullable): void
    {
        $this->nullable = $nullable;
    }

}