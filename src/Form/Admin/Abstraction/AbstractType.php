<?php
/**
 * This is automatically generated file using the BOZA Framework generator.
 *
 * PHP version 8
 *
 * @category PHP
 *
 * @author   CODIFIC <info@codific.com>
 *
 * @see     http://codific.com
 */

declare(strict_types=1);

namespace App\Form\Admin\Abstraction;

use Symfony\Component\Form\AbstractType as SymfonyAbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AbstractType extends SymfonyAbstractType
{
    protected array $elements = [];

    protected function addElements(FormBuilderInterface $builder)
    {
        uasort($this->elements, fn ($a, $b) => $a['order'] <=> $b['order']);
        foreach ($this->elements as $element) {
            $builder->add($element['name'], $element['type'], $element['options']);
        }
    }
}
