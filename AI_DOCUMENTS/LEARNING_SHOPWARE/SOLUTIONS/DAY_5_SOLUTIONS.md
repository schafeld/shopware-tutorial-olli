# Day 5 Solutions: Debugging and Error Analysis

Complete solutions for all exercises in Day 5.

## Exercise 1: Performance Profiler

### Performance Profile Service

**File:** `custom/plugins/LearningBundle/src/Service/PerformanceProfilerService.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;

class PerformanceProfilerService
{
    private LoggerInterface $logger;
    private array $profiles = [];
    private float $slowThreshold = 1.0; // 1 second

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setSlowThreshold(float $seconds): void
    {
        $this->slowThreshold = $seconds;
    }

    public function start(string $operationName): string
    {
        $profileId = uniqid($operationName . '_', true);
        
        $this->profiles[$profileId] = [
            'name' => $operationName,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ];

        return $profileId;
    }

    public function stop(string $profileId): array
    {
        if (!isset($this->profiles[$profileId])) {
            throw new \InvalidArgumentException("Profile ID '{$profileId}' not found");
        }

        $profile = $this->profiles[$profileId];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $duration = $endTime - $profile['start_time'];
        $memoryUsed = $endMemory - $profile['start_memory'];

        $result = [
            'name' => $profile['name'],
            'duration' => $duration,
            'duration_ms' => round($duration * 1000, 2),
            'memory_used' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
            'is_slow' => $duration > $this->slowThreshold,
        ];

        // Log slow operations
        if ($result['is_slow']) {
            $this->logger->warning('Slow operation detected', [
                'operation' => $result['name'],
                'duration_ms' => $result['duration_ms'],
                'memory_mb' => $result['memory_used_mb'],
                'threshold_ms' => $this->slowThreshold * 1000,
                'backtrace' => $this->formatBacktrace($profile['backtrace']),
            ]);
        } else {
            $this->logger->debug('Operation completed', [
                'operation' => $result['name'],
                'duration_ms' => $result['duration_ms'],
                'memory_mb' => $result['memory_used_mb'],
            ]);
        }

        unset($this->profiles[$profileId]);

        return $result;
    }

    public function profile(callable $callback, string $operationName)
    {
        $profileId = $this->start($operationName);
        
        try {
            $result = $callback();
            $this->stop($profileId);
            return $result;
        } catch (\Throwable $e) {
            $this->stop($profileId);
            throw $e;
        }
    }

    public function getActiveProfiles(): array
    {
        $active = [];
        $currentTime = microtime(true);

        foreach ($this->profiles as $id => $profile) {
            $active[] = [
                'id' => $id,
                'name' => $profile['name'],
                'running_time' => round(($currentTime - $profile['start_time']) * 1000, 2),
            ];
        }

        return $active;
    }

    private function formatBacktrace(array $backtrace): array
    {
        return array_map(function ($trace) {
            return sprintf(
                '%s%s%s() in %s:%d',
                $trace['class'] ?? '',
                $trace['type'] ?? '',
                $trace['function'] ?? '',
                $trace['file'] ?? 'unknown',
                $trace['line'] ?? 0
            );
        }, $backtrace);
    }
}
```

### Profiler Decorator Pattern

**File:** `custom/plugins/LearningBundle/src/Service/ProfiledProductViewService.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Shopware\Core\Framework\Context;

class ProfiledProductViewService
{
    private ProductViewService $decorated;
    private PerformanceProfilerService $profiler;

    public function __construct(
        ProductViewService $decorated,
        PerformanceProfilerService $profiler
    ) {
        $this->decorated = $decorated;
        $this->profiler = $profiler;
    }

    public function trackProductView(string $productId, ?string $customerId, Context $context): void
    {
        $this->profiler->profile(
            fn() => $this->decorated->trackProductView($productId, $customerId, $context),
            'trackProductView'
        );
    }

    public function getProductViewCount(string $productId, Context $context): int
    {
        return $this->profiler->profile(
            fn() => $this->decorated->getProductViewCount($productId, $context),
            'getProductViewCount'
        );
    }

    public function getMostViewedProducts(int $limit, Context $context): array
    {
        return $this->profiler->profile(
            fn() => $this->decorated->getMostViewedProducts($limit, $context),
            'getMostViewedProducts'
        );
    }
}
```

### Profiler Command

**File:** `custom/plugins/LearningBundle/src/Command/ProfileOperationsCommand.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\PerformanceProfilerService;
use Learning\Bundle\Service\ProductViewService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProfileOperationsCommand extends Command
{
    protected static $defaultName = 'learning:profile-operations';

    private PerformanceProfilerService $profiler;
    private ProductViewService $productViewService;

    public function __construct(
        PerformanceProfilerService $profiler,
        ProductViewService $productViewService
    ) {
        parent::__construct();
        $this->profiler = $profiler;
        $this->productViewService = $productViewService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Profile plugin operations')
            ->addOption('iterations', 'i', InputOption::VALUE_OPTIONAL, 'Number of iterations', 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        $iterations = (int) $input->getOption('iterations');

        $io->title('Performance Profiling');

        $results = [];

        // Profile getMostViewedProducts
        $io->section('Profiling getMostViewedProducts');
        for ($i = 0; $i < $iterations; $i++) {
            $profileId = $this->profiler->start('getMostViewedProducts');
            $this->productViewService->getMostViewedProducts(10, $context);
            $results[] = $this->profiler->stop($profileId);
        }

        $avgDuration = array_sum(array_column($results, 'duration_ms')) / count($results);
        $avgMemory = array_sum(array_column($results, 'memory_used_mb')) / count($results);

        $io->table(
            ['Metric', 'Value'],
            [
                ['Iterations', $iterations],
                ['Avg Duration (ms)', number_format($avgDuration, 2)],
                ['Min Duration (ms)', number_format(min(array_column($results, 'duration_ms')), 2)],
                ['Max Duration (ms)', number_format(max(array_column($results, 'duration_ms')), 2)],
                ['Avg Memory (MB)', number_format($avgMemory, 2)],
            ]
        );

        // Profile getProductViewCount
        $io->section('Profiling getProductViewCount');
        $results = [];
        $testProductId = 'test-product-id';

        for ($i = 0; $i < $iterations; $i++) {
            $profileId = $this->profiler->start('getProductViewCount');
            $this->productViewService->getProductViewCount($testProductId, $context);
            $results[] = $this->profiler->stop($profileId);
        }

        $avgDuration = array_sum(array_column($results, 'duration_ms')) / count($results);

        $io->table(
            ['Metric', 'Value'],
            [
                ['Iterations', $iterations],
                ['Avg Duration (ms)', number_format($avgDuration, 2)],
            ]
        );

        $io->success('Profiling completed');

        return Command::SUCCESS;
    }
}
```

### Services Configuration

Add to `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<service id="Learning\Bundle\Service\PerformanceProfilerService">
    <argument type="service" id="monolog.logger.learning"/>
</service>

<service id="Learning\Bundle\Service\ProfiledProductViewService" decorates="Learning\Bundle\Service\ProductViewService">
    <argument type="service" id="Learning\Bundle\Service\ProfiledProductViewService.inner"/>
    <argument type="service" id="Learning\Bundle\Service\PerformanceProfilerService"/>
</service>

<service id="Learning\Bundle\Command\ProfileOperationsCommand">
    <argument type="service" id="Learning\Bundle\Service\PerformanceProfilerService"/>
    <argument type="service" id="Learning\Bundle\Service\ProductViewService"/>
    <tag name="console.command"/>
</service>
```

---

## Exercise 2: Error Report Command

### Error Analyzer Service

**File:** `custom/plugins/LearningBundle/src/Service/ErrorAnalyzerService.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

class ErrorAnalyzerService
{
    private const ERROR_PATTERNS = [
        'fatal' => '/PHP Fatal error:/i',
        'error' => '/PHP Error:/i',
        'warning' => '/PHP Warning:/i',
        'notice' => '/PHP Notice:/i',
        'deprecated' => '/PHP Deprecated:/i',
        'exception' => '/Exception:/i',
        'critical' => '/CRITICAL:/i',
    ];

    public function analyzeLogFile(string $logFile): array
    {
        if (!file_exists($logFile)) {
            throw new \InvalidArgumentException("Log file not found: {$logFile}");
        }

        $errors = [];
        $errorCounts = [];
        $errorsByType = [];
        $recentErrors = [];

        $handle = fopen($logFile, 'r');
        $lineNumber = 0;

        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            
            foreach (self::ERROR_PATTERNS as $type => $pattern) {
                if (preg_match($pattern, $line)) {
                    // Extract error message
                    $errorMessage = $this->extractErrorMessage($line);
                    $errorHash = md5($errorMessage);

                    if (!isset($errorCounts[$errorHash])) {
                        $errorCounts[$errorHash] = [
                            'message' => $errorMessage,
                            'type' => $type,
                            'count' => 0,
                            'first_seen' => $lineNumber,
                            'last_seen' => $lineNumber,
                        ];
                    }

                    $errorCounts[$errorHash]['count']++;
                    $errorCounts[$errorHash]['last_seen'] = $lineNumber;

                    if (!isset($errorsByType[$type])) {
                        $errorsByType[$type] = 0;
                    }
                    $errorsByType[$type]++;

                    // Keep last 10 errors
                    $recentErrors[] = [
                        'line' => $lineNumber,
                        'type' => $type,
                        'message' => trim($line),
                    ];
                    if (count($recentErrors) > 10) {
                        array_shift($recentErrors);
                    }

                    break;
                }
            }
        }

        fclose($handle);

        // Sort by count (most frequent first)
        uasort($errorCounts, fn($a, $b) => $b['count'] <=> $a['count']);

        return [
            'file' => $logFile,
            'total_lines' => $lineNumber,
            'total_errors' => array_sum($errorsByType),
            'errors_by_type' => $errorsByType,
            'most_common_errors' => array_slice($errorCounts, 0, 10, true),
            'recent_errors' => $recentErrors,
        ];
    }

    private function extractErrorMessage(string $line): string
    {
        // Remove timestamp and log level prefixes
        $message = preg_replace('/^\[.*?\]\s+\w+\.\w+:\s+/', '', $line);
        
        // Truncate very long messages
        if (strlen($message) > 200) {
            $message = substr($message, 0, 200) . '...';
        }

        return trim($message);
    }

    public function getErrorSeverity(string $type): int
    {
        $severities = [
            'fatal' => 5,
            'critical' => 5,
            'error' => 4,
            'exception' => 4,
            'warning' => 3,
            'notice' => 2,
            'deprecated' => 1,
        ];

        return $severities[$type] ?? 0;
    }
}
```

### Error Report Command

**File:** `custom/plugins/LearningBundle/src/Command/ErrorReportCommand.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\ErrorAnalyzerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ErrorReportCommand extends Command
{
    protected static $defaultName = 'learning:error-report';

    private ErrorAnalyzerService $errorAnalyzer;
    private string $projectDir;

    public function __construct(ErrorAnalyzerService $errorAnalyzer, string $projectDir)
    {
        parent::__construct();
        $this->errorAnalyzer = $errorAnalyzer;
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Analyze error logs and generate a summary report')
            ->addOption('log', 'l', InputOption::VALUE_OPTIONAL, 'Log file to analyze', 'dev.log');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $logFileName = $input->getOption('log');
        $logFile = $this->projectDir . '/var/log/' . $logFileName;

        $io->title('Error Log Analysis Report');

        try {
            $analysis = $this->errorAnalyzer->analyzeLogFile($logFile);

            // Overview
            $io->section('Overview');
            $io->table(
                ['Metric', 'Value'],
                [
                    ['Log File', $analysis['file']],
                    ['Total Lines', number_format($analysis['total_lines'])],
                    ['Total Errors', number_format($analysis['total_errors'])],
                ]
            );

            // Errors by Type
            if (!empty($analysis['errors_by_type'])) {
                $io->section('Errors by Type');
                $typeRows = [];
                foreach ($analysis['errors_by_type'] as $type => $count) {
                    $severity = $this->errorAnalyzer->getErrorSeverity($type);
                    $emoji = $this->getSeverityEmoji($severity);
                    $typeRows[] = [$emoji . ' ' . ucfirst($type), number_format($count)];
                }
                $io->table(['Type', 'Count'], $typeRows);
            }

            // Most Common Errors
            if (!empty($analysis['most_common_errors'])) {
                $io->section('Most Common Errors (Top 10)');
                $commonRows = [];
                foreach ($analysis['most_common_errors'] as $error) {
                    $commonRows[] = [
                        $error['count'],
                        ucfirst($error['type']),
                        $this->truncate($error['message'], 80),
                    ];
                }
                $io->table(['Count', 'Type', 'Message'], $commonRows);
            }

            // Recent Errors
            if (!empty($analysis['recent_errors'])) {
                $io->section('Recent Errors (Last 10)');
                $recentRows = [];
                foreach ($analysis['recent_errors'] as $error) {
                    $recentRows[] = [
                        $error['line'],
                        ucfirst($error['type']),
                        $this->truncate($error['message'], 80),
                    ];
                }
                $io->table(['Line', 'Type', 'Message'], $recentRows);
            }

            // Recommendations
            $this->showRecommendations($io, $analysis);

            $io->success('Error analysis completed');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function getSeverityEmoji(int $severity): string
    {
        return match (true) {
            $severity >= 5 => '🔴',
            $severity >= 4 => '🟠',
            $severity >= 3 => '🟡',
            $severity >= 2 => '🔵',
            default => '⚪',
        };
    }

    private function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length - 3) . '...';
    }

    private function showRecommendations(SymfonyStyle $io, array $analysis): void
    {
        $io->section('Recommendations');

        $recommendations = [];

        if (isset($analysis['errors_by_type']['fatal']) || isset($analysis['errors_by_type']['critical'])) {
            $recommendations[] = '🔴 Critical/Fatal errors detected - investigate immediately';
        }

        if (isset($analysis['errors_by_type']['deprecated'])) {
            $recommendations[] = '⚠️  Deprecated warnings found - update code for future compatibility';
        }

        if ($analysis['total_errors'] > 1000) {
            $recommendations[] = '📊 High error count detected - consider implementing error monitoring';
        }

        $mostCommon = reset($analysis['most_common_errors']);
        if ($mostCommon && $mostCommon['count'] > 10) {
            $recommendations[] = sprintf(
                '🔁 Same error occurring %d times - fix root cause: %s',
                $mostCommon['count'],
                $this->truncate($mostCommon['message'], 60)
            );
        }

        if (empty($recommendations)) {
            $io->success('No critical issues detected');
        } else {
            $io->listing($recommendations);
        }
    }
}
```

### Services Configuration

Add to `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<service id="Learning\Bundle\Service\ErrorAnalyzerService"/>

<service id="Learning\Bundle\Command\ErrorReportCommand">
    <argument type="service" id="Learning\Bundle\Service\ErrorAnalyzerService"/>
    <argument>%kernel.project_dir%</argument>
    <tag name="console.command"/>
</service>
```

---

## Exercise 3: Health Check Endpoint

### Health Check Service

**File:** `custom/plugins/LearningBundle/src/Service/HealthCheckService.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class HealthCheckService
{
    private Connection $connection;
    private AdapterInterface $cache;

    public function __construct(Connection $connection, AdapterInterface $cache)
    {
        $this->connection = $connection;
        $this->cache = $cache;
    }

    public function checkHealth(): array
    {
        $checks = [];

        // Database check
        $checks['database'] = $this->checkDatabase();

        // Cache check
        $checks['cache'] = $this->checkCache();

        // Disk space check
        $checks['disk_space'] = $this->checkDiskSpace();

        // PHP extensions
        $checks['php_extensions'] = $this->checkPhpExtensions();

        // Plugin tables
        $checks['plugin_tables'] = $this->checkPluginTables();

        // Overall status
        $overallStatus = 'healthy';
        foreach ($checks as $check) {
            if ($check['status'] === 'error') {
                $overallStatus = 'error';
                break;
            }
            if ($check['status'] === 'warning' && $overallStatus !== 'error') {
                $overallStatus = 'warning';
            }
        }

        return [
            'status' => $overallStatus,
            'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
            'checks' => $checks,
        ];
    }

    private function checkDatabase(): array
    {
        try {
            $result = $this->connection->fetchOne('SELECT 1');
            
            return [
                'status' => $result === 1 ? 'healthy' : 'error',
                'message' => $result === 1 ? 'Database connection OK' : 'Database query failed',
                'details' => [
                    'driver' => $this->connection->getDriver()->getName(),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';

            // Write test
            $item = $this->cache->getItem($testKey);
            $item->set($testValue);
            $this->cache->save($item);

            // Read test
            $item = $this->cache->getItem($testKey);
            $readValue = $item->get();

            // Cleanup
            $this->cache->deleteItem($testKey);

            $isWorking = $readValue === $testValue;

            return [
                'status' => $isWorking ? 'healthy' : 'error',
                'message' => $isWorking ? 'Cache read/write OK' : 'Cache read/write failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache check failed: ' . $e->getMessage(),
            ];
        }
    }

    private function checkDiskSpace(): array
    {
        $varPath = dirname(__DIR__, 5) . '/var';
        $freeSpace = disk_free_space($varPath);
        $totalSpace = disk_total_space($varPath);
        $usedSpace = $totalSpace - $freeSpace;
        $usedPercentage = ($usedSpace / $totalSpace) * 100;

        $status = 'healthy';
        $message = 'Disk space OK';

        if ($usedPercentage > 90) {
            $status = 'error';
            $message = 'Disk space critically low';
        } elseif ($usedPercentage > 80) {
            $status = 'warning';
            $message = 'Disk space running low';
        }

        return [
            'status' => $status,
            'message' => $message,
            'details' => [
                'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'total_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'used_percentage' => round($usedPercentage, 2),
            ],
        ];
    }

    private function checkPhpExtensions(): array
    {
        $required = ['pdo', 'pdo_mysql', 'json', 'xml', 'zip', 'curl', 'gd'];
        $missing = [];

        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        $status = empty($missing) ? 'healthy' : 'error';
        $message = empty($missing) 
            ? 'All required PHP extensions loaded' 
            : 'Missing PHP extensions: ' . implode(', ', $missing);

        return [
            'status' => $status,
            'message' => $message,
            'details' => [
                'php_version' => PHP_VERSION,
                'missing_extensions' => $missing,
            ],
        ];
    }

    private function checkPluginTables(): array
    {
        try {
            $tables = [
                'learning_product_view',
                'learning_product_rating',
                'learning_wishlist',
                'learning_product_comparison',
            ];

            $existingTables = $this->connection->createSchemaManager()->listTableNames();
            $missing = array_diff($tables, $existingTables);

            $status = empty($missing) ? 'healthy' : 'warning';
            $message = empty($missing) 
                ? 'All plugin tables exist' 
                : 'Missing plugin tables: ' . implode(', ', $missing);

            return [
                'status' => $status,
                'message' => $message,
                'details' => [
                    'expected_tables' => count($tables),
                    'existing_tables' => count($tables) - count($missing),
                    'missing_tables' => $missing,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Plugin table check failed: ' . $e->getMessage(),
            ];
        }
    }
}
```

### Health Check Controller

**File:** `custom/plugins/LearningBundle/src/Controller/HealthCheckController.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Controller;

use Learning\Bundle\Service\HealthCheckService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class HealthCheckController extends AbstractController
{
    private HealthCheckService $healthCheck;

    public function __construct(HealthCheckService $healthCheck)
    {
        $this->healthCheck = $healthCheck;
    }

    /**
     * @Route("/api/_action/learning/health", name="api.action.learning.health", methods={"GET"})
     */
    public function check(): JsonResponse
    {
        $health = $this->healthCheck->checkHealth();

        $statusCode = match ($health['status']) {
            'healthy' => Response::HTTP_OK,
            'warning' => Response::HTTP_OK,
            'error' => Response::HTTP_SERVICE_UNAVAILABLE,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };

        return new JsonResponse($health, $statusCode);
    }
}
```

### Health Check Command

**File:** `custom/plugins/LearningBundle/src/Command/HealthCheckCommand.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\HealthCheckService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class HealthCheckCommand extends Command
{
    protected static $defaultName = 'learning:health-check';

    private HealthCheckService $healthCheck;

    public function __construct(HealthCheckService $healthCheck)
    {
        parent::__construct();
        $this->healthCheck = $healthCheck;
    }

    protected function configure(): void
    {
        $this->setDescription('Check system health');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('System Health Check');

        $health = $this->healthCheck->checkHealth();

        // Overall status
        $statusEmoji = match ($health['status']) {
            'healthy' => '✅',
            'warning' => '⚠️',
            'error' => '❌',
            default => '❓',
        };

        $io->section('Overall Status: ' . $statusEmoji . ' ' . strtoupper($health['status']));

        // Individual checks
        foreach ($health['checks'] as $name => $check) {
            $checkEmoji = match ($check['status']) {
                'healthy' => '✅',
                'warning' => '⚠️',
                'error' => '❌',
                default => '❓',
            };

            $io->writeln(sprintf(
                '%s <info>%s</info>: %s',
                $checkEmoji,
                ucwords(str_replace('_', ' ', $name)),
                $check['message']
            ));

            if (isset($check['details']) && !empty($check['details'])) {
                $io->writeln('  Details:');
                foreach ($check['details'] as $key => $value) {
                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }
                    $io->writeln(sprintf('    %s: %s', ucwords(str_replace('_', ' ', $key)), $value));
                }
            }
        }

        $io->newLine();

        if ($health['status'] === 'healthy') {
            $io->success('All systems operational');
            return Command::SUCCESS;
        } elseif ($health['status'] === 'warning') {
            $io->warning('System has warnings');
            return Command::SUCCESS;
        } else {
            $io->error('System has errors');
            return Command::FAILURE;
        }
    }
}
```

### Services Configuration

Add to `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<service id="Learning\Bundle\Service\HealthCheckService">
    <argument type="service" id="Doctrine\DBAL\Connection"/>
    <argument type="service" id="cache.app"/>
</service>

<service id="Learning\Bundle\Controller\HealthCheckController" public="true">
    <argument type="service" id="Learning\Bundle\Service\HealthCheckService"/>
    <tag name="controller.service_arguments"/>
</service>

<service id="Learning\Bundle\Command\HealthCheckCommand">
    <argument type="service" id="Learning\Bundle\Service\HealthCheckService"/>
    <tag name="console.command"/>
</service>
```

---

## Testing All Solutions

### Test Commands

```bash
# Test performance profiler
bin/console learning:profile-operations -i 20

# Test error report
bin/console learning:error-report

# Test health check
bin/console learning:health-check
```

### Test Health Check API

```bash
#!/bin/bash

# test-health-check.sh
BASE_URL="http://localhost:8000"
ACCESS_TOKEN="your-admin-access-token"

echo "=== Testing Health Check API ==="

curl "${BASE_URL}/api/_action/learning/health" \
  -H "Authorization: Bearer ${ACCESS_TOKEN}" \
  -H "Accept: application/json" | jq
```

---

## Monitoring Dashboard (Bonus)

### Create Monitoring Dashboard Command

**File:** `custom/plugins/LearningBundle/src/Command/MonitoringDashboardCommand.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\ErrorAnalyzerService;
use Learning\Bundle\Service\HealthCheckService;
use Learning\Bundle\Service\PerformanceProfilerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MonitoringDashboardCommand extends Command
{
    protected static $defaultName = 'learning:monitoring-dashboard';

    private HealthCheckService $healthCheck;
    private ErrorAnalyzerService $errorAnalyzer;
    private PerformanceProfilerService $profiler;
    private string $projectDir;

    public function __construct(
        HealthCheckService $healthCheck,
        ErrorAnalyzerService $errorAnalyzer,
        PerformanceProfilerService $profiler,
        string $projectDir
    ) {
        parent::__construct();
        $this->healthCheck = $healthCheck;
        $this->errorAnalyzer = $errorAnalyzer;
        $this->profiler = $profiler;
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this->setDescription('Display comprehensive monitoring dashboard');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Learning Bundle Monitoring Dashboard');
        $io->writeln(sprintf('Generated: %s', (new \DateTime())->format('Y-m-d H:i:s')));
        $io->newLine();

        // Health Check
        $io->section('🏥 System Health');
        $health = $this->healthCheck->checkHealth();
        $io->writeln(sprintf('Overall Status: %s', strtoupper($health['status'])));
        
        $healthTable = [];
        foreach ($health['checks'] as $name => $check) {
            $healthTable[] = [
                ucwords(str_replace('_', ' ', $name)),
                $check['status'],
                $check['message'],
            ];
        }
        $io->table(['Check', 'Status', 'Message'], $healthTable);

        // Error Analysis
        $io->section('🔍 Error Analysis');
        try {
            $logFile = $this->projectDir . '/var/log/dev.log';
            $analysis = $this->errorAnalyzer->analyzeLogFile($logFile);
            
            $io->writeln(sprintf('Total Errors: %d', $analysis['total_errors']));
            
            if (!empty($analysis['errors_by_type'])) {
                $errorTable = [];
                foreach ($analysis['errors_by_type'] as $type => $count) {
                    $errorTable[] = [ucfirst($type), $count];
                }
                $io->table(['Type', 'Count'], $errorTable);
            }
        } catch (\Exception $e) {
            $io->warning('Could not analyze error logs: ' . $e->getMessage());
        }

        // Performance Metrics
        $io->section('⚡ Performance');
        $activeProfiles = $this->profiler->getActiveProfiles();
        if (!empty($activeProfiles)) {
            $io->table(
                ['Operation', 'Running Time (ms)'],
                array_map(fn($p) => [$p['name'], $p['running_time']], $activeProfiles)
            );
        } else {
            $io->writeln('No active operations');
        }

        $io->success('Dashboard generated successfully');

        return Command::SUCCESS;
    }
}
```

---

## Key Takeaways

✅ **You've mastered:**
- Performance profiling with timing and memory tracking
- Log file analysis and error reporting
- Health check implementation for system monitoring
- Creating comprehensive debugging tools
- Building monitoring dashboards
- Stack trace analysis
- Automated error detection

## Best Practices

✅ **DO:**
- Profile operations regularly
- Monitor error logs daily
- Set up automated health checks
- Track performance regressions
- Use structured logging

❌ **DON'T:**
- Ignore slow operations
- Let errors accumulate
- Skip health checks in production
- Profile in production without care
- Log sensitive data

---

**Next:** Day 6 - Testing and Caching
