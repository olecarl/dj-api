<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepositoryInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @psalm-api The command is discovered by Symfony's AsCommand attribute. */
#[AsCommand(
    name: 'app:user:create',
    description: 'Creates a user account.',
)]
final class CreateUserCommand extends Command
{
    /**
     * @param UserRepositoryInterface<User> $userRepository
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument(
                'email',
                InputArgument::OPTIONAL,
                'The email address of the user. If omitted, it is requested interactively.',
            )
            ->addOption(
                'role',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'A role to assign to the user. May be specified more than once.',
            )
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->isInteractive()) {
            $io->error('This command must be run interactively so the password is not exposed in shell history.');

            return self::INVALID;
        }

        try {
            $email = $this->getEmail($input, $io);
            if (null !== $this->userRepository->findOneBy(['email' => $email])) {
                throw new InvalidArgumentException(\sprintf('A user with email "%s" already exists.', $email));
            }

            $password = $this->askPassword($io);
            $roles = $this->getRoles($input);

            $user = (new User())
                ->setEmail($email)
                ->setRoles($roles)
            ;
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));

            $this->entityManager->wrapInTransaction(function () use ($user): void {
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            });
        } catch (InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return self::INVALID;
        } catch (UniqueConstraintViolationException) {
            $io->error('A user with that email already exists.');

            return self::FAILURE;
        }

        $io->success(\sprintf('User "%s" was created.', $email));

        return self::SUCCESS;
    }

    /** @psalm-suppress TypeDoesNotContainType */
    private function getEmail(InputInterface $input, SymfonyStyle $io): string
    {
        $email = $input->getArgument('email');
        if (null !== $email) {
            if (!\is_string($email)) {
                throw new InvalidArgumentException('The email address must be a string.');
            }

            return $this->validateEmail($email);
        }

        $question = new Question('Email: ');
        $question->setValidator(fn (mixed $answer): string => $answer instanceof \Stringable || \is_string($answer)
            ? $this->validateEmail((string) $answer)
            : throw new InvalidArgumentException('The email address must be a string.')
        );

        return $this->askString($io, $question, 'The email address must be a string.');
    }

    private function askPassword(SymfonyStyle $io): string
    {
        $question = new Question('Password: ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $question->setValidator(static function (mixed $answer): string {
            if (!\is_string($answer) || mb_strlen($answer) < 12) {
                throw new InvalidArgumentException('The password must be at least 12 characters long.');
            }

            return $answer;
        });
        $password = $this->askString($io, $question, 'A password is required.');
        $confirmation = new Question('Repeat password: ');
        $confirmation->setHidden(true);
        $confirmation->setValidator(static function (mixed $answer) use ($password): string {
            if (!\is_string($answer) || $answer !== $password) {
                throw new InvalidArgumentException('The passwords do not match.');
            }

            return $answer;
        });

        $io->askQuestion($confirmation);

        return $password;
    }

    private function askString(SymfonyStyle $io, Question $question, string $errorMessage): string
    {
        $answer = $io->askQuestion($question);
        if (!\is_string($answer)) {
            throw new InvalidArgumentException($errorMessage);
        }

        return $answer;
    }

    private function validateEmail(string $email): string
    {
        $email = mb_strtolower(mb_trim($email));
        if ('' === $email) {
            throw new InvalidArgumentException('The email address cannot be empty.');
        }

        $violations = $this->validator->validate($email, [
            new Email(),
            new Length(max: 180),
        ]);
        if ($violations->count() > 0) {
            throw new InvalidArgumentException((string) $violations->get(0)->getMessage());
        }

        return $email;
    }

    /**
     * @return list<string>
     */
    private function getRoles(InputInterface $input): array
    {
        /** @var list<string> $providedRoles */
        $providedRoles = $input->getOption('role');
        $roles = [];

        foreach ($providedRoles as $role) {
            if (1 !== preg_match('/^ROLE_[A-Z][A-Z0-9_]*$/', $role)) {
                throw new InvalidArgumentException(\sprintf('Invalid role "%s".', $role));
            }

            $roles[] = $role;
        }

        return array_values(array_unique($roles));
    }
}
