<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

#[AsCommand(
    name: 'app:create-super-admin',
    description: 'Creates a super admin user'
)]
class CreateSuperAdmin extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

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

        // Email validation
        do {
            $email = $io->ask('Email');
            $emailErrors = $this->validator->validate($email, [
                new NotBlank(['message' => 'Email cannot be blank']),
                new Email(['message' => 'Invalid email address'])
            ]);

            if ($emailErrors->count() > 0) {
                $io->error((string) $emailErrors->get(0)->getMessage());
            }

            // Check if email already exists
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $io->error('This email is already in use');
                $emailErrors = true;
            }
        } while ($emailErrors->count() > 0);

        // First Name validation
        do {
            $firstName = $io->ask('First Name');
            $firstNameErrors = $this->validator->validate($firstName, [
                new NotBlank(['message' => 'First name cannot be blank']),
                new Length([
                    'min' => 2,
                    'max' => 50,
                    'minMessage' => 'First name must be at least {{ limit }} characters long',
                    'maxMessage' => 'First name cannot be longer than {{ limit }} characters'
                ])
            ]);

            if ($firstNameErrors->count() > 0) {
                $io->error((string) $firstNameErrors->get(0)->getMessage());
            }
        } while ($firstNameErrors->count() > 0);

        // Last Name validation
        do {
            $lastName = $io->ask('Last Name');
            $lastNameErrors = $this->validator->validate($lastName, [
                new NotBlank(['message' => 'Last name cannot be blank']),
                new Length([
                    'min' => 2,
                    'max' => 50,
                    'minMessage' => 'Last name must be at least {{ limit }} characters long',
                    'maxMessage' => 'Last name cannot be longer than {{ limit }} characters'
                ])
            ]);

            if ($lastNameErrors->count() > 0) {
                $io->error((string) $lastNameErrors->get(0)->getMessage());
            }
        } while ($lastNameErrors->count() > 0);

        // Password validation
        do {
            $password = $io->askHidden('Password');
            $passwordErrors = $this->validator->validate($password, [
                new NotBlank(['message' => 'Password cannot be blank']),
                new Length([
                    'min' => 6,
                    'max' => 4096,
                    'minMessage' => 'Password must be at least {{ limit }} characters long',
                    'maxMessage' => 'Password cannot be longer than {{ limit }} characters'
                ])
            ]);

            if ($passwordErrors->count() > 0) {
                $io->error((string) $passwordErrors->get(0)->getMessage());
            } else {
                // Confirm password
                $confirmPassword = $io->askHidden('Confirm Password');
                if ($password !== $confirmPassword) {
                    $io->error('Passwords do not match');
                    $passwordErrors = true;
                }
            }
        } while ($passwordErrors->count() > 0);

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $user->setVerified(true);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $io->success([
                'Super admin user created successfully!',
                sprintf('Email: %s', $email)
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Could not create super admin user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}