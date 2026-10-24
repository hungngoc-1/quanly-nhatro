<?php

declare(strict_types=1);

namespace Tests;

use App\Models\UtilityModel;
use InvalidArgumentException;

if (!class_exists(UtilityModel::class)) {
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
    abstract class StandaloneUtilityTestCase
    {
        protected function assertSame(mixed $expected, mixed $actual, string $message = ''): void
        {
            if ($expected !== $actual) {
                throw new \RuntimeException($message ?: "Expected " . var_export($expected, true) . " but got " . var_export($actual, true));
            }
        }

        protected function assertEqualsWithDelta(float $expected, float $actual, float $delta, string $message = ''): void
        {
            if (abs($expected - $actual) > $delta) {
                throw new \RuntimeException($message ?: "Expected {$expected} +/- {$delta}, got {$actual}");
            }
        }
    }
    class_alias(StandaloneUtilityTestCase::class, \PHPUnit\Framework\TestCase::class);
}

class UtilityTest extends \PHPUnit\Framework\TestCase
{
    private UtilityModel $utilityModel;

    public function setUp(): void
    {
        $this->utilityModel = new UtilityModel();
    }

    public function testCalculateElectricityAndWaterAccurately(): void
    {
        $result = $this->utilityModel->calculateUsageAndCost(
            oldElectric: 1200,
            newElectric: 1320, // 120 kWh * 3500 = 420,000
            oldWater: 80,
            newWater: 89,      // 9 m3 * 15000 = 135,000
            electricRate: 3500.0,
            waterRate: 15000.0,
            otherFee: 150000.0
        );

        $this->assertSame(120, $result['electric_used']);
        $this->assertSame(9, $result['water_used']);
        $this->assertEqualsWithDelta(420000.0, $result['electric_amount'], 0.01);
        $this->assertEqualsWithDelta(135000.0, $result['water_amount'], 0.01);
        $this->assertEqualsWithDelta(705000.0, $result['total_utility_amount'], 0.01);
    }

    public function testThrowsExceptionWhenNewElectricLessThanOldElectric(): void
    {
        $thrown = false;
        try {
            $this->utilityModel->calculateUsageAndCost(500, 480, 20, 25);
        } catch (InvalidArgumentException) {
            $thrown = true;
        }
        $this->assertSame(true, $thrown, 'Phải báo lỗi khi chỉ số điện mới nhỏ hơn chỉ số điện cũ.');
    }

    public function testThrowsExceptionWhenNewWaterLessThanOldWater(): void
    {
        $thrown = false;
        try {
            $this->utilityModel->calculateUsageAndCost(500, 550, 30, 25);
        } catch (InvalidArgumentException) {
            $thrown = true;
        }
        $this->assertSame(true, $thrown, 'Phải báo lỗi khi chỉ số nước mới nhỏ hơn chỉ số nước cũ.');
    }
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $test = new UtilityTest();
    $methods = array_filter(get_class_methods($test), fn($m) => str_starts_with($m, 'test'));
    $passed = 0;
    foreach ($methods as $method) {
        $test->setUp();
        $test->$method();
        echo "[PASS] UtilityTest::{$method}\n";
        $passed++;
    }
    echo "=> UtilityTest hoàn tất: {$passed}/" . count($methods) . " tests đạt.\n";
}
