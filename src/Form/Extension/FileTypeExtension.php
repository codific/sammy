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

class FileTypeExtension extends AbstractTypeExtension
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['file_property', 'file_route']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $fileUrl = null;
        $fileName = '';
        if (isset($options['file_property']) && isset($options['file_route'])) {
            $parentData = $form->getParent()->getData();
            if (null !== $parentData) {
                $accessor = PropertyAccess::createPropertyAccessor();
                $tmp = $accessor->getValue($parentData, $options['file_property']);
                if ($tmp !== null && strlen($tmp) > 0) {
                    $fileUrl = $this->urlGenerator->generate(
                        $options['file_route'],
                        ['id' => $accessor->getValue($parentData, 'id')]
                    );
                    $fileName = $tmp;
                }
            }
        }
        $view->vars['file_url'] = $fileUrl;
        $view->vars['file_name'] = $fileName;
    }
}
