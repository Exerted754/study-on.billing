<?php

namespace App\Command;

use App\Repository\TransactionRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'payment:report')]
class PaymentReportCommand extends Command
{
    public function __construct(
        private TransactionRepository $transactionRepository,
        private MailerInterface $mailer,
        #[Autowire('%mailer_from%')]
        private string $mailerFrom,
        #[Autowire('%report_email%')]
        private string $reportEmail
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $to = new \DateTimeImmutable();
        $from = $to->modify('-1 month');

        $rows = $this->transactionRepository->getPaymentsReport($from, $to);

        $total = 0.0;

        foreach ($rows as $row) {
            $total += (float) $row['totalAmount'];
        }

        $message = $this->buildMessage($from, $to, $rows, $total);

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($this->reportEmail)
            ->subject('Отчет об оплаченных курсах')
            ->text($message);

        $this->mailer->send($email);

        $output->writeln('Отчет отправлен.');

        return Command::SUCCESS;
    }

    private function buildMessage(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        array $rows,
        float $total
    ): string {
        $lines = [
            sprintf(
                'Отчет об оплаченных курсах за период %s - %s',
                $from->format('d.m.Y'),
                $to->format('d.m.Y')
            ),
            '',
            'Название курса | Тип курса | Число аренд/покупок | Общая сумма',
        ];

        if ($rows === []) {
            $lines[] = 'Оплат за период нет.';
        }

        foreach ($rows as $row) {
            $lines[] = sprintf(
                '%s | %s | %d | %.2f',
                $row['title'],
                $this->formatCourseType($row['type']),
                (int) $row['paymentsCount'],
                (float) $row['totalAmount']
            );
        }

        $lines[] = '';
        $lines[] = sprintf('Итого: %.2f', $total);

        return implode(PHP_EOL, $lines);
    }

    private function formatCourseType(string $type): string
    {
        return match ($type) {
            'buy' => 'Покупка',
            'rent' => 'Аренда',
            'free' => 'Бесплатный',
            default => $type,
        };
    }
}
