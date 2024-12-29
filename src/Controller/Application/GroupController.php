<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\Group;
use App\Entity\Project;
use App\Form\Admin\GroupType;
use App\Repository\GroupRepository;
use App\Repository\ProjectRepository;
use App\Service\GroupService;
use App\Service\ProjectService;
use App\Service\SanitizerService;
use App\Service\UserService;
use App\Traits\SafeAjaxModifyTrait;
use App\Traits\UtilsBundle\CrudDeleteTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/group', name: 'group_')]
#[IsGranted('ROLE_MANAGER')]
class GroupController extends AbstractController
{
    use CrudDeleteTrait;
    use SafeAjaxModifyTrait;

    #[Route('/index', name: 'index')]
    public function index(Request $request, GroupRepository $groupRepository, ProjectService $projectService, GroupService $groupService): Response
    {
        $user = $this->getUser();
        $page = $request->query->getInt('page', 1);
        $groups = $groupRepository->findWithoutParentsPaginated($page);

        return $this->render(
            'application/group/index.html.twig',
            [
                'groups' => $groups,
                'allProjects' => $projectService->getAvailableProjectsForUser($user),
                'groupProjectData' => $projectService->getProjectsIndexedByGroup(),
                'addGroupForm' => $this->createForm(GroupType::class, new Group())->createView(),
                'queryParams' => $request->query->all(),
                'groupsByParent' => $groupService->orderGroupByParent(),
            ]
        );
    }

    #[Route('/filter', name: 'filter')]
    public function filter(Request $request, GroupRepository $groupRepository, GroupService $groupService, ProjectService $projectService): Response
    {
        $user = $this->getUser();
        $page = $request->query->getInt('page', 1);
        $searchTerm = $request->query->get('searchTerm', '');
        $groups = $groupRepository->findAllIndexedById(searchTerm: $searchTerm, page: $page, returnPaginated: true);

        return $this->render(
            'application/group/table-paginator-container.html.twig',
            [
                'groups' => $groups,
                'allProjects' => $projectService->getAvailableProjectsForUser($user),
                'groupProjectData' => $projectService->getProjectsIndexedByGroup(),
                'addGroupForm' => $this->createForm(GroupType::class, new Group())->createView(),
                'queryParams' => $request->query->all(),
                'groupsByParent' => $searchTerm === "" ? $groupService->orderGroupByParent() : [],
            ]
        );
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function add(Request $request, GroupService $groupService, UserService $userService, SanitizerService $sanitizer): Response
    {
        $group = new Group();
        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $group = $groupService->createGroup($sanitizer->sanitizeValue($group->getName()), $group->getParent());
            $this->addFlash('success', $this->translator->trans('application.group.save_success', ['project' => trim("$group")], 'application'), true);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $formError = $form->getErrors(true)->current();
            $msg = $formError->getMessage();
            $failedValue = $formError->getOrigin()->getData();
            $this->addFlash('error', "({$failedValue}) {$msg}", true);
        }

        return $this->redirectToRoute('app_group_index');
    }

    #[Route('/ajaxindex/{id}', name: 'ajaxindex', defaults: ["id" => null], methods: ['GET'])]
    public function ajaxIndex(Request $request, GroupService $groupService, ?Group $selectedGroup): JsonResponse
    {
        $search = $request->query->get("term");
        $result = $groupService->getPossibleParentNamesAndIds($selectedGroup, $search);

        return new JsonResponse($result);
    }

    #[Route('/ajaxModify/{id}', name: 'ajaxmodify', requirements: ['id' => "\d+"], methods: ['POST'])]
    #[IsGranted('GROUP_EDIT', 'group')]
    public function ajaxModify(Request $request, Group $group, GroupRepository $groupRepository, GroupService $groupService): JsonResponse
    {
        if ($request->request->get("name") === "parent" && $request->request->get("value") !== "0") {
            $targetGroup = $groupRepository->findOneBy(["id" => $request->request->get("value")]);
            if ($groupService->doesGroupContainsInParents($targetGroup, $group)) {
                return new JsonResponse([], Response::HTTP_FORBIDDEN);
            }
        }

        return $this->safeAjaxModify($request, $group);
    }

    #[Route('/{id}', name: 'delete', requirements: ['group' => "\d+"], methods: ['DELETE'])]
    #[IsGranted('GROUP_EDIT', 'group')]
    public function delete(Request $request, Group $group): Response
    {
        return $this->abstractDelete($request, $group);
    }

    #[Route('/editProjects/{id}', name: 'edit_projects', requirements: ['id' => "\d+"], methods: ['POST'])]
    #[IsGranted('GROUP_EDIT', 'group')]
    public function editProjects(Request $request, Group $group, ProjectService $projectService, ProjectRepository $projectRepository): Response
    {
        $requestProjectIds = $request->request->all('projectIds');
        $projects = $projectRepository->findBy(['id' => $requestProjectIds]);
        $projectIds = array_map(fn(Project $project) => $project->getId(), $projects);
        $projectService->modifyGroupProjects($group, $projectIds);
        $this->addFlash('success', $this->translator->trans('application.group.project_edit_success', [], 'application'));

        return $this->redirectToRoute('app_group_index');
    }
}
