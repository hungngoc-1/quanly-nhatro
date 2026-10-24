<?php

use App\Helpers\CoreHelper;

?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <p class="text-muted mb-0">Tra cứu danh sách phiếu báo tiền phòng, tiền điện nước và trạng thái thanh toán hàng tháng.</p>
    </div>

    <form method="GET" action="<?= CoreHelper::url('/invoices/my-list') ?>" class="d-flex gap-2">
        <select name="status" class="form-select form-select-sm" style="width: 160px;">
            <option value="">-- Tất cả trạng thái --</option>
            <option value="unpaid" <?= ($filters['status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>Chưa đóng</option>
            <option value="partial" <?= ($filters['status'] ?? '') === 'partial' ? 'selected' : '' ?>>Đóng 1 phần</option>
            <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
            <option value="overdue" <?= ($filters['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Quá hạn</option>
        </select>
        <button type="submit" class="btn btn-sm btn-secondary"><i class="bi bi-funnel me-1"></i>Lọc</button>
    </form>
</div>

<div class="row g-3">
    <?php if (empty($invoices)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm p-5 text-center text-muted">
                Không tìm thấy hóa đơn nào.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($invoices as $inv): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <div>
                            <span class="badge bg-dark me-1"><?= CoreHelper::e($inv['room_number']) ?></span>
                            <strong>Kỳ Tháng <?= (int) $inv['month'] ?>/<?= (int) $inv['year'] ?></strong>
                        </div>
                        <?php if ($inv['status'] === 'paid'): ?>
                            <span class="badge bg-success">Đã thanh toán</span>
                        <?php elseif ($inv['status'] === 'partial'): ?>
                            <span class="badge bg-warning text-dark">Đóng 1 phần</span>
                        <?php elseif ($inv['status'] === 'overdue'): ?>
                            <span class="badge bg-danger">Quá hạn</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Chưa đóng</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="small text-muted mb-2">Mã hóa đơn: <strong><?= CoreHelper::e($inv['invoice_code']) ?></strong> &bull; Khách: <?= CoreHelper::e($inv['tenant_name']) ?></div>
                        <ul class="list-group list-group-flush small mb-3">
                            <li class="list-group-item d-flex justify-content-between px-0 py-1">
                                <span>Tiền thuê phòng:</span>
                                <span><?= CoreHelper::formatCurrency($inv['room_fee']) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-1">
                                <span>Tiền điện + nước:</span>
                                <span><?= CoreHelper::formatCurrency((float) $inv['electric_fee'] + (float) $inv['water_fee']) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-1">
                                <span>Phí dịch vụ:</span>
                                <span><?= CoreHelper::formatCurrency($inv['service_fee']) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 fw-bold">
                                <span>Tổng cộng:</span>
                                <span class="text-primary fs-6"><?= CoreHelper::formatCurrency($inv['total_amount']) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-1 text-danger fw-semibold">
                                <span>Còn cần thanh toán:</span>
                                <span><?= CoreHelper::formatCurrency($inv['remaining_amount']) ?></span>
                            </li>
                        </ul>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Hạn đóng: <?= CoreHelper::formatDate($inv['due_date']) ?></small>
                            <a href="<?= CoreHelper::url('/invoices/detail', ['id' => $inv['id']]) ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-file-earmark-text me-1"></i> Xem chi tiết / In phiếu
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
