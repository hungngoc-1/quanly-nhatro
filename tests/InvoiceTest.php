<?php

declare(strict_types=1);

namespace Tests;

use App\Models\InvoiceModel;
use InvalidArgumentException;

if (!class_exists(InvoiceModel::class)) {
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
    abstract class StandaloneInvoiceTestCase
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
    class_alias(StandaloneInvoiceTestCase::class, \PHPUnit\Framework\TestCase::class);
}

class InvoiceTest extends \PHPUnit\Framework\TestCase
{
    private InvoiceModel $invoiceModel;

    public function setUp(): void
    {
        $this->invoiceModel = new InvoiceModel();
    }

    public function testCalculateInvoiceTotalWithAndWithoutDiscount(): void
    {
        $total = $this->invoiceModel->calculateInvoiceTotal(
            roomFee: 2800000.0,
            electricFee: 420000.0,
            waterFee: 135000.0,
            serviceFee: 150000.0,
            discount: 50000.0
        );

        $this->assertEqualsWithDelta(3455000.0, $total, 0.01);
    }

    public function testDeterminePaymentStatusTransitions(): void
    {
        // Đã đóng đủ
        $statusPaid = $this->invoiceModel->determinePaymentStatus(3500000.0, 3500000.0, '2026-10-25', '2026-10-20');
        $this->assertSame('paid', $statusPaid);

        // Đóng 1 phần
        $statusPartial = $this->invoiceModel->determinePaymentStatus(3500000.0, 2000000.0, '2026-10-25', '2026-10-20');
        $this->assertSame('partial', $statusPartial);

        // Chưa đóng và còn trong hạn
        $statusUnpaid = $this->invoiceModel->determinePaymentStatus(3500000.0, 0.0, '2026-10-25', '2026-10-20');
        $this->assertSame('unpaid', $statusUnpaid);

        // Chưa đóng và đã quá hạn
        $statusOverdue = $this->invoiceModel->determinePaymentStatus(3500000.0, 0.0, '2026-10-15', '2026-10-20');
        $this->assertSame('overdue', $statusOverdue);
    }

    public function testDiscountExceedingTotalThrowsException(): void
    {
        $thrown = false;
        try {
            $this->invoiceModel->calculateInvoiceTotal(1000000, 100000, 50000, 50000, 1500000);
        } catch (InvalidArgumentException) {
            $thrown = true;
        }
        $this->assertSame(true, $thrown, 'Phải ném ngoại lệ khi tiền giảm giá vượt quá tổng tiền hóa đơn.');
    }
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $test = new InvoiceTest();
    $methods = array_filter(get_class_methods($test), fn($m) => str_starts_with($m, 'test'));
    $passed = 0;
    foreach ($methods as $method) {
        $test->setUp();
        $test->$method();
        echo "[PASS] InvoiceTest::{$method}\n";
        $passed++;
    }
    echo "=> InvoiceTest hoàn tất: {$passed}/" . count($methods) . " tests đạt.\n";
}
