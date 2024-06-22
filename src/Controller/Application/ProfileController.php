<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Enum\Role;
use App\Form\Application\ChangePasswordType;
use App\Form\Application\UserType;
use App\Repository\GroupRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile', name: 'profile_')]
class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, EntityManagerInterface $entityManager, GroupRepository $groupRepository, UserService $userService): RedirectResponse|Response
    {
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user, [
            UserType::DISABLE_EMAIL => (bool)$user->getEmail(),
            UserType::DISABLE_ROLES => !in_array(Role::MANAGER->string(), $user->getRoles(), true),
            UserType::DISABLE_TEAMS => true,
            UserType::SHOW_TIMEZONE_SUPPORT => true,
            'groups' => $groupRepository->findAllByUser($user),
        ]);

        $oldRoles = $this->getUser()->getRoles();
        $groups = $groupRepository->findAllByUser($user);
        $oldGroups = array_map(fn($group) => $group->getId(), $groups);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userRequestObject = $request->request->all('user');
            if (isset($userRequestObject['roles']) || isset($userRequestObject['groups'])) {
                //this if is just to make sure that if the user is not manager and tries to manually send the payload
                if (in_array(Role::MANAGER->string(), $oldRoles, true)) {
                    $newRoles = $userRequestObject['roles'] ?? $oldRoles;
                    $newGroups = $userRequestObject['groups'] ?? $oldGroups;

                    // basically if the user is manager and if he tries to remove his own manager role
                    if (in_array(Role::MANAGER->string(), $oldRoles, true) && !in_array(Role::MANAGER->string(), $newRoles, true)) {
                        $newRoles[] = Role::MANAGER->string();
                    }

                    $userService->editUser($this->getUser(), $newRoles, $newGroups);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', $this->trans('application.general.profile_save_success', [], 'application'));

            return $this->redirectToRoute('app_profile_profile');
        }

        $response = $this->render('application/profile/profile.html.twig', ['form' => $form->createView()]);
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    #[IsGranted(new Expression('is_granted("USER_CHANGE_PASSWORD", user)'))]
    #[Route('/changePassword', name: 'change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('newPassword')->getData();

            $user = $this->getUser();
            $user->setPassword($hasher->hashPassword($user, $password));
            $entityManager->flush();

            $request->getSession()->migrate();

            $this->addFlash('success', $this->trans('application.general.password_change_success', [], 'application'));

            return $this->redirectToRoute('app_dashboard_index');
        }

        return $this->render('application/profile/change_password.html.twig', ['form' => $form->createView()]);
    }
}
