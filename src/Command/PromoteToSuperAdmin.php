<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'app:promote-to-super-admin',
    description: 'Promotes an existing admin user to super admin role'
)]
class PromoteToSuperAdmin extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the admin user to promote');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        // Check if a super admin already exists
        $existingSuperAdmin = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_SUPER_ADMIN%')
            ->getQuery()
            ->getResult();

        if (!empty($existingSuperAdmin)) {
            $io->error('A super admin already exists. Only one super admin account is allowed.');
            return Command::FAILURE;
        }

        // Find the user
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('User with email %s not found.', $email));
            return Command::FAILURE;
        }

        // Check if user is an admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            $io->error('This user is not an admin. Only admin users can be promoted to super admin.');
            return Command::FAILURE;
        }

        // Promote to super admin
        $user->setRoles(['ROLE_SUPER_ADMIN']);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $io->success([
                'User promoted to super admin successfully!',
                sprintf('Email: %s', $email)
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Could not promote user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}