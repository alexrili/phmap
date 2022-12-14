<?php

declare(strict_types=1);

namespace Phmap\Phmap;

use Hell\Vephar\Collection;
use Hell\Vephar\Resource;
use Hell\Vephar\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Phmap\Phmap\Contracts\InputMap;
use Phmap\Phmap\Contracts\OutputMap;


/**
 * @author '@alexrili'
 * @class PayloadMap
 * @package Phmap\Phmap
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
     * @var \Hell\Vephar\Collection|\Phmap\Phmap\Contracts\InputMap|mixed
     */
    protected Collection|InputMap $map;

    /**
     * @param array $inputData
     * @param array $map
     */
    public function __construct(array $inputData, array $map)
    {
        $this->map = Response::collection($map, InputMap::class);
        $this->inputData = $inputData;
    }

    /**
     * @return void
     */
    public function doMapping(): void
    {
        if ($this->map instanceof InputMap) {
            $values = $this->getValue($this->map);
            $this->handleWithValues($values);
            return;
        }
        $this->map->each(function (InputMap $map) {
            $values = $this->getValue($map);
            $this->handleWithValues($values);
        });
    }

    /**
     * @param $inputMap
     * @return \Phmap\Phmap\Contracts\OutputMap|\Hell\Vephar\Collection
     */
    protected function getValue($inputMap): OutputMap|Collection
    {
        if ($this->isMultiLevel($inputMap->from)) {
            return $this->getMultiValues($inputMap);
        }

        if ($this->isConcatenatedValue($inputMap->from)) {
            return $this->getConcatValues($inputMap);
        }

        if ($this->isOneOrAnotherValue($inputMap->from)) {
            return $this->getOneOrAnotherValue($inputMap);
        }

        if ($this->isFixedValue($inputMap->from)) {
            return $this->handleFixedValue($inputMap);
        }

        $data = [
            ...(array)$inputMap,
            'value' => Arr::get($this->inputData, $inputMap->from),
        ];
        return Response::collection($data, OutputMap::class);
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
     * @param \Phmap\Phmap\Contracts\InputMap $inputMap
     * @param array $data
     * @param int $indexFrom
     * @param int $indexTo
     * @return \Phmap\Phmap\Contracts\OutputMap|\Hell\Vephar\Collection
     */
    protected function getMultiValues(
        InputMap $inputMap,
        array $data = [],
        int $indexFrom = 0,
        int $indexTo = 0
    ): OutputMap|Collection {
        $newInputMap = new InputMap([
            ...(array)$inputMap,
            'from' => $this->getAbsolutePath($inputMap->from, $indexFrom),
            'to' => $this->getAbsolutePath($inputMap->to, $indexTo),
        ]);

        $result = $this->getValue($newInputMap);
        $data[] = [
            ...(array)$newInputMap,
            'value' => $result->value,
        ];
        if ($this->mustKeepingWalkingThrough($newInputMap->from, $indexFrom)) {
            return $this->getMultiValues($inputMap, $data, ++$indexTo, ++$indexFrom);
        }

        return Response::collection($data, OutputMap::class);
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
     * @param string $path
     * @param $index
     * @return bool
     */
    protected function mustKeepingWalkingThrough(string $path, $index): bool
    {
        if ($this->isConcatenatedValue($path)) {
            $path = Arr::first(explode(CONCAT_SYMBOL, $path));
        }

        if ($this->isOneOrAnotherValue($path)) {
            $path = Arr::first(explode(OR_SYMBOL, $path));
        }

        preg_match(MULTI_LEVEL_PATERN_VALUES, $path, $metches);
        $multilevelSize = count(Arr::get($this->inputData, $metches[1] ?? null) ?? []);

        return $index + 1 < $multilevelSize;
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
     * @param mixed $path
     * @return bool
     */
    protected function isOneOrAnotherValue(mixed $path): bool
    {
        return str_contains($path, OR_SYMBOL);
    }

    /**
     * @param \Phmap\Phmap\Contracts\InputMap $inputMap
     * @return \Phmap\Phmap\Contracts\OutputMap
     */
    protected function getConcatValues(InputMap $inputMap): OutputMap
    {
        $concatPath = explode(CONCAT_SYMBOL, $inputMap->from);
        $concatValues = [];
        foreach ($concatPath as $concatItem) {
            $newInputMap = new InputMap([
                ...(array)$inputMap,
                'from' => $concatItem,
            ]);
            $result = $this->getValue($newInputMap);
            $concatValues[] = $result->value;
        }

        $outputMap = [
            ...(array)$inputMap,
            'value' => implode($concatValues),
        ];
        return new OutputMap($outputMap);
    }

    /**
     * @param \Phmap\Phmap\Contracts\InputMap $inputMap
     * @return \Phmap\Phmap\Contracts\OutputMap
     */
    protected function getOneOrAnotherValue(InputMap $inputMap): OutputMap
    {
        $oneOrAnotherPath = explode(OR_SYMBOL, $inputMap->from);
        $value = null;
        foreach ($oneOrAnotherPath as $item) {
            $newInputMap = new InputMap([
                ...(array)$inputMap,
                'from' => $item,
            ]);
            $result = $this->getValue($newInputMap);
            if ($result->value) {
                $value = $result->value;
                break;
            }
        }
        $outputMap = [
            ...(array)$inputMap,
            'value' => $value,
        ];
        return new OutputMap($outputMap);
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
     * @param \Phmap\Phmap\Contracts\InputMap $inputMap
     * @return \Phmap\Phmap\Contracts\OutputMap
     */
    protected function handleFixedValue(InputMap $inputMap): OutputMap
    {
        preg_match(FIXED_VALUE_PATERN, $inputMap->from, $matches);
        $outputMap = [
            ...(array)$inputMap,
            'value' => $matches['fixedValue'] ?? null,
        ];
        return new OutputMap($outputMap);
    }

    /**
     * @param \Hell\Vephar\Collection|\Phmap\Phmap\Contracts\OutputMap $outputMap
     * @return void
     */
    protected function handleWithValues(Collection|OutputMap $outputMap): void
    {
        if ($outputMap instanceof OutputMap) {
            $this->setOutputData($outputMap->to, $outputMap->value, $outputMap->nullable);
            return;
        }

        $outputMap->each(function (OutputMap $map) {
            $value = $map->value instanceof Resource ? (array)$map->value : $map->value;
            $this->setOutputData($map->to, $value, $map->nullable);
        });
    }

    /**
     * @param string $to
     * @param mixed $value
     * @param mixed $nullable
     * @return void
     */
    protected function setOutputData(string $to, mixed $value, mixed $nullable): void
    {
        if ($value || $nullable === 'true') {
            $this->outputData = data_fill($this->outputData, $to, $value);
        }
    }

}