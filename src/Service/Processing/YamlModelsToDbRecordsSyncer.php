<?php

declare(strict_types=1);

namespace App\Service\Processing;

use App\Enum\ModelEntitySyncEnum;
use App\Repository\ActivityRepository;
use App\Repository\AnswerRepository;
use App\Repository\AnswerSetRepository;
use App\Repository\BusinessFunctionRepository;
use App\Repository\MaturityLevelRepository;
use App\Repository\PracticeLevelRepository;
use App\Repository\PracticeRepository;
use App\Repository\QuestionRepository;
use App\Repository\StreamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

class YamlModelsToDbRecordsSyncer extends ModelToDbSyncer
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly BusinessFunctionRepository $businessFunctionRepository,
        private readonly PracticeRepository $practiceRepository,
        private readonly MaturityLevelRepository $maturityLevelRepository,
        private readonly PracticeLevelRepository $practiceLevelRepository,
        private readonly StreamRepository $streamRepository,
        private readonly ActivityRepository $activityRepository,
        private readonly QuestionRepository $questionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AnswerSetRepository $answerSetRepository,
        private readonly AnswerRepository $answerRepository,
    ) {
        parent::__construct(
            $this->entityManager,
            $this->businessFunctionRepository,
            $this->practiceRepository,
            $this->streamRepository,
            $this->maturityLevelRepository,
            $this->practiceLevelRepository,
            $this->activityRepository,
            $this->answerSetRepository,
            $this->answerRepository,
            $this->questionRepository,
        );
    }

    /**
     * @return int[]
     */
    public function syncBusinessFunctions(): array
    {
        $businessFunctionsFolderPath = "{$this->getModelsFolder()}/business_functions";
        $businessFunctionFiles = $this->removeDotDirectories(scandir($businessFunctionsFolderPath));
        $added = $modified = 0;
        foreach ($businessFunctionFiles as $businessFunctionYaml) {
            $parsedYamlFile = Yaml::parseFile($businessFunctionsFolderPath.'/'.$businessFunctionYaml);
            $entityStatus = $this->syncBusinessFunction($parsedYamlFile['id'], $parsedYamlFile['name'], $parsedYamlFile['description'], $parsedYamlFile['order']);
            if ($entityStatus === ModelEntitySyncEnum::ADDED) {
                ++$added;
            } elseif ($entityStatus === ModelEntitySyncEnum::MODIFIED) {
                ++$modified;
            }
        }

        $this->entityManager->flush();

        return [$added, $modified];
    }

    /**
     * @return int[]
     */
    public function syncSecurityPractices(): array
    {
        $securityPracticesFolderPath = "{$this->getModelsFolder()}/security_practices";
        $securityPracticesFiles = $this->removeDotDirectories(scandir($securityPracticesFolderPath));
        $added = $modified = 0;
        foreach ($securityPracticesFiles as $securityPracticeYaml) {
            $parsedYamlFile = Yaml::parseFile($securityPracticesFolderPath.'/'.$securityPracticeYaml);

            $entityStatus = $this->syncSecurityPractice(
                $parsedYamlFile['id'],
                $parsedYamlFile['function'],
                $parsedYamlFile['name'],
                $parsedYamlFile['shortName'],
                $parsedYamlFile['shortDescription'],
                $parsedYamlFile['longDescription'],
                $parsedYamlFile['order']
            );

            if ($entityStatus === ModelEntitySyncEnum::ADDED) {
                ++$added;
            } elseif ($entityStatus === ModelEntitySyncEnum::MODIFIED) {
                ++$modified;
            }
        }

        $this->entityManager->flush();

        return [$added, $modified];
    }

    /**
     * @return int[]
     */
    public function syncMaturityLevels(): array
    {
        $maturityLevelsFolderPath = "{$this->getModelsFolder()}/maturity_levels";
        $maturityLevelFiles = $this->removeDotDirectories(scandir($maturityLevelsFolderPath));
        $added = $modified = 0;
        foreach ($maturityLevelFiles as $maturityLevelFile) {
            $parsedYamlFile = Yaml::parseFile($maturityLevelsFolderPath.'/'.$maturityLevelFile);
            $entityStatus = $this->syncMaturityLevel($parsedYamlFile['id'], $parsedYamlFile['number'], $parsedYamlFile['description']);
            if ($entityStatus === ModelEntitySyncEnum::ADDED) {
                ++$added;
            } elseif ($entityStatus === ModelEntitySyncEnum::MODIFIED) {
                ++$modified;
            }
        }

        $this->entityManager->flush();

        return [$added, $modified];
    }

    /**
     * @return int[]
     */
    public function syncPracticeLevels(): array
    {
        $practiceLevelsFolderPath = "{$this->getModelsFolder()}/practice_levels";
        $practiceLevelFiles = $this->removeDotDirectories(scandir($practiceLevelsFolderPath));
        $added = $modified = 0;
        foreach ($practiceLevelFiles as $practiceLevel) {
            $parsedYamlFile = Yaml::parseFile($practiceLevelsFolderPath.'/'.$practiceLevel);
            $entityStatus = $this->syncPracticeLevel($parsedYamlFile['id'], $parsedYamlFile['practice'], $parsedYamlFile['maturitylevel'], $parsedYamlFile['objective']);
            if ($entityStatus === ModelEntitySyncEnum::ADDED) {
                ++$added;
            } elseif ($entityStatus === ModelEntitySyncEnum::MODIFIED) {
                ++$modified;
            }
        }

        $this->entityManager->flush();

        return [$added, $modified];
    }

    /**
     * @return int[]
     */
    public function syncStreams(): array
    {
        $streamsFolderPath = "{$this->getModelsFolder()}/streams";
        $streamFiles = $this->removeDotDirectories(scandir($streamsFolderPath));
        $added = $modified = 0;
        foreach ($streamFiles as $streamFile) {
            $parsedYamlFile = Yaml::parseFile($streamsFolderPath.'/'.$streamFile);

            $entityStatus = $this->syncStream($parsedYamlFile['id'], $parsedYamlFile['practice'], $parsedYamlFile['name'], $parsedYamlFile['description'], $parsedYamlFile['order']);
            if ($entityStatus === ModelEntitySyncEnum::ADDED) {
                ++$added;
            } elseif ($entityStatus === ModelEntitySyncEnum::MODIFIED) {
                ++$modified;
            }
        }

        $this->entityManager->flush();

        return [$added, $modified];
    }

    /**
     * @return int[]
     */
    public function syncActivities(): array
    {
        $activitiesFolderPath = "{$this->getModelsFolder()}/activities";
        $activityFiles = $this->removeDotDirectories(scandir($activitiesFolderPath));
        $added = $modified = 0;
        foreach ($activityFiles as $activityFile) {
            $parsedYamlFile = Yaml::parseFile($activitiesFolderPath.'/'.$activityFile);

            $entityStatus = $this->syncActivity(
                $parsedYamlFile['id'],
                $parsedYamlFile['stream'],
                $parsedYamlFile['title'],
                $parsedYamlFile['benefit'],
                $parsedYamlFile['shortDescription'],
                $parsedYamlFile['longDescription'],
                $parsedYamlFile['level'],
                $parsedYamlFile['notes']
            );

            if ($entityStatus === ModelEntitySyncEnum::ADDED) {
                ++$added;
            } elseif ($entityStatus === ModelEntitySyncEnum::MODIFIED) {
                ++$modified;
            }
        }

        $this->entityManager->flush();

        return [$added, $modified];
    }

    /**
     * @return int[]
     */
    public function syncQuestions(): array
    {
        $questionsFolderPath = "{$this->getModelsFolder()}/questions";
        $questionFiles = $this->removeDotDirectories(scandir($questionsFolderPath));
        $added = $modified = 0;
        foreach ($questionFiles as $questionFile) {
            $parsedYamlFile = Yaml::parseFile($questionsFolderPath.'/'.$questionFile);

            $entityStatus = $this->syncQuestion(
                $parsedYamlFile['id'],
                $parsedYamlFile['activity'],
                $parsedYamlFile['text'],
                $parsedYamlFile['quality'],
                $parsedYamlFile['answerset'],
                $parsedYamlFile['order']
            );

            if ($entityStatus === ModelEntitySyncEnum::ADDED) {
                ++$added;
            } elseif ($entityStatus === ModelEntitySyncEnum::MODIFIED) {
                ++$modified;
            }
        }

        $this->entityManager->flush();

        return [$added, $modified];
    }

    /**
     * @return int[]
     */
    public function syncAnswerSets(): array
    {
        $answersFolderPath = "{$this->getModelsFolder()}/answer_sets";
        $answerFiles = $this->removeDotDirectories(scandir($answersFolderPath));
        $added = $modified = 0;
        foreach ($answerFiles as $answerFile) {
            $parsedYamlFile = Yaml::parseFile($answersFolderPath.'/'.$answerFile);
            $externalId = $parsedYamlFile['id'];

            $answerSetStatus = $this->syncAnswerSet($externalId);

            if ($answerSetStatus === ModelEntitySyncEnum::ADDED) {
                ++$added;
                $this->entityManager->flush();
            }

            $answerSetEntity = $this->answerSetRepository->findOneBy(['externalId' => $externalId]);

            foreach ($parsedYamlFile['values'] as $answerYamlFileValues) {
                $entityStatus = $this->syncAnswer($answerSetEntity, $answerYamlFileValues['order'], $answerYamlFileValues['text'], $answerYamlFileValues['value'], $answerYamlFileValues['weight']);
                if ($entityStatus === ModelEntitySyncEnum::MODIFIED) {
                    ++$modified;
                }
            }
        }

        $this->entityManager->flush();

        return [$added, $modified];
    }

    private function getModelsFolder(): string
    {
        return "{$this->parameterBag->get('kernel.project_dir')}/private/core/model";
    }

    private function removeDotDirectories(array $directories): array
    {
        return array_filter($directories, fn ($dir) => $dir !== '.' && $dir !== '..');
    }
}
