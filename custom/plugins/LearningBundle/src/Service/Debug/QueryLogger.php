<?php declare(strict_types=1);

namespace Learning\Bundle\Service\Debug;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Psr\Log\LoggerInterface;

class QueryLoggingMiddleware implements Middleware
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function wrap(Driver $driver): Driver
    {
        return new class($driver, $this->logger) extends AbstractDriverMiddleware {
            private LoggerInterface $logger;

            public function __construct(Driver $wrappedDriver, LoggerInterface $logger)
            {
                parent::__construct($wrappedDriver);
                $this->logger = $logger;
            }

            public function connect(array $params): DriverConnection
            {
                return new QueryLoggingConnection(
                    parent::connect($params),
                    $this->logger
                );
            }
        };
    }
}

class QueryLoggingConnection implements DriverConnection
{
    private DriverConnection $connection;
    private LoggerInterface $logger;

    public function __construct(DriverConnection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function prepare(string $sql): Statement
    {
        return new QueryLoggingStatement(
            $this->connection->prepare($sql),
            $this->logger,
            $sql
        );
    }

    public function query(string $sql): Result
    {
        $start = microtime(true);
        $result = $this->connection->query($sql);
        $this->logQuery($sql, [], microtime(true) - $start);
        
        return $result;
    }

    public function exec(string $sql): int
    {
        $start = microtime(true);
        $result = $this->connection->exec($sql);
        $this->logQuery($sql, [], microtime(true) - $start);
        
        return $result;
    }

    public function lastInsertId($name = null): string
    {
        return $this->connection->lastInsertId($name);
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    public function quote($value, $type = 2): string
    {
        return $this->connection->quote($value, $type);
    }

    public function getServerVersion(): string
    {
        return $this->connection->getServerVersion();
    }

    public function getNativeConnection()
    {
        return $this->connection->getNativeConnection();
    }

    private function logQuery(string $sql, array $params, float $executionTime): void
    {
        if ($executionTime > 0.1) { // Log queries taking more than 100ms
            $this->logger->warning('Slow query detected', [
                'query' => $sql,
                'params' => $params,
                'duration_ms' => $executionTime * 1000,
            ]);
        }
    }
}

class QueryLoggingStatement implements Statement
{
    private Statement $statement;
    private LoggerInterface $logger;
    private string $sql;

    public function __construct(Statement $statement, LoggerInterface $logger, string $sql)
    {
        $this->statement = $statement;
        $this->logger = $logger;
        $this->sql = $sql;
    }

    public function bindValue($param, $value, $type = 2): void
    {
        $this->statement->bindValue($param, $value, $type);
    }

    public function bindParam($param, &$variable, $type = 2, $length = null): void
    {
        $this->statement->bindParam($param, $variable, $type, $length);
    }

    public function execute($params = null): Result
    {
        $start = microtime(true);
        $result = $this->statement->execute($params);
        
        $this->logQuery($this->sql, $params ?? [], microtime(true) - $start);
        
        return $result;
    }

    private function logQuery(string $sql, array $params, float $executionTime): void
    {
        if ($executionTime > 0.1) { // Log queries taking more than 100ms
            $this->logger->warning('Slow query detected', [
                'query' => $sql,
                'params' => $params,
                'duration_ms' => $executionTime * 1000,
            ]);
        }
    }
}