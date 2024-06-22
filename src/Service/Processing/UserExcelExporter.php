<?php

declare(strict_types=1);

namespace App\Service\Processing;

use App\Entity\User;
use App\Enum\Role;
use App\Repository\AssessmentAnswerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserExcelExporter extends ExcelExporter
{
    public function __construct(
        KernelInterface $httpKernel,
        Filesystem $fileSystem,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AssessmentAnswerRepository $answerRepository,
        private readonly TranslatorInterface $translator
    ) {
        parent::__construct($httpKernel, $fileSystem);
    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportUsersData(): string
    {
        $users = $this->userRepository->findAllIndexedByName();
        $headers = [
            "Name",
            "Surname",
            "Email",
            "Roles",
        ];
        $data = [];

        /** @var User $user */
        foreach ($users as $user) {
            $row = [];
            $row[] = $user->getName();
            $row[] = $user->getSurname();
            $row[] = $user->getEmail();
            $row[] = $this->getUserRoles($user);
            $data[] = $row;
        }

        return $this->export("Exported users", $headers, $data, "Exported users");
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportManagerUsersData(): string
    {

        $headers = [
            $this->translator->trans("admin.user.export.name"),
            $this->translator->trans("admin.user.export.surname"),
            $this->translator->trans("admin.user.export.email"),
            $this->translator->trans("admin.user.export.organization"),
            $this->translator->trans("admin.user.export.created_at"),
            $this->translator->trans("admin.user.export.last_login"),
            $this->translator->trans("admin.user.export.last_answer"),
        ];
        $data = [];
        $allUsers = $this->userRepository->findNonCodificManagersWithEmailAndLastLogin();

        $this->entityManager->getFilters()->disable('deleted_entity');
        $answers = $this->answerRepository->findLatestAnswersOfUsers($allUsers);
        $this->entityManager->getFilters()->enable('deleted_entity');
        /** @var User $user */
        foreach ($allUsers as $user) {
            if (array_key_exists($user->getId(), $answers)) {
                $row = [];
                $row[] = $user->getName();
                $row[] = $user->getSurname();
                $row[] = $user->getEmail();
                $row[] = "";
                $row[] = $user->getCreatedAt()->format("Y-m-d H:i") ?? "";
                $row[] = $user->getLastLogin()?->format("Y-m-d H:i") ?? "";
                $row[] = $answers[$user->getId()][1];
                $data[] = $row;
            }
        }

        return $this->export("Managers", $headers, $data, "Exported managers");
    }

    private function getUserRoles(User $user): string
    {
        $roleSymbols = [
            Role::EVALUATOR->string() => 'E',
            Role::VALIDATOR->string() => 'V',
            Role::IMPROVER->string() => 'I',
            Role::MANAGER->string() => 'M',
        ];

        $userRoles = $user->getRoles();
        $exportRoles = '';
        foreach ($roleSymbols as $roleKey => $roleSymbol) {
            if (in_array($roleKey, $userRoles, true)) {
                $exportRoles .= $roleSymbol.',';
            }
        }

        return rtrim($exportRoles, ',');
    }
}
