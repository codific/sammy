<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType as SymfonyAbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AbstractType extends SymfonyAbstractType
{
    protected array $elements = [];

    protected function addElements(FormBuilderInterface $builder)
    {
        uasort($this->elements, function ($a, $b) {
            if ($a['order'] === $b['order']) {
                return 0;
            }

            return $a['order'] > $b['order'] ? 1 : -1;
        });
        foreach ($this->elements as $element) {
            $builder->add($element['name'], $element['type'], $element['options']);
        }
    }
}
