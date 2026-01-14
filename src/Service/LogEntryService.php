<?php

namespace App\Service;

use App\Entity\Shared\logEntry;
use App\Repository\Shared\logEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class LogEntryService
{
    private $security;
    private $logEntryRepository;
    private $entityManager;

    public function __construct(Security $security, logEntryRepository $logEntryRepository, EntityManagerInterface $entityManager)
    {
        $this->logEntryRepository = $logEntryRepository;
        $this->security = $security;
        $this->entityManager = $entityManager;

    }

    public function createLogEntry(string $message): void
    {
        $user= $this->security->getUser();
        $logEntry = new logEntry();
        $logEntry->setMessage($message);
        $logEntry->setUser($user);
        $this->entityManager->persist($logEntry);
        $this->entityManager->flush();
    }

    public function getLogEntries(): array
    {
        $logEntries = $this->logEntryRepository->findAll();
        $data = [];
        foreach ($logEntries as $logEntry) {
            $data[] = [
                'id' => $logEntry->getId(),
                'message' => $logEntry->getMessage(),
                'user' => $logEntry->getUser()->getId(),
                'createdAt' => $logEntry->getCreatedAt()->format('Y-m-d H:i:s'),
            ]; 
        }
    return $data;
    }
    
  

}