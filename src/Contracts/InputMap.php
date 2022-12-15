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
    protected $setters = true;
    /**
     * @var string
     */
    public string $from;
    /**
     * @var string
     */
    public string $to;

    /**
     * @var string
     */
    public string $nullable = 'false';

    /**
     * @param string $from
     */
    public function setFrom(string $from): void
    {
        $this->from = $from;
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
    public function setNullable(?string $nullable): void
    {
        $this->nullable = $nullable ?? 'false';
    }



}