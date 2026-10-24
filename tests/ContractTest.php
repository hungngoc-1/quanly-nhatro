<?php

declare(strict_types=1);

namespace Tests;

use App\Models\ContractModel;
use InvalidArgumentException;

// Tự động nạp Autoloader nếu chạy trực tiếp qua PHP CLI trên WAMP
if (!class_exists(ContractModel::class)) {
    spl_autoload_register(function (string $class): void {
        $prefix = 'App\\';
        $baseDir = dirname(__DIR__) . '/app/';
        if (strncmp($prefix, $class, strlen($prefix)) === 0) {
            $file = $baseDir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    });
}

if (!class_exists(\PHPUnit\Framework\TestCase::class)) {
    abstract class StandaloneContractTestCase
    {
        protected int $assertions = 0;

        protected function assertSame(mixed $expected, mixed $actual, string $message = ''): void
        {
            $this->assertions++;
            if ($expected !== $actual) {
                throw new \RuntimeException($message ?: "Expected " . var_export($expected, true) . " but got " . var_export($actual, true));
            }
        }

        protected function assertEmpty(mixed $actual, string $message = ''): void
        {
            $this->assertions++;
            if (!empty($actual)) {
                throw new \RuntimeException($message ?: "Expected empty value, got: " . var_export($actual, true));
            }
        }

        protected function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
        {
            $this->assertions++;
            if (!array_key_exists($key, $array)) {
                throw new \RuntimeException($message ?: "Expected array to have key '{$key}'");
            }
        }
    }
    class_alias(StandaloneContractTestCase::class, \PHPUnit\Framework\TestCase::class);
}

class ContractTest extends \PHPUnit\Framework\TestCase
{
    private ContractModel $contractModel;

    public function setUp(): void
    {
        $this->contractModel = new ContractModel();
    }

    public function testValidContractDataPassesValidation(): void
    {
        $errors = $this->contractModel->validateContractData([
            'room_id'        => 1,
            'tenant_id'      => 2,
            'start_date'     => '2026-01-01',
            'end_date'       => '2026-12-31',
            'monthly_rent'   => 2800000,
            'deposit_amount' => 2800000,
        ]);

        $this->assertEmpty($errors);
    }

    public function testEndDateBeforeStartDateFailsValidation(): void
    {
        $errors = $this->contractModel->validateContractData([
            'room_id'        => 1,
            'tenant_id'      => 2,
            'start_date'     => '2026-10-01',
            'end_date'       => '2026-05-01',
            'monthly_rent'   => 2800000,
            'deposit_amount' => 1000000,
        ]);

        $this->assertArrayHasKey('end_date', $errors);
    }

    public function testNegativeRentOrDepositFailsValidation(): void
    {
        $errors = $this->contractModel->validateContractData([
            'room_id'        => 1,
            'tenant_id'      => 1,
            'start_date'     => '2026-01-01',
            'end_date'       => '2026-12-31',
            'monthly_rent'   => 0,
            'deposit_amount' => -500000,
        ]);

        $this->assertArrayHasKey('monthly_rent', $errors);
        $this->assertArrayHasKey('deposit_amount', $errors);
    }

    public function testCalculateRemainingDaysAndContractHealth(): void
    {
        $days = $this->contractModel->calculateRemainingDays('2026-10-30', '2026-10-20');
        $this->assertSame(10, $days);

        $healthSoon = $this->contractModel->evaluateContractHealth([
            'status'   => 'active',
            'end_date' => '2026-11-05',
        ], '2026-10-20');
        $this->assertSame('expiring_soon', $healthSoon);

        $healthActive = $this->contractModel->evaluateContractHealth([
            'status'   => 'active',
            'end_date' => '2027-03-31',
        ], '2026-10-20');
        $this->assertSame('active', $healthActive);

        $healthExpired = $this->contractModel->evaluateContractHealth([
            'status'   => 'active',
            'end_date' => '2026-10-01',
        ], '2026-10-20');
        $this->assertSame('expired', $healthExpired);
    }
}

// Chạy trực tiếp trên CLI nếu gọi `php tests/ContractTest.php`
if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $test = new ContractTest();
    $methods = array_filter(get_class_methods($test), fn($m) => str_starts_with($m, 'test'));
    $passed = 0;
    foreach ($methods as $method) {
        $test->setUp();
        $test->$method();
        echo "[PASS] ContractTest::{$method}\n";
        $passed++;
    }
    echo "=> ContractTest hoàn tất: {$passed}/" . count($methods) . " tests đạt.\n";
}
