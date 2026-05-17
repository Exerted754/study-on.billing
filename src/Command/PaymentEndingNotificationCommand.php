<?php

namespace App\Command;

use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'payment:ending:notification')]
class PaymentEndingNotificationCommand extends Command
{
    public function __construct(
        private TransactionRepository $transactionRepository,
        private MailerInterface $mailer,
        #[Autowire('%mailer_from%')]
        private string $mailerFrom
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $transactions = $this->transactionRepository->findRentPaymentsEndingTomorrow();

        if ($transactions === []) {
            $output->writeln('Нет аренд, которые заканчиваются завтра.');

            return Command::SUCCESS;
        }

        $transactionsByEmail = [];

        foreach ($transactions as $transaction) {
            /** @var Transaction $transaction */
            $user = $transaction->getUser();

            if ($user === null) {
                continue;
            }

            $email = $user->getEmail();

            if ($email === null) {
                continue;
            }

            $transactionsByEmail[$email][] = $transaction;
        }

        $sentCount = 0;

        foreach ($transactionsByEmail as $email => $userTransactions) {
            $message = $this->buildMessage($userTransactions);

            $mail = (new Email())
                ->from($this->mailerFrom)
                ->to($email)
                ->subject('Срок аренды курса подходит к концу')
                ->text($message);

            $this->mailer->send($mail);

            $sentCount++;
        }

        $output->writeln(sprintf('Отправлено писем: %d', $sentCount));

        return Command::SUCCESS;
    }

    /**
     * @param Transaction[] $transactions
     */
    private function buildMessage(array $transactions): string
    {
        $lines = [
            'Уважаемый клиент! У вас есть курсы, срок аренды которых подходит к концу:',
        ];

        foreach ($transactions as $transaction) {
            $course = $transaction->getCourse();

            if ($course === null || $transaction->getExpiresAt() === null) {
                continue;
            }

            $lines[] = sprintf(
                '%s действует до %s.',
                $course->getTitle(),
                $transaction->getExpiresAt()->format('d.m.Y H:i')
            );
        }

        return implode(PHP_EOL, $lines);
    }
}
