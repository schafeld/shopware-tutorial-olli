<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'learning:recommendations:generate',
    description: 'Generate product recommendations from existing session tracking data'
)]
class GenerateRecommendationsCommand extends Command
{
    private const DEFAULT_MIN_VIEWS = 2;
    private const DEFAULT_SESSION_WINDOW_MINUTES = 30;

    public function __construct(
        private readonly Connection $connection,
        private readonly EntityRepository $recommendationRepository,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'min-views',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Minimum number of co-views to create recommendation',
                self::DEFAULT_MIN_VIEWS
            )
            ->addOption(
                'window',
                'w',
                InputOption::VALUE_OPTIONAL,
                'Session time window in minutes',
                self::DEFAULT_SESSION_WINDOW_MINUTES
            )
            ->addOption(
                'clear',
                'c',
                InputOption::VALUE_NONE,
                'Clear existing recommendations before generating'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        $minViews = (int) $input->getOption('min-views');
        $windowMinutes = (int) $input->getOption('window');
        $clear = $input->getOption('clear');

        $io->title('Generate Product Recommendations');

        // Step 1: Clear existing recommendations if requested
        if ($clear) {
            $io->section('Clearing existing recommendations...');
            $deleted = $this->connection->executeStatement('DELETE FROM learning_product_recommendation');
            $io->success(sprintf('Deleted %d existing recommendations', $deleted));
        }

        // Step 2: Analyze session data
        $io->section('Analyzing session data...');
        
        $sql = "
            SELECT 
                s1.product_id as product_a,
                s1.product_version_id as product_a_version,
                s2.product_id as product_b,
                s2.product_version_id as product_b_version,
                COUNT(*) as co_views,
                MAX(s1.viewed_at) as last_viewed
            FROM learning_product_session s1
            INNER JOIN learning_product_session s2 
                ON s1.session_id = s2.session_id 
                AND s1.product_id != s2.product_id
                AND TIMESTAMPDIFF(MINUTE, s1.viewed_at, s2.viewed_at) BETWEEN -:window AND :window
            GROUP BY s1.product_id, s1.product_version_id, s2.product_id, s2.product_version_id
            HAVING COUNT(*) >= :minViews
            ORDER BY co_views DESC
        ";

        $coViews = $this->connection->fetchAllAssociative($sql, [
            'window' => $windowMinutes,
            'minViews' => $minViews,
        ]);

        $io->text(sprintf('Found %d product pairs with %d+ co-views', count($coViews), $minViews));

        if (empty($coViews)) {
            $io->warning('No product pairs found. Users need to view multiple products in the same session.');
            return Command::SUCCESS;
        }

        // Step 3: Generate recommendations
        $io->section('Generating recommendations...');
        $progressBar = $io->createProgressBar(count($coViews));
        $progressBar->start();

        $recommendations = [];
        $maxCoViews = max(array_column($coViews, 'co_views'));

        foreach ($coViews as $pair) {
            // Calculate affinity score (0.0 to 1.0)
            $affinityScore = min(($pair['co_views'] / $maxCoViews) * 0.9 + 0.1, 1.0);

            $recommendations[] = [
                'id' => Uuid::randomHex(),
                'sourceProductId' => bin2hex($pair['product_a']),
                'recommendedProductId' => bin2hex($pair['product_b']),
                'affinityScore' => round($affinityScore, 2),
                'viewCount' => (int) $pair['co_views'],
                'lastUpdated' => new \DateTimeImmutable(),
                'createdAt' => new \DateTimeImmutable(),
            ];

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Step 4: Batch insert recommendations
        if (!empty($recommendations)) {
            $io->section('Saving recommendations...');
            
            try {
                $this->recommendationRepository->create($recommendations, $context);
                $io->success(sprintf('Successfully created %d recommendations', count($recommendations)));
            } catch (\Exception $e) {
                $io->error('Failed to save recommendations: ' . $e->getMessage());
                $this->logger->error('Failed to generate recommendations', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return Command::FAILURE;
            }
        }

        // Step 5: Show statistics
        $io->section('Statistics');
        $stats = $this->connection->fetchAssociative("
            SELECT 
                COUNT(DISTINCT source_product_id) as products_with_recommendations,
                COUNT(*) as total_recommendations,
                AVG(affinity_score) as avg_affinity,
                MAX(view_count) as max_co_views
            FROM learning_product_recommendation
        ");

        $io->table(
            ['Metric', 'Value'],
            [
                ['Products with recommendations', $stats['products_with_recommendations']],
                ['Total recommendations', $stats['total_recommendations']],
                ['Average affinity score', round((float) $stats['avg_affinity'], 2)],
                ['Max co-views', $stats['max_co_views']],
            ]
        );

        $io->success('Recommendation generation completed!');

        return Command::SUCCESS;
    }
}
