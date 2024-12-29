<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\Project;
use App\Event\Admin\Create\ProjectCreatedEvent;
use App\Exception\AnswerNotFoundInDatabaseException;
use App\Exception\QuestionNotFoundInToolboxException;
use App\Exception\SammVersionNotFoundInToolboxException;
use App\Exception\ZeroSheetsProvidedForImportException;
use App\Form\Application\ProjectType;
use App\Form\Application\TemplateProjectType;
use App\Form\Application\ToolboxType;
use App\Repository\GroupRepository;
use App\Repository\MetamodelRepository;
use App\Repository\ProjectRepository;
use App\Service\AssessmentService;
use App\Service\GroupService;
use App\Service\Processing\SammToolboxImporterService;
use App\Service\ProjectService;
use App\Service\SanitizerService;
use App\Service\ScoreService;
use App\Traits\SafeAjaxModifyTrait;
use App\Traits\UtilsBundle\CrudDeleteTrait;
use App\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/project', name: 'project_')]
#[IsGranted('ROLE_MANAGER')]
class ProjectController extends AbstractController
{
    use SafeAjaxModifyTrait;
    use CrudDeleteTrait;

    #[Route('/index', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        ProjectRepository $projectRepository,
        GroupService $groupService,
        GroupRepository $groupRepository,
        ProjectService $projectService,
        MetamodelRepository $metamodelRepository,
        ScoreService $scoreService,
    ): Response {
        $user = $this->getUser();
        $page = $request->query->getInt('page', 1);
        $searchTerm = $request->query->get('searchTerm', '');
        $archived = (int)$request->query->get('archived', '0');
        $projects = $projectRepository->findOptimized(searchTerm: $searchTerm, page: $page, archived: $archived, returnPaginated: true);
        $projectMkpi = $scoreService->getProjectScores(new \DateTime('now'), true, ...$projects->getResults());

        return $this->render(
            'application/project/index.html.twig',
            [
                'projects' => $projects,
                'projectScore' => $projectMkpi,
                'allGroups' => $groupRepository->findAllIndexedById(),
                'projectsWithUserAccess' => $projectService->getAvailableProjectsForUser($user),
                'groupProjectData' => $projectService->getGroupsIndexedByProject(),
                'addProjectForm' => $this->createForm(ProjectType::class, new Project(), [
                    'metamodels' => $metamodelRepository->findAll(),
                    ProjectType::GROUPS => $groupService->getGroupsData(),
                ])->createView(),
                'importToolboxForm' => $this->createForm(ToolboxType::class),
                'queryParams' => $request->query->all(),
            ]
        );
    }

    #[Route('/templates', name: 'templates', methods: ['GET'])]
    public function templates(
        Request $request,
        ProjectRepository $projectRepository,
        ScoreService $scoreService,
        MetamodelRepository $metamodelRepository,
    ): Response {
        $user = $this->getUser();
        $page = $request->query->getInt('page', 1);
        $templateProjects = $projectRepository->findOptimized(true, page: $page, returnPaginated: true);

        $projectScore = $scoreService->getProjectScores(new \DateTime('now'), true, ...$templateProjects->getResults());

        return $this->render(
            'application/project/templates.html.twig',
            [
                'templateProjects' => $templateProjects,
                'projectScore' => $projectScore,
                'addTemplateForm' => $this->createForm(TemplateProjectType::class, new Project(), ['metamodels' => $metamodelRepository->findAll()])->createView(),
                'queryParams' => [],
            ]
        );
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function add(
        Request $request,
        ProjectService $projectService,
        GroupService $groupService,
        MetamodelRepository $metamodelRepository,
        SanitizerService $sanitizer
    ): Response {
        $user = $this->getUser();
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project, [
            'metamodels' => $metamodelRepository->findAll(),
            ProjectType::GROUPS => $groupService->getGroupsData(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $groups = $form->get(ProjectType::GROUPS)->getData();
            $project = $projectService->createProject(
                $sanitizer->sanitizeValue($project->getName()),
                $project->getDescription(),
                $groups,
                $project->getMetamodel()->getId(),
                $project->getValidationThreshold()
            );
            $this->eventDispatcher->dispatch(new ProjectCreatedEvent($request, $project));
            $this->addFlash('success', $this->translator->trans('application.project.save_success', ['project' => trim("$project")], 'application'), true);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $formError = $form->getErrors(true)->current();
            $msg = $formError->getMessage();
            $failedValue = $formError->getOrigin()->getData();
            $this->addFlash('error', "({$failedValue}) {$msg}", true);
        }

        return $this->redirectToRoute('app_project_index');
    }

    #[Route('/addTemplate', name: 'add_template', methods: ['POST'])]
    public function addTemplate(
        Request $request,
        ProjectService $projectService,
        MetamodelRepository $metamodelRepository,
        SanitizerService $sanitizer
    ): Response {
        $user = $this->getUser();
        $project = new Project();

        $form = $this->createForm(TemplateProjectType::class, $project, ['metamodels' => $metamodelRepository->findAll()]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $project = $projectService->createProject(
                $sanitizer->sanitizeValue($project->getName()),
                $project->getDescription(),
                [],
                $project->getMetamodel()->getId(),
                3,
                true
            );
            $this->eventDispatcher->dispatch(new ProjectCreatedEvent($request, $project));
            $this->addFlash('success', $this->translator->trans('application.project.save_success', ['project' => trim("$project")], 'application'));
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $formError = $form->getErrors(true)->current();
            $msg = $formError->getMessage();
            $failedValue = $formError->getOrigin()->getData();
            $this->addFlash('error', "({$failedValue}) {$msg}", true);
        }

        return $this->redirectToRoute('app_project_templates');
    }

    #[Route('/import-toolbox', name: 'import_toolbox', methods: ['POST'])]
    public function importToolbox(
        Request $request,
        SammToolboxImporterService $toolboxImporterService,
    ): Response {
        $user = $this->getUser();


        $form = $this->createForm(ToolboxType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $toolboxFile = $form->get('toolboxFile')->getData();
            $autoValidate = $form->get('autoValidate')->getData();
            try {
                $project = $toolboxImporterService->import($toolboxFile, $autoValidate, $this->getUser());
                $this->eventDispatcher->dispatch(new ProjectCreatedEvent($request, $project));
                $this->addFlash('success', $this->translator->trans('application.project.save_success', ['project' => trim("$project")], 'application'));
            } catch (SammVersionNotFoundInToolboxException|ZeroSheetsProvidedForImportException|QuestionNotFoundInToolboxException|AnswerNotFoundInDatabaseException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $formError = $form->getErrors(true)->current();
            $msg = $formError->getMessage();
            $this->addFlash('error', "{$msg}");
        }

        return $this->redirectToRoute('app_project_index');
    }

    #[Route('/{id}', name: 'delete', requirements: ['project' => "\d+"], methods: ['DELETE'])]
    #[IsGranted('PROJECT_EDIT', 'project')]
    public function delete(Request $request, Project $project, ProjectService $projectService): Response
    {
        if ($project->isTemplate()) {
            $projectService->deleteTemplateProjectLinks($project);
        }

        $this->abstractDelete($request, $project);

        return $project->isTemplate() ? $this->redirectToRoute('app_project_templates', $request->query->all()) : $this->redirectToRoute('app_project_index', $request->query->all());
    }

    #[Route('/editGroups/{id}', name: 'edit_groups', requirements: ['id' => "\d+"], methods: ['POST'])]
    #[IsGranted('PROJECT_EDIT', 'project')]
    public function editGroups(Request $request, Project $project, ProjectService $projectService): Response
    {
        $groupIds = $request->request->all('groupIds');
        $projectService->modifyProjectGroups($project, $groupIds);
        $this->addFlash('success', $this->translator->trans('application.project.team_edit_success', [], 'application'));

        return $this->redirectToRoute('app_project_index');
    }

    #[Route('/ajaxModify/{id}', name: 'ajaxmodify', requirements: ['id' => "\d+"], methods: ['POST'])]
    #[IsGranted('PROJECT_EDIT', 'project')]
    public function ajaxModify(Request $request, Project $project): JsonResponse
    {
        $allowed = $project->getUserModifiableFields();
        if ($project->isTemplate()) {
            $allowed = array_diff($allowed, ['templateProject']);
        }

        $name = $request->request->get('name');
        $value = $request->get('value');
        if ($name === 'validationThreshold') {
            $float = (float)$value;
            if ($float > 3) {
                $float = 3;
            }
            if ($float < -10) {
                $float = -10;
            }
            $value = number_format($float, 2, '.', '');
            $request->request->set('value', $value);
        }

        return $this->safeAjaxModify($request, $project, $allowed);
    }

    #[Route('/ajaxindexTemplates', name: 'ajaxindexTemplates', methods: ['GET'])]
    public function ajaxindexTemplates(
        Request $request,
        ProjectRepository $projectRepository,
    ): JsonResponse {
        $selectedProject = $projectRepository->findOneBy(['id' => $request->query->get('id')]);
        $templateProjectsData = array_reduce(
            $projectRepository->findBy([
                'template' => true,
                'metamodel' => $selectedProject->getMetamodel(),
            ], ['id' => 'ASC']),
            fn(array $result, Project $project) => array_merge($result, [['value' => $project->getId(), 'text' => $project->getName()]]),
            []
        );

        return new JsonResponse($templateProjectsData);
    }

    #[Route('/filter', name: 'filter', methods: ['GET'])]
    public function filter(
        Request $request,
        ProjectRepository $projectRepository,
        ScoreService $scoreService,
        GroupService $groupService,
        GroupRepository $groupRepository,
        ProjectService $projectService,
        MetamodelRepository $metamodelRepository,
    ): Response {
        $user = $this->getUser();
        $page = $request->query->getInt('page', 1);
        $searchTerm = $request->query->get('searchTerm', '');
        $archived = $request->query->getInt('archived', 0);
        $projects = $projectRepository->findOptimized(searchTerm: $searchTerm, page: $page, archived: $archived, returnPaginated: true);
        $projectMkpi = $scoreService->getProjectScores(new \DateTime('now'), true, ...$projects->getResults());

        return $this->render(
            'application/project/project_table.html.twig',
            [
                'projects' => $projects,
                'projectScore' => $projectMkpi,
                'allGroups' => $groupRepository->findAllIndexedById(),
                'projectsWithUserAccess' => $projectService->getAvailableProjectsForUser($user),
                'groupProjectData' => $projectService->getGroupsIndexedByProject(),
                'addProjectForm' => $this->createForm(ProjectType::class, new Project(), [
                    'metamodels' => $metamodelRepository->findAll(),
                    ProjectType::GROUPS => $groupService->getGroupsData(),
                ])->createView(),
                'importToolboxForm' => $this->createForm(ToolboxType::class),
                'queryParams' => $request->query->all(),
            ]
        );
    }

    #[Route('/{id}', name: 'unarchive', requirements: ['project' => "\d+"], methods: ['POST'])]
    #[IsGranted('PROJECT_EDIT', 'project')]
    public function unarchive(Request $request, Project $project, ProjectService $projectService): Response
    {
        $projectService->unarchiveProject($project);

        $this->addFlash('success', $this->translator->trans('application.project.restore_success', ['project' => trim((string)$project)], 'application'));

        return $this->redirectToRoute('app_project_index', $request->query->all());
    }

}
