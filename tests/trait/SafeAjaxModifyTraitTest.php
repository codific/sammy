<?php

namespace App\Tests\trait;

use App\Entity\Abstraction\AbstractEntity;
use App\Entity\Abstraction\UserModifiableFieldsInterface;
use App\Entity\Group;
use App\Entity\User;
use App\Traits\SafeAjaxModifyTrait;
use Codific\Bundle\UtilitiesBundle\Interface\EntityInterface;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SafeAjaxModifyTraitTest extends TestCase
{

    /**
     * @dataProvider safeAjaxModifyProvider
     * @throws \Exception
     */
    public function testSafeAjaxModify(
        array $modifiableFields,
        array $userModifiableFields,
        ?array $topLevelWhitelist,
        array $requestArray,
        bool $expectSuccess
    ) {
        $selfMock = self::createPartialMock(ClassWithTrait::class, ['abstractAjaxModify']);
        $selfMock->expects(self::any())->method('abstractAjaxModify')->willReturn(new JsonResponse());


        $entity = new ExampleEntity();
        $entity->modifiableFields = $modifiableFields;
        $entity->userModifiableFields = $userModifiableFields;

        $request = new Request();
        $request->request = new InputBag();
        foreach ($requestArray as $key => $value){
            $request->request->set($key,$value);
        }


        $response = $selfMock->safeAjaxModify($request, $entity, $topLevelWhitelist);
        $expectedStatus = $expectSuccess ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;
        self::assertEquals($expectedStatus, $response->getStatusCode());
    }

    public function safeAjaxModifyProvider(): Generator
    {
        yield "Positive test 0" => [
            ['example'],
            ['example'],
            ['example'],
            [
                'name' => 'example',
            ],
            true,
        ];

        yield "Negative test 0A" => [
            [],
            [],
            [],
            [
                'name' => '',
            ],
            false,
        ];

        yield "Negative test 0B" => [
            [],
            [],
            [],
            [
                'name' => 'example',
            ],
            false,
        ];

        yield "Negative test 0C" => [
            ['example'],
            [],
            [],
            [
                'name' => 'example',
            ],
            false,
        ];

        yield "Negative test 0D" => [
            [],
            ['example'],
            [],
            [
                'name' => 'example',
            ],
            false,
        ];

        yield "Negative test 0E" => [
            [],
            [],
            ['example'],
            [
                'name' => 'example',
            ],
            false,
        ];

        yield "Negative test 0F" => [
            [],
            ['example'],
            ['example'],
            [
                'name' => 'example',
            ],
            false,
        ];

        yield "Negative test 0G" => [
            ['example'],
            ['example'],
            [],
            [
                'name' => 'example',
            ],
            false,
        ];

        yield "Negative test 0H" => [
            ['example'],
            ['example'],
            ['example'],
            [
                'name' => 'Example',
            ],
            false,
        ];


        yield "Positive test 1" => [
            ['f1','f2','f3','f4'],
            ['f1','f2','f3'],
            ['f1','f2'],
            [
                'name' => 'f1',
            ],
            true,
        ];


        yield "Negative test 1A" => [
            ['f1','f2','f3','f4'],
            ['f1','f2','f3'],
            ['f1','f2'],
            [
                'name' => 'f3',
            ],
            false,
        ];

        yield "Negative test 1B" => [
            ['f1','f2','f3','f4'],
            ['f1','f2','f3'],
            ['f1','f2'],
            [
                'name' => 'f4',
            ],
            false,
        ];

        yield "Negative test 1C" => [
            ['f1','f2','f3','f4'],
            ['f1','f2','f3'],
            ['f1','f2'],
            [
                'name' => 'f5',
            ],
            false,
        ];

        yield "Negative test 1D" => [
            ['f1','f2','f3','f4'],
            ['f1','f2','f3'],
            ['f1','f2'],
            [
                'name' => 'f7',
            ],
            false,
        ];

        yield "Negative test 1E" => [
            ['f1','f2','f3','f4'],
            ['f1','f2','f3'],
            ['f1','f2'],
            [
                'name' => 'F1',
            ],
            false,
        ];

        yield "Positive test 2A" => [
            ['f1','f2','f3'],
            ['f1','f2'],
            null,
            [
                'name' => 'f1',
            ],
            true,
        ];


        yield "Negative test 2A" => [
            ['f1','f2','f3'],
            ['f1','f2'],
            null,
            [
                'name' => 'f3',
            ],
            false,
        ];

        yield "Negative test 2B" => [
            ['f1','f2','f3'],
            ['f1','f2'],
            null,
            [
                'name' => 'F1',
            ],
            false,
        ];

        yield "Negative test 2C" => [
            ['f1','f2','f3'],
            ['f1','f2'],
            null,
            [
                'name' => null,
            ],
            false,
        ];

        yield "Negative test 2D" => [
            ['f1','f2','f3'],
            ['f1','f2'],
            null,
            [
                'name' => '',
            ],
            false,
        ];
    }

}

final class ExampleEntity extends AbstractEntity implements UserModifiableFieldsInterface
{

    public array $modifiableFields = [];
    public array $userModifiableFields = [];

    public function __toString(): string
    {
        return "";
    }

    public function getModifiableFields(): array
    {
        return $this->modifiableFields;
    }

    public function getUserModifiableFields(): array
    {
        return $this->userModifiableFields;
    }
}

class ClassWithTrait
{
    use SafeAjaxModifyTrait;
}