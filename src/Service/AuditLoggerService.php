<?php
// src/Service/AuditLoggerService.php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AuditLoggerService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    public function log(
        string $action,
        ?User $user = null,
        ?string $resource = null,
        ?array $details = null,
        ?string $ipAddress = null
    ): void {
        $auditLog = new AuditLog();
        $auditLog->setAction($action);
        $auditLog->setUser($user);
        $auditLog->setResource($resource);
        $auditLog->setDetails($details ?? []);
        $auditLog->setIpAddress($ipAddress);
        $auditLog->setTimestamp(new \DateTime());

        $this->em->persist($auditLog);
        $this->em->flush();

        // También log a archivo
        $this->logger->info('AUDIT_LOG', [
            'action' => $action,
            'user' => $user ? $user->getEmail() : 'anonymous',
            'resource' => $resource,
            'details' => $details
        ]);
    }

    public function logUserAction(User $user, string $action, ?string $resource = null, ?array $details = null): void
    {
        $this->log($action, $user, $resource, $details);
    }

    public function logSecurityEvent(string $action, ?User $user = null, ?array $details = null): void
    {
        $this->log($action, $user, 'security', $details);
    }
}
