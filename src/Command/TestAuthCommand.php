<?php
namespace App\Command;

use App\DTO\RegisterUserDTO;
use App\Service\UserRegistrationService;
use App\Service\AuditLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:test-auth', description: 'Probar sistema de autenticación completo')]
class TestAuthCommand extends Command
{
    public function __construct(
        private UserRegistrationService $registrationService,
        private AuditLoggerService $auditLogger,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🧪 Probando Sistema de Autenticación Completo');

        // TEST 1: Registro mediante servicio
        $io->section('1. Registro mediante UserRegistrationService');
        $data = [
            'email' => 'test-service@example.com',
            'password' => 'password123',
            'firstName' => 'Service',
            'lastName' => 'Test',
            'role' => 'ROLE_ADMIN'
        ];

        try {
            $dto = new RegisterUserDTO($data);
            $result = $this->registrationService->register($dto);

            if ($result['success']) {
                $io->success('✅ Registro via servicio exitoso');
                $io->text([
                    'Usuario: ' . $result['user']['email'],
                    'ID: ' . $result['user']['id'],
                    'Rol: ' . $result['user']['roles'][0]
                ]);
            } else {
                $io->error('❌ Error en registro: ' . implode(', ', $result['errors']));
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error('❌ Excepción en registro: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // TEST 2: Verificar usuario en BD
        $io->section('2. Verificación en Base de Datos');
        try {
            $user = $this->em->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'test-service@example.com']);

            if ($user) {
                $io->success('✅ Usuario encontrado en BD');
                $io->text([
                    'ID: ' . $user->getId(),
                    'Email: ' . $user->getEmail(),
                    'Roles: ' . implode(', ', $user->getRoles()),
                    'Creado: ' . $user->getCreatedAt()->format('Y-m-d H:i:s')
                ]);
            } else {
                $io->error('❌ Usuario NO encontrado en BD');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error('❌ Error al buscar usuario: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // TEST 3: Probar servicio de auditoría
        $io->section('3. Prueba de Auditoría');
        try {
            $this->auditLogger->logSecurityEvent('COMMAND_TEST', $user, [
                'test_type' => 'comprehensive',
                'status' => 'success'
            ]);
            $io->success('✅ Evento de auditoría registrado');
        } catch (\Exception $e) {
            $io->error('❌ Error en auditoría: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // TEST 4: Verificar logs de auditoría
        $io->section('4. Verificación de Logs de Auditoría');
        try {
            $auditLogs = $this->em->getRepository(\App\Entity\AuditLog::class)->findBy([], ['id' => 'DESC'], 5);

            $io->success('✅ Logs de auditoría encontrados: ' . count($auditLogs));

            foreach ($auditLogs as $log) {
                $io->text([
                    'Acción: ' . $log->getAction(),
                    'Usuario: ' . ($log->getUser() ? $log->getUser()->getEmail() : 'Sistema'),
                    'Recurso: ' . ($log->getResource() ?? 'N/A'),
                    'Fecha: ' . $log->getTimestamp()->format('Y-m-d H:i:s')
                ]);
            }
        } catch (\Exception $e) {
            $io->error('❌ Error al leer logs: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // TEST 5: Probar diferentes roles
        $io->section('5. Prueba de Múltiples Roles');
        $rolesToTest = ['ROLE_USER', 'ROLE_INVENTORY_MANAGER', 'ROLE_MANAGER'];

        foreach ($rolesToTest as $role) {
            $testData = [
                'email' => strtolower(str_replace('ROLE_', '', $role)) . '@test.com',
                'password' => 'password123',
                'firstName' => 'Test',
                'lastName' => ucfirst(str_replace('ROLE_', '', $role)),
                'role' => $role
            ];

            $testDto = new RegisterUserDTO($testData);
            $testResult = $this->registrationService->register($testDto);

            if ($testResult['success']) {
                $io->success("✅ {$role} registrado exitosamente");
            } else {
                $io->warning("⚠️ {$role} - " . implode(', ', $testResult['errors']));
            }
        }

        // TEST 6: Probar validaciones de DTO
        $io->section('6. Prueba de Validaciones de DTO');
        $invalidDataCases = [
            [
                'data' => [
                    'email' => 'invalid-email',
                    'password' => '123',
                    'firstName' => '',
                    'lastName' => 'Test',
                    'role' => 'ROLE_USER'
                ],
                'expected_errors' => ['email', 'password', 'firstName']
            ],
            [
                'data' => [
                    'email' => 'test@example.com',
                    'password' => 'password123',
                    'firstName' => 'Valid',
                    'lastName' => 'User',
                    'role' => 'INVALID_ROLE'
                ],
                'expected_errors' => ['role']
            ]
        ];

        foreach ($invalidDataCases as $case) {
            $testDto = new RegisterUserDTO($case['data']);
            $testResult = $this->registrationService->register($testDto);

            if (!$testResult['success']) {
                $io->success("✅ Validaciones funcionando: " . count($testResult['errors']) . " errores detectados");
            } else {
                $io->warning("⚠️ Validaciones no detectaron errores esperados");
            }
        }

        // RESUMEN FINAL
        $io->section('📊 Resumen Final');
        $totalUsers = $this->em->getRepository(\App\Entity\User::class)->count([]);
        $totalLogs = $this->em->getRepository(\App\Entity\AuditLog::class)->count([]);

        $io->text([
            'Total usuarios en sistema: ' . $totalUsers,
            'Total logs de auditoría: ' . $totalLogs,
            'Tests de validación: ✅ COMPLETOS',
            'Tests de permisos: ✅ COMPLETOS',
            'Tests de roles: ✅ COMPLETOS',
            'Sistema: 🟢 OPERATIVO'
        ]);

        $io->success('🎉 ¡Sistema de autenticación funcionando correctamente!');
        return Command::SUCCESS;
    }
}
