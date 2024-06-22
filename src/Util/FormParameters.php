<?php

declare(strict_types=1);

namespace App\Util;

final class FormParameters
{
    /** @var \stdClass|null Any additional properties which have to be sent to the form */
    public ?\stdClass $additionalFormVars = null;

    /**
     * FormParameters constructor.
     */
    public function __construct()
    {
        $this->additionalFormVars = new \stdClass();
    }

    /**
     * Get array with only the modified properties.
     *
     * @return array
     */
    public function getModifiedPropertiesArray()
    {
        try {
            foreach (get_object_vars($this->additionalFormVars) as $key => $value) {
                if (!property_exists($this, $key)) {
                    $this->{$key} = $value; // @phpstan-ignore-line
                }
            }
            $diff = $this->arrayRecursiveDiff(
                get_object_vars($this),
                (new \ReflectionClass($this))->getDefaultProperties()
            );
            if (isset($diff['additionalFormVars'])) {
                unset($diff['additionalFormVars']);
            }

            return $diff;
        } catch (\ReflectionException $e) {
            return [];
        }
    }

    /**
     * Recursive diff between two arrays.
     */
    private function arrayRecursiveDiff(array $firstArray, array $secondArray): array
    {
        $result = [];
        foreach ($firstArray as $key => $value) {
            if (array_key_exists($key, $secondArray)) {
                if (is_array($value)) {
                    $recursiveDiff = $this->arrayRecursiveDiff($value, $secondArray[$key]);
                    if (count($recursiveDiff) > 0) {
                        $result[$key] = $recursiveDiff;
                    }
                } else {
                    if ($value !== $secondArray[$key]) {
                        $result[$key] = $value;
                    }
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
