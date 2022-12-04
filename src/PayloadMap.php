<?php

declare(strict_types=1);

namespace Phmap\Phmap;

use Phmap\Phmap\Contracts\Map;
use Phmap\Phmap\Contracts\MapCollection;
use Hell\Vephar\Collection;
use Hell\Vephar\Resource;
use Hell\Vephar\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @author '@alexrili'
 * @class PayloadMap
 * @package App\Helpers\Memed
 */
class PayloadMap
{

    /**
     * @var array
     */
    public array $outputData = [];
    /**
     * @var array
     */
    protected array $inputData;
    /**
     * @var \App\Helpers\Memed\Contracts\MapCollection|mixed
     */
    protected MapCollection|Map $map;

    /**
     * @param array $inputData
     * @param array $map
     */
    public function __construct(array $inputData, array $map)
    {
        $this->map = Response::collection($map, Map::class, MapCollection::class);
        $this->inputData = $inputData;
    }


    /**
     * @return void
     */
    public function doMapping(): void
    {
        if ($this->map instanceof Map) {
            $values = $this->getValue($this->map->from, $this->map->to);
            $this->setOutputData($values);
            return;
        }
        $this->map->each(function (Map $map) {
            $values = $this->getValue($map->from, $map->to);
            $this->setOutputData($values);
        });
    }

    /**
     * @param string $from
     * @param string $to
     * @return \App\Helpers\Memed\Contracts\Map|\Hell\Vephar\Collection
     */
    protected function getValue(string $from, string $to): Map|Collection
    {
        if ($this->isMultiLevel($from)) {
            return $this->getMultiValues($from, $to);
        }

        if ($this->isConcatenatedValue($from)) {
            return $this->getConcatValues($from, $to);
        }

        if ($this->isFixedValue($from)) {
            return $this->handleFixedValue($from, $to);
        }

        $value = Arr::get($this->inputData, $from);
        return Response::collection(compact('from', 'to', 'value'), Map::class);
    }

    /**
     * @param string $from
     * @param string $to
     * @param array $data
     * @param int $indexFrom
     * @param int $indexTo
     * @return \App\Helpers\Memed\Contracts\Map|\Hell\Vephar\Collection
     */
    protected function getMultiValues(
        string $from,
        string $to,
        array $data = [],
        int $indexFrom = 0,
        int $indexTo = 0
    ): Map|Collection {
        $fromAbsolutePath = $this->getAbsolutePath($from, $indexFrom);
        $toAbsolutePath = $this->getAbsolutePath($to, $indexTo);
        $result = $this->getValue($fromAbsolutePath, $toAbsolutePath);

        if ($this->mustKeepingWalkingThrough($result->value, $fromAbsolutePath)) {
            $data[] = ['from' => $fromAbsolutePath, 'to' => $toAbsolutePath, 'value' => $result->value];
            return $this->getMultiValues($from, $to, $data, ++$indexTo, ++$indexFrom);
        }

        return Response::collection($data, Map::class);
    }

    /**
     * @param string $relativePath
     * @param int $index
     * @return string
     */
    protected function getAbsolutePath(string $relativePath, int $index): string
    {
        return Str::replace(MULTI_LEVEL_SYMBOL, $index, $relativePath);
    }


    /**
     * @param string $from
     * @param string $to
     * @return \App\Helpers\Memed\Contracts\Map
     */
    protected function handleFixedValue(string $from, string $to): Map
    {
        preg_match(FIXED_VALUE_PATERN, $from, $matches);
        $value = $matches['fixedValue'] ?? null;
        return Response::collection(compact('from', 'to', 'value'), Map::class);
    }


    /**
     * @param string $from
     * @param string $to
     * @return \App\Helpers\Memed\Contracts\Map
     */
    protected function getConcatValues(string $from, string $to): Map
    {
        $concatPath = explode(CONCAT_SYMBOL, $from);
        $data = [];
        foreach ($concatPath as $concatItem) {
            $result = $this->getValue($concatItem, $to);
            $data[] = $result->value;
        }

        return Response::collection(['from' => $from, 'to' => $to, 'value' => implode($data)], Map::class);
    }


    /**
     * @param mixed $value
     * @param string $from
     * @return bool
     */
    protected function mustKeepingWalkingThrough(mixed $value, string $from): bool
    {
        if ($this->isConcatenatedValue($from)) {
            $path = explode(CONCAT_SYMBOL, $from);
            $data = Arr::get($this->inputData, $path[0]);
            return (bool)$data;
        }

        return (bool)$value;
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function isFixedValue(string $path): bool
    {
        return (bool)preg_match(FIXED_VALUE_PATERN, $path);
    }


    /**
     * @param mixed $path
     * @return bool
     */
    protected function isMultiLevel(mixed $path): bool
    {
        return (bool)preg_match(MULTI_LEVEL_PATERN, $path);
    }

    /**
     * @param mixed $path
     * @return bool
     */
    protected function isConcatenatedValue(mixed $path): bool
    {
        return str_contains($path, CONCAT_SYMBOL);
    }

    /**
     * @param \Hell\Vephar\Collection|\App\Helpers\Memed\Contracts\Map $map
     * @return void
     */
    protected function setOutputData(Collection|Map $map): void
    {
        if ($map instanceof Map) {
            $this->outputData = data_fill($this->outputData, $map->to, $map->value);
            return;
        }

        $map->each(function (Map $item) {
            $value = $item->value instanceof Resource ? $item->value->toArray() : $item->value;
            $this->outputData = data_fill($this->outputData, $item->to, $value);
        });
    }

}