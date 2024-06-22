<?php

declare(strict_types=1);

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ImageTypeExtension extends AbstractTypeExtension
{
    private UrlGeneratorInterface $urlGenerator;

    /**
     * ImageTypeExtension constructor.
     */
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public static function getExtendedTypes(): iterable
    {
        return [FileType::class];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['image_property', 'image_route']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $imageUrl = null;
        $imageName = '';
        if (isset($options['image_property']) && isset($options['image_route'])) {
            $parentData = $form->getParent()->getData();
            if (null !== $parentData) {
                $accessor = PropertyAccess::createPropertyAccessor();
                $tmp = $accessor->getValue($parentData, $options['image_property']);
                if ($tmp !== null && strlen($tmp) > 0) {
                    $imageUrl = $this->urlGenerator->generate(
                        $options['image_route'],
                        ['id' => $accessor->getValue($parentData, 'id')]
                    );
                    $imageName = $tmp;
                }
            }
        }
        $view->vars['image_url'] = $imageUrl;
        $view->vars['image_name'] = $imageName;
    }
}
