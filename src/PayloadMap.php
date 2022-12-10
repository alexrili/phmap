<?php

declare(strict_types=1);

namespace Phmap\Phmap;

use Hell\Vephar\Collection;
use Hell\Vephar\Resource;
use Hell\Vephar\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Phmap\Phmap\Contracts\InputMap;
use Phmap\Phmap\Contracts\Map;

/**
 * @author '@alexrili'
 * @class PayloadMap
 * @package App\Helpers\Memed
 */
class PayloadMap
{
    public array $outputData = [];
    protected array $inputData;
    protected Collection|InputMap $map;

    public function __construct(array $inputData, array $map)
    {
        $this->map = Response::collection($map, InputMap::class);
        $this->inputData = $inputData;
    }

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

    protected function getValue($inputMap): Map|Collection
    {
        if ($this->isMultiLevel($inputMap->from)) {
            return $this->getMultiValues($inputMap);
        }

        if ($this->isConcatenatedValue($inputMap->from)) {
            return $this->getConcatValues($inputMap);
        }

        if ($this->isFixedValue($inputMap->from)) {
            return $this->handleFixedValue($inputMap);
        }

        $data = [
            'from' => $inputMap->from,
            'to' => $inputMap->to,
            'nullable' => $inputMap->nullable,
            'value' => Arr::get($this->inputData, $inputMap->from),
        ];
        return Response::collection($data, Map::class);
    }

    protected function isMultiLevel(mixed $path): bool
    {
        return (bool)preg_match(MULTI_LEVEL_PATERN, $path);
    }

    protected function getMultiValues(
        InputMap $inputMap,
        array $data = [],
        int $indexFrom = 0,
        int $indexTo = 0
    ): Map|Collection {
        $newInputMap = new InputMap([
            'from' => $this->getAbsolutePath($inputMap->from, $indexFrom),
            'to' => $this->getAbsolutePath($inputMap->to, $indexTo),
            'nullable' => $inputMap->nullable
        ]);
        $result = $this->getValue($newInputMap);

        if ($this->mustKeepingWalkingThrough($result->value, $newInputMap->from)) {
            $data[] = [
                'from' => $newInputMap->from,
                'to' => $newInputMap->to,
                'value' => $result->value,
                'nullable' => $newInputMap->nullable
            ];
            return $this->getMultiValues($inputMap, $data, ++$indexTo, ++$indexFrom);
        }

        return Response::collection($data, Map::class);
    }

    protected function getAbsolutePath(string $relativePath, int $index): string
    {
        return Str::replace(MULTI_LEVEL_SYMBOL, $index, $relativePath);
    }

    protected function mustKeepingWalkingThrough(mixed $value, string $from): bool
    {
        if ($this->isConcatenatedValue($from)) {
            $path = explode(CONCAT_SYMBOL, $from);
            $data = Arr::get($this->inputData, $path[0]);
            return (bool)$data;
        }

        return (bool)$value;
    }

    protected function isConcatenatedValue(mixed $path): bool
    {
        return str_contains($path, CONCAT_SYMBOL);
    }


    protected function getConcatValues(InputMap $inputMap): Map
    {
        $concatPath = explode(CONCAT_SYMBOL, $inputMap->from);
        $concatValues = [];
        foreach ($concatPath as $concatItem) {
            $newInputMap = new InputMap([
                'from' => $concatItem,
                'to' => $inputMap->to,
                'nullable' => $inputMap->nullable
            ]);
            $result = $this->getValue($newInputMap);
            $concatValues[] = $result->value;
        }
        $data = [
            'from' => $inputMap->from,
            'to' => $inputMap->to,
            'nullable' => $inputMap->nullable,
            'value' => implode($concatValues),
        ];
        return Response::collection($data, Map::class);
    }

    protected function isFixedValue(string $path): bool
    {
        return (bool)preg_match(FIXED_VALUE_PATERN, $path);
    }


    protected function handleFixedValue(InputMap $inputMap): Map
    {
        preg_match(FIXED_VALUE_PATERN, $inputMap->from, $matches);
        $data = [
            'from' => $inputMap->from,
            'to' => $inputMap->to,
            'nullable' => $inputMap->nullable,
            'value' => $matches['fixedValue'] ?? null,
        ];
        return Response::collection($data, Map::class);
    }

    protected function handleWithValues(Collection|Map $map): void
    {
        if ($map instanceof Map) {
            $this->setOutputData($map->to, $map->value, $map->nullable);
            return;
        }

        $map->each(function (Map $item) {
            $value = $item->value instanceof Resource ? (array)$item->value : $item->value;
            $this->setOutputData($item->to, $value, $item->nullable);
        });
    }

    protected function setOutputData(string $to, mixed $value, mixed $nullable): void
    {
        if ($value || $nullable === 'true') {
            $this->outputData = data_fill($this->outputData, $to, $value);
        }
    }


}