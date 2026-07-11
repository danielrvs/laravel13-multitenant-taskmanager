<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionProperty;

abstract readonly class AbstractDto
{
    public static function fromRequest(Request $request)
    {
        $sourceData = method_exists($request, 'validated') ? $request->validated() : $request->all();

        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return new static;
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $paramName = $parameter->getName();

            if ($paramName === 'presentFields') {
                $arguments[$paramName] = array_keys($sourceData);

                continue;
            }

            $snakeKey = Str::snake($paramName);

            if (array_key_exists($snakeKey, $sourceData)) {
                $arguments[$paramName] = $sourceData[$snakeKey];
            } elseif (array_key_exists($paramName, $sourceData)) {
                $arguments[$paramName] = $sourceData[$paramName];
            } else {
                // Si el campo no se envió, respetamos su valor por defecto o le asignamos null
                $arguments[$paramName] = $parameter->isDefaultValueAvailable()
                    ? $parameter->getDefaultValue()
                    : null;
            }
        }

        return new static(...$arguments);
    }

    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $mappedData = [];

        foreach ($properties as $property) {
            $name = $property->getName();
            $mappedData[$name] = $property->getValue($this);
        }

        if (property_exists($this, 'presentFields') && ! empty($this->presentFields)) {
            return array_intersect_key($mappedData, array_flip($this->presentFields));
        }

        return $mappedData;
    }
}
