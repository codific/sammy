<?php

namespace App\Tests\_support;

use Closure;
use ReflectionFunctionAbstract;

class ReflectionEntityFactoryBuilder
{

    /**
     * Example Usage
     *
     * This function's argument names must match the entity setters
     *
     * private function getEntity($entityProperty1, ... , $entityPropertyN): Entity
     * {
     * $entityFactory = ReflectionEntityFactoryBuilder::getEntityFactoryByReflection(new ReflectionMethod(__METHOD__), Entity::class);
     * $entity = $entityFactory( func_get_args() );
     *
     * return $entity;
     * }
     */


    public static function getEntityFactoryByClosure(
        string $entityClass,
        Closure $closure
    ): Closure {
        return self::getEntityFactoryByReflection($entityClass, new \ReflectionFunction($closure));
    }


    public static function getEntityFactoryByReflection(
        string $entityClass,
        ReflectionFunctionAbstract $reflectionFunction
    ): Closure {
        $getArgumentsArray = self::getArgumentsArrayFunction($reflectionFunction);

        return function (array $arguments) use ($getArgumentsArray, $entityClass) {
            $entity = new $entityClass();
            $fullData = $getArgumentsArray($arguments);

            foreach ($fullData as $property => $value) {
                $entity->{"set$property"}($value);
            }

            return $entity;
        };
    }

    private static function getArgumentsArrayFunction(
        ReflectionFunctionAbstract $reflectionFunction
    ): Closure {

        $argumentsArray = array_reduce(
            $reflectionFunction->getParameters(),
            function(array $result, \ReflectionParameter $param){
                $result[$param->getName()] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                return $result;
            },
            []
        );

        return function (array $arguments) use ($argumentsArray) {

            $argumentsKeys = array_keys($argumentsArray);
            foreach ($arguments as $key => $argument) {
                $argumentsArray[$argumentsKeys[$key]] = $argument;
            }

            return $argumentsArray;
        };

    }
}