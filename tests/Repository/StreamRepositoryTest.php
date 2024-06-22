<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Stream;
use App\Repository\StreamRepository;
use App\Tests\_support\AbstractKernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class StreamRepositoryTest extends AbstractKernelTestCase
{
    //Expects that DB includes DRP model entities
    public function testFindAll()
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        /** @var StreamRepository $streamRepository */
        $streamRepository = $entityManager->getRepository(Stream::class);

        $entityManager->clear();
        //Regular mode gets only SAMM streams
        $streams = $streamRepository->findAll();
        self::assertEquals(30, sizeof($streams));

        //Adds new stream
        $newStream = new Stream();
        $entityManager->persist($newStream);
        $entityManager->flush();

        //Regular mode gets only SAMM streams
        $streams = $streamRepository->findAll();
        self::assertEquals(30, sizeof($streams));

        //Expert mode gets all streams, including the new one
        $streams = $streamRepository->findAll(true);
        self::assertGreaterThanOrEqual(31, sizeof($streams));
    }

}