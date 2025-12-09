<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;

class CounterService
{
    private const COUNTER_FILE = 'var/learning_counter.txt';

    private LoggerInterface $logger;
    private string $counterFilePath;

    public function __construct(LoggerInterface $logger, string $projectDir)
    {
        $this->logger = $logger;
        $this->counterFilePath = $projectDir . '/' . self::COUNTER_FILE;
    }

    /**
     * Increments the counter stored in the file and returns the new value.
     */
    public function incrementCount(): int
    {
        $currentCount = $this -> getCount();
        $newCount = $currentCount + 1;

        $this -> saveCount($newCount);

        $this->logger->info('Counter incremented', [
            'old_value' => $currentCount,
            'new_value' => $newCount,
        ]);
        
        return $newCount;
    }

    /**
     * Get the current counter value from the file.
     */
    public function getCount(): int
    {
        if (!file_exists($this->counterFilePath)) {
            return 0;
        }

        $content = file_get_contents($this->counterFilePath);

        if ($content === false) {
            $this->logger->error('Failed to read counter file', [
                'file' => $this->counterFilePath
            ]);
            return 0;
        }

        return (int) $content;
    }

    /**
     * Reset the counter to zero.
     */
    public function resetCount(): void
    {
        $this->saveCount(0);
        $this->logger->info('Counter reset to zero');
    }

    /*
     * Save the counter value to the file.
     */
    private function saveCount(int $count): void
    {
        // Make sure directory exists
        $directory = dirname($this->counterFilePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $result = file_put_contents($this->counterFilePath, (string) $count);

        if ($result === false) {
            $this->logger->error('Failed to write counter file', [
                'file_path' => $this->counterFilePath,
                'count' => $count,
            ]);
        }

    }

    /**
     * Get statistics about the counter
     */
    public function getStatistics(): array
    {
        $count = $this->getCount();
        $fileExists = file_exists($this->counterFilePath);
        $lastModified = $fileExists ? filemtime($this->counterFilePath) : null;

        return [
            'count' => $count,
            'file_exists' => $fileExists,
            'file_path' => $this->counterFilePath,
            'file_modified' => $lastModified ? date('Y-m-d H:i:s', $lastModified) : null,
        ];
    }
}