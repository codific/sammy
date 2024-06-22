<?php

declare(strict_types=1);

namespace App\Traits;

use App\Traits\UtilsBundle\CrudAddTrait;
use App\Traits\UtilsBundle\CrudDeleteTrait;
use App\Traits\UtilsBundle\CrudEditTrait;
use App\Traits\UtilsBundle\CrudHandleFileUploadTrait;

trait ApplicationCrudTrait
{
    use CrudAddTrait;
    use CrudEditTrait;
    use CrudDeleteTrait;
    use CrudHandleFileUploadTrait;
}
