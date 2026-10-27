<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\CoreHelper;
use PDO;

class PaymentModel
{
    private ?PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db;
    }

    private function db(): PDO
    {
        if ($this->db === null) {
            $this->db = Database::getConnection();
        }
        return $this->db;
    }

    public function getAll(array $filters = []): array
    {
        $buildingId = array_key_exists('building_id', $filters)
            ? ($filters['building_id'] !== null && $filters['building_id'] !== '' ? (int) $filters['building_id'] : null)
            : CoreHelper::currentBuildingId();

        $sql = "
            SELECT p.*,
                   inv.invoice_code,
                   inv.month,
                   inv.year,
                   inv.total_amount AS invoice_total,
                   r.room_number,
                   r.building_id,
                   b.building_name,
                   t.full_name AS tenant_name,
                   u.full_name AS receiver_name
            FROM payments p
            INNER JOIN invoices inv ON inv.id = p.invoice_id
            INNER JOIN rooms r ON r.id = inv.room_id
            LEFT JOIN buildings b ON b.id = r.building_id
            INNER JOIN tenants t ON t.id = inv.tenant_id
            LEFT JOIN users u ON u.id = p.received_by
            WHERE 1=1
        ";
        $params = [];

        if ($buildingId !== null && $buildingId > 0) {
            $sql .= " AND r.building_id = :building_id";
            $params['building_id'] = $buildingId;
        }

        if (!empty($filters['payment_method'])) {
            $sql .= " AND p.payment_method = :payment_method";
            $params['payment_method'] = $filters['payment_method'];
        }

        if (!empty($filters['invoice_id'])) {
            $sql .= " AND p.invoice_id = :invoice_id";
            $params['invoice_id'] = (int) $filters['invoice_id'];
        }

        if (!empty($filters['keyword'])) {
            $sql .= " AND (p.payment_code LIKE :kw1 OR inv.invoice_code LIKE :kw2 OR r.room_number LIKE :kw3 OR t.full_name LIKE :kw4)";
            $kw = '%' . trim($filters['keyword']) . '%';
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
            $params['kw3'] = $kw;
            $params['kw4'] = $kw;
        }

        $sql .= " ORDER BY p.payment_date DESC, p.id DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getByInvoiceId(int $invoiceId): array
    {
        return $this->getAll(['invoice_id' => $invoiceId]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM payments WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function generatePaymentCode(): string
    {
        return sprintf('PAY-%s-%03d', date('Ym'), random_int(100, 999));
    }

    public function create(array $data): int
    {
        $pdo = $this->db();
        $pdo->beginTransaction();

        try {
            $code = !empty($data['payment_code']) ? trim($data['payment_code']) : $this->generatePaymentCode();
            $invoiceId = (int) $data['invoice_id'];

            $stmt = $pdo->prepare("
                INSERT INTO payments (
                    payment_code, invoice_id, amount, payment_method,
                    transaction_ref, payment_date, received_by, notes
                ) VALUES (
                    :payment_code, :invoice_id, :amount, :payment_method,
                    :transaction_ref, :payment_date, :received_by, :notes
                )
            ");

            $stmt->execute([
                'payment_code'    => $code,
                'invoice_id'      => $invoiceId,
                'amount'          => (float) $data['amount'],
                'payment_method'  => $data['payment_method'] ?? 'bank_transfer',
                'transaction_ref' => !empty($data['transaction_ref']) ? trim($data['transaction_ref']) : null,
                'payment_date'    => !empty($data['payment_date']) ? $data['payment_date'] : date('Y-m-d H:i:s'),
                'received_by'     => !empty($data['received_by']) ? (int) $data['received_by'] : null,
                'notes'           => trim($data['notes'] ?? ''),
            ]);

            $paymentId = (int) $pdo->lastInsertId();

            $invoiceModel = new InvoiceModel($pdo);
            $invoiceModel->recalculatePaymentStatus($invoiceId);

            $pdo->commit();
            return $paymentId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $payment = $this->findById($id);
        if (!$payment) {
            return false;
        }

        $pdo = $this->db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("DELETE FROM payments WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $invoiceModel = new InvoiceModel($pdo);
            $invoiceModel->recalculatePaymentStatus((int) $payment['invoice_id']);

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
