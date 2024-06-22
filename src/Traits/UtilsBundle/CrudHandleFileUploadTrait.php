<?php
declare(strict_types=1);

namespace App\Traits\UtilsBundle;

use App\Entity\Abstraction\AbstractEntity;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait CrudHandleFileUploadTrait
{
    public function handleFileUpload(FormInterface $form, AbstractEntity $entity): AbstractEntity
    {
        $uploadFields = $entity->getUploadFields();
        foreach ($uploadFields as $field) {
            if (isset($form[$field])) {
                $file = $form[$field]->getData();
                if ($file instanceof UploadedFile) {
                    $fileName = $this->uploadService->upload($file, $field);
                    $entity->{'set'.ucfirst($field)}($fileName); // @phpstan-ignore-line
                }
                if (isset($form['remove'.ucfirst($field)])) {
                    $remove = $form['remove'.ucfirst($field)]->getData();
                    if ($remove) { // @phpstan-ignore-line
                        $entity->{'set'.ucfirst($field)}(null); // @phpstan-ignore-line
                    }
                }
            }
        }

        return $entity;
    }
}
