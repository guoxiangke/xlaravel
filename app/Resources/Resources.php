<?php

namespace App\Resources;

use ReflectionClass;
use Symfony\Component\Finder\Finder;

class Resources
{
    /**
     * Resolve the resource matching the given keyword.
     */
    public function resolve(string $keyword): mixed
    {
        $handlersPath = __DIR__ . '/Handlers';

        if (!is_dir($handlersPath)) {
            return null;
        }

        foreach ((new Finder)->in($handlersPath)->files()->name('*.php') as $file) {
            $className = 'App\\Resources\\Handlers\\' . $file->getBasename('.php');

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if ($reflection->isAbstract()) {
                continue;
            }

            $handler = app($className);
            if (!method_exists($handler, 'resolve')) {
                continue;
            }

            $result = $handler->resolve($keyword);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Get a list of all available resources.
     */
    public function getAll(): array
    {
        $handlersPath = __DIR__ . '/Handlers';
        $list = [];

        if (!is_dir($handlersPath)) {
            return $list;
        }

        foreach ((new Finder)->in($handlersPath)->files()->name('*.php') as $file) {
            $className = 'App\\Resources\\Handlers\\' . $file->getBasename('.php');

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if ($reflection->isAbstract()) {
                continue;
            }

            $handler = app($className);
            if (method_exists($handler, 'getResourceList')) {
                $handlerName = class_basename($className);
                $list[$handlerName] = $handler->getResourceList();
            }
        }

        return $list;
    }

    /**
     * Get a list of resources for a specific handler.
     */
    public function getByHandler(string $handlerName): array
    {
        $className = 'App\\Resources\\Handlers\\' . $handlerName;

        if (!class_exists($className)) {
            return [];
        }

        $reflection = new ReflectionClass($className);
        if ($reflection->isAbstract()) {
            return [];
        }

        $handler = app($className);
        if (method_exists($handler, 'getResourceList')) {
            return $handler->getResourceList();
        }

        return [];
    }
}