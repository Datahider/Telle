<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use losthost\DB\DB;
use losthost\telle\Bot;
use losthost\telle\Worker;
use losthost\telle\model\DBPendingUpdate;

final class WorkerTestPdo extends PDO
{
    public function __construct()
    {
        parent::__construct('sqlite::memory:');
    }

    public function getAttribute(int $attribute): mixed
    {
        if ($attribute === PDO::ATTR_SERVER_INFO) {
            return 'test';
        }
        return parent::getAttribute($attribute);
    }
}

final class WorkerTestPendingUpdate extends DBPendingUpdate
{
    public bool $deleted = false;
    public bool $in_transaction_on_delete = false;

    public function __construct()
    {
    }

    public function delete($comment = '', $data = null)
    {
        $this->deleted = true;
        $this->in_transaction_on_delete = DB::inTransaction();
    }
}

final class WorkerTestWorker extends Worker
{
    public function __construct(private Closure $handler)
    {
    }

    protected function handleQueuedUpdate(DBPendingUpdate $queued_update): void
    {
        ($this->handler)();
    }

    public function process(DBPendingUpdate $queued_update): void
    {
        $this->processQueuedUpdate($queued_update);
    }
}

function workerAssertSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

$pdo_property = new ReflectionProperty(DB::class, 'pdo');
$pdo_property->setValue(null, new WorkerTestPdo());
$transaction_property = new ReflectionProperty(DB::class, 'in_transaction');
$transaction_property->setValue(null, false);
Bot::$working_thread = 'test';
ini_set('display_errors', 'stderr');
ini_set('error_log', sys_get_temp_dir() . '/telle-worker-test.log');

$cases = [
    'throwable inside transaction' => function (): void {
        DB::beginTransaction();
        throw new Error('handler failed');
    },
    'exception inside transaction' => function (): void {
        DB::beginTransaction();
        throw new Exception('handler failed');
    },
    'transaction left open without exception' => function (): void {
        DB::beginTransaction();
    },
    'committed transaction' => function (): void {
        DB::beginTransaction();
        DB::commit();
    },
    'throwable without transaction' => function (): void {
        throw new Error('handler failed');
    },
];

foreach ($cases as $name => $handler) {
    $queued_update = new WorkerTestPendingUpdate();
    (new WorkerTestWorker($handler))->process($queued_update);
    workerAssertSame(false, DB::inTransaction(), "$name: transaction closed");
    workerAssertSame(true, $queued_update->deleted, "$name: update deleted");
    workerAssertSame(false, $queued_update->in_transaction_on_delete, "$name: deleted outside transaction");
}

echo "Telle worker transaction tests: OK\n";
