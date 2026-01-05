<?php declare(strict_types=1);

namespace LearningBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'learning:show-access-keys',
    description: 'Show sales channel access keys'
)]
class ShowAccessKeyCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Sales Channel Access Keys');
        
        $channels = $this->connection->fetchAllAssociative('
            SELECT 
                COALESCE(sct.name, sc.short_name, LOWER(HEX(sc.id))) as name,
                sc.access_key,
                sc.active
            FROM sales_channel sc
            LEFT JOIN sales_channel_translation sct ON sc.id = sct.sales_channel_id
            GROUP BY sc.id, sc.access_key, sc.active
        ');
        
        if (empty($channels)) {
            $io->warning('No sales channels found in database');
            return Command::FAILURE;
        }
        
        $rows = array_map(fn($ch) => [
            $ch['name'],
            $ch['access_key'],
            $ch['active'] ? '✓' : '✗'
        ], $channels);
        
        $io->table(['Name', 'Access Key', 'Active'], $rows);
        
        $io->success('Found ' . count($channels) . ' sales channel(s)');
        
        // Also print first access key for easy copying
        $io->note('To use in API calls: export SW_ACCESS_KEY="' . $channels[0]['access_key'] . '"');
        
        return Command::SUCCESS;
    }
}
