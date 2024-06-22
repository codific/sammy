<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Group;
use App\Entity\GroupProject;
use App\Entity\Metamodel;
use App\Entity\Project;
use App\Repository\GroupProjectRepository;
use App\Repository\GroupRepository;
use App\Repository\ProjectRepository;
use App\Service\GroupService;
use App\Service\ScoreService;
use App\Tests\_support\AbstractKernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class GroupServiceTest extends AbstractKernelTestCase
{
    private GroupService $groupService;

    public function setUp(): void
    {
        parent::setUp();
        $this->groupService = self::getContainer()->get(GroupService::class);
    }

    /**
     * @dataProvider testCreateGroupDataProvider
     */
    public function testCreateGroup(string $expectedGroupName, ?Group $expectedParent)
    {
        if ($expectedParent !== null) {
            $this->entityManager->persist($expectedParent);
            $this->entityManager->flush();
        }
        $group = $this->groupService->createGroup($expectedGroupName, $expectedParent);
        self::assertEquals($group->getName(), $expectedGroupName);
        self::assertEquals($group->getParent(), $expectedParent);
    }

    private function testCreateGroupDataProvider(): array
    {
        return [
            "Positive 1, expected group with parent" => [
                "Test group", //expected group name
                new Group(), //expected parent
            ],
            "Positive 2, expected group without parent" => [
                "Test group", //expected group name
                null, //expected parent
            ],
        ];
    }

    public function testGetGroupsData()
    {
        ($group = new Group())->setId(2);
        ($group2 = new Group())->setId(1);
        $this->entityManager->persist($group);
        $this->entityManager->persist($group2);
        $this->entityManager->flush();
        self::assertCount(3, $this->groupService->getGroupsData());
    }

    /**
     * @dataProvider orderGroupByParentDataProvider
     */
    public function testOrderGroupByParent(array $groupsToSave, array $childGroups, array $expectedParentGroups, int $expectedGlobalGroupsCount): void
    {
        foreach ($groupsToSave as $group) {
            $this->entityManager->persist($group);
        }
        $this->entityManager->flush();

        $expectedParentGroupIds = array_map(fn(Group $group) => $group->getId(), $expectedParentGroups);

        $groupService = $this->getMockBuilder(GroupService::class)->setConstructorArgs([
            $this->entityManager,
            self::getContainer()->get(GroupRepository::class),
            self::getContainer()->get(TranslatorInterface::class),
            self::getContainer()->get(ScoreService::class),
            self::getContainer()->get(GroupProjectRepository::class),
            self::getContainer()->get(ProjectRepository::class),
        ])->onlyMethods([])->getMock();

        $result = $groupService->orderGroupByParent();

        self::assertCount($expectedGlobalGroupsCount, $result['global']);
        foreach ($expectedParentGroupIds as $parentGroupId) {
            self::assertTrue(isset($result[$parentGroupId]));
        }
    }

    public function orderGroupByParentDataProvider(): array
    {
        $group = new Group();
        $group2 = new Group();
        $group3 = new Group();
        $group3->setParent($group);
        $group4 = new Group();
        $group4->setParent($group);
        $group5 = new Group();
        $group5->setParent($group3);
        $group6 = new Group();

        return [
            "Positive 1, expected only one parent group returned" => [
                [$group, $group2], // to save
                [], //child groups
                [], // expected parent groups
                3, // expected global groups count
            ],
            "Positive 2, expected no groups returned" => [
                [$group2], // to save
                [], // child groups
                [], // expected parent groups
                2, // expected global groups count
            ],
            "Positive 3, expected complex structure returned" => [
                [$group, $group2, $group3, $group4, $group5, $group6], // to save
                [$group3, $group4, $group5], // child groups
                [$group, $group3], // expected parent groups
                4, // expected global groups count
            ],
        ];
    }

    /**
     * @dataProvider getPossibleParentNamesAndIdsDataProvider
     */
    public function testGetPossibleParentNamesAndIds(array $returnedGroupsFromDb, Group $selectedGroup, array $expectedResult): void
    {
        $groupRepository = $this->createMock(GroupRepository::class);
        $groupRepository
            ->method("findAllIndexedById")
            ->willReturn($returnedGroupsFromDb);

        $groupService = $this->getMockBuilder(GroupService::class)->setConstructorArgs([
            $this->entityManager,
            $groupRepository,
            self::getContainer()->get(TranslatorInterface::class),
            self::getContainer()->get(ScoreService::class),
            self::getContainer()->get(GroupProjectRepository::class),
            self::getContainer()->get(ProjectRepository::class),
        ])->onlyMethods([])->getMock();

        $actualResult = $groupService->getPossibleParentNamesAndIds($selectedGroup);

        self::assertEquals($expectedResult, $actualResult);
    }

    public function getPossibleParentNamesAndIdsDataProvider(): array
    {
        $group1 = new Group();
        $group1->setName("Group 1");
        $group2 = new Group();
        $group2->setName("Group 2");
        $group3 = new Group();
        $group3->setName("Group 3");
        $group4 = new Group();
        $group4->setName("Group 4");
        $group5 = new Group();
        $group5->setName("Group 5")->setParent($group1);
        $group6 = new Group();
        $group6->setName("Group 6")->setParent($group5);

        return [
            "Positive 1, expected five groups returned" => [
                [1 => $group1, 2 => $group2, 3 => $group3, 4 => $group4, 5 => $group5, 6 => $group6], // returned from db
                $group4, // selected group
                [
                    [
                        "text" => "No parent",
                        "value" => 0,
                    ],
                    [
                        "text" => "Group 1",
                        "value" => 1,
                    ],
                    [
                        "text" => "Group 2",
                        "value" => 2,
                    ],
                    [
                        "text" => "Group 3",
                        "value" => 3,
                    ],
                    [
                        "text" => "Group 5",
                        "value" => 5,
                    ],
                    [
                        "text" => "Group 6",
                        "value" => 6,
                    ],
                ], // expected
            ],
            "Positive 2, expected no groups returned" => [
                [], // returned from db
                $group1, // selected group
                [
                    [
                        "text" => "No parent",
                        "value" => 0,
                    ],
                ], // expected
            ],
            "Positive 3, expected three groups returned, group 5 have parent - child connection" => [
                [1 => $group1, 2 => $group2, 3 => $group3, 4 => $group4, 5 => $group5], // returned from db
                $group1, // selected group
                [
                    [
                        "text" => "No parent",
                        "value" => 0,
                    ],
                    [
                        "text" => "Group 2",
                        "value" => 2,
                    ],
                    [
                        "text" => "Group 3",
                        "value" => 3,
                    ],
                    [
                        "text" => "Group 4",
                        "value" => 4,
                    ],
                ], // expected
            ],
            "Positive 4, expected three groups returned, group 5 and 6 have parent - child connection" => [
                [1 => $group1, 2 => $group2, 3 => $group3, 4 => $group4, 5 => $group5, 6 => $group6], // returned from db
                $group1, // selected group
                [
                    [
                        "text" => "No parent",
                        "value" => 0,
                    ],
                    [
                        "text" => "Group 2",
                        "value" => 2,
                    ],
                    [
                        "text" => "Group 3",
                        "value" => 3,
                    ],
                    [
                        "text" => "Group 4",
                        "value" => 4,
                    ],
                ], // expected
            ],
        ];
    }

    /**
     * @dataProvider testDoesGroupContainsInParentsDataProvider
     */
    public function testDoesGroupContainsInParents(Group $selectedGroup, Group $parentToSearchFor, bool $expectedResult)
    {
        $result = $this->groupService->doesGroupContainsInParents($selectedGroup, $parentToSearchFor);
        self::assertEquals($expectedResult, $result);
    }

    private function testDoesGroupContainsInParentsDataProvider(): array
    {
        $group1 = new Group();
        $group2 = new Group();
        $group3 = new Group();
        $group3->setParent($group1);
        $group4 = new Group();
        $group4->setParent($group3);

        return [
            "Positive 1, unrelated groups expect false" => [
                $group1, // selected
                $group2, // to check parent
                false,
            ],
            "Positive 2, parent <-> child relationship, expect true" => [
                $group3, // selected
                $group1, // to check parent
                true,
            ],
            "Positive 3, child <-> parent relationship, expect false" => [
                $group1, // selected
                $group3, // to check parent
                false,
            ],
            "Positive 4, parent <-> grand child relationship, expect true" => [
                $group4, // selected
                $group1, // to check parent
                true,
            ],
            "Positive 5, parent <-> child relationship, expect true" => [
                $group4, // selected
                $group3, // to check parent
                true,
            ],
        ];
    }

    /**
     * @dataProvider getSammPropagatedLeafGroupScoresDataProvider
     */
    public function testGetSammPropagatedLeafGroupScores(
        array $groupProjectResult,
        array $groupsFromDb,
        array $scoreServiceResult,
        array $expectedResult
    ): void {
        $scoreService = $this->createMock(ScoreService::class);
        $scoreService
            ->method("getProjectScores")
            ->willReturn($scoreServiceResult);

        $groupRepository = $this->createMock(GroupRepository::class);
        $groupRepository
            ->method("findAllIndexedById")
            ->willReturn($groupsFromDb);

        $groupRepository
            ->method("findBy")
            ->willReturn($groupsFromDb);
        $groupRepository
            ->method("findAll")
            ->willReturn($groupsFromDb);


        $groupProjectRepository = $this->createMock(GroupProjectRepository::class);
        $groupProjectRepository
            ->method("findAllOptimized")
            ->willReturn($groupProjectResult);

        $groupService = $this->getMockBuilder(GroupService::class)->setConstructorArgs([
            $this->entityManager,
            $groupRepository,
            self::getContainer()->get(TranslatorInterface::class),
            $scoreService,
            $groupProjectRepository,
            self::getContainer()->get(ProjectRepository::class),
        ])->onlyMethods([])->getMock();

        $actualResult = $groupService->getSammPropagatedLeafGroupScores();
        self::assertEquals($expectedResult, $actualResult);
    }

    private function getSammPropagatedLeafGroupScoresDataProvider(): array
    {
        $group1 = new Group();
        $group2 = new Group();
        $group1->setId(1);
        $group2->setId(2);
        $project1 = new Project();
        $project2 = new Project();
        $project3 = new Project();
        $project1->setId(11);
        $project2->setId(12);
        $project3->setId(13);
        $metamodel1 = new Metamodel();
        $metamodel1->setId(1);
        $project1->setMetamodel($metamodel1);
        $project2->setMetamodel($metamodel1);
        $project3->setMetamodel($metamodel1);
        $group2->setParent($group1);
        $groupProject = new GroupProject();
        $groupProject->setGroup($group1)->setProject($project1);
        $groupProject2 = new GroupProject();
        $groupProject2->setGroup($group2)->setProject($project2);
        $group3 = new Group();
        $group3->setId(3);
        $groupProject3 = new GroupProject();
        $groupProject3->setGroup($group3)->setProject($project2);
        $group4 = new Group();
        $group4->setId(4);
        $group4->setParent($group1);
        $groupProject4 = new GroupProject();
        $groupProject4->setGroup($group4)->setProject($project3);

        return [
            "Positive 1 have one child of parent with score 1.00, expect propagation of 1.00. Parent have 0.00 own score" => [
                [$groupProject2], //Group projects in db
                [
                    1 => $group1,
                    2 => $group2,
                ], // returned groups from db
                [
                    11 => ["arithmeticMean" => 1.00],
                    12 => ["arithmeticMean" => 1.00],
                ], // returned scores
                [
                    2 => "1.00",
                    1 => "1.00",
                ], //expected result
            ],
            "Positive 2 have one child of parent with score 1.00, expect propagation of 1.00. Parent have 1.50 own score." => [
                [$groupProject2], //Group projects in db
                [
                    1 => $group1,
                    2 => $group2,
                ], // returned groups from db
                [
                    11 => ["arithmeticMean" => 1.50],
                    12 => ["arithmeticMean" => 1.00],
                ], // returned scores
                [
                    2 => "1.00",
                    1 => "1.00",
                ], //expected result
            ],
            "Positive 3 have no child. Parent have 1.50 own score. Expect 1.50 score" => [
                [$groupProject3], //Group projects in db
                [
                    3 => $group3,
                ], // returned groups from db
                [
                    11 => ["arithmeticMean" => 1.50],
                    12 => ["arithmeticMean" => 1.50],
                ], // returned scores
                [
                    3 => "1.50",
                ], //expected result
            ],
            "Positive 4 parent have 2 childs. One child 1.00 the other with 1.50. Expect propagation of 1.25 to parent" => [
                [$groupProject2, $groupProject4], //Group projects in db
                [
                    1 => $group1,
                    2 => $group2,
                    4 => $group4,
                ], // returned groups from db
                [
                    11 => ["arithmeticMean" => 1.50],
                    12 => ["arithmeticMean" => 1.00],
                    13 => ["arithmeticMean" => 1.50],
                ], // returned scores
                [
                    2 => "1.00",
                    4 => "1.50",
                    1 => "1.25",
                ], //expected result
            ],
        ];
    }

}