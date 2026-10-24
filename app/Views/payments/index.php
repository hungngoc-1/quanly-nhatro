<?php

use App\Helpers\CoreHelper;

$methodLabels = [
    'bank_transfer' => ['text' => 'Chuyển khoản', 'class' => 'bg-primary'],
    'cash'          => ['text' => 'Tiền mặt', 'class' => 'bg-success'],
    'momo'          => ['text' => 'Ví MoMo', 'class' => 'bg-danger'],
    'vnpay'         => ['text' => 'VNPay', 'class' => 'bg-info text-dark'],
];
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <form method="GET" action="<?= CoreHelper::url('/payments') ?>" class="d-flex gap-2 flex-wrap">
        <input type="text" name="keyword" value="<?= CoreHelper::e($filters['keyword'] ?? '') ?>" class="form-control form-control-sm" style="width: 240px;" placeholder="Mã phiếu thu, mã HĐ, phòng, tên khách...">
        <select name="payment_method" class="form-select form-select-sm" style="width: 170px;">
            <option value="">-- Hình thức thu --</option>
            <option value="bank_transfer" <?= ($filters['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : '' ?>>Chuyển khoản</option>
            <option value="cash" <?= ($filters['payment_method'] ?? '') === 'cash' ? 'selected' : '' ?>>Tiền mặt</option>
            <option value="momo" <?= ($filters['payment_method'] ?? '') === 'momo' ? 'selected' : '' ?>>Ví MoMo</option>
            <option value="vnpay" <?= ($filters['payment_method'] ?? '') === 'vnpay' ? 'selected' : '' ?>>VNPay</option>
        </select>
        <button type="submit" class="btn btn-sm btn-secondary"><i class="bi bi-search me-1"></i>Lọc</button>
    </form>

    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
        <i class="bi bi-cash-stack me-1"></i> Ghi nhận Phiếu thu mới
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã Phiếu thu</th>
                        <th>Hóa đơn</th>
                        <th>Phòng / Khách thuê</th>
                        <th>Số tiền thu</th>
                        <th>Hình thức</th>
                        <th>Ngày giao dịch</th>
                        <th>Người thu</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Chưa có giao dịch thanh toán nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                            <?php $mBadge = $methodLabels[$p['payment_method']] ?? ['text' => $p['payment_method'], 'class' => 'bg-secondary']; ?>
                            <tr>
                                <td class="fw-bold"><?= CoreHelper::e($p['payment_code']) ?></td>
                                <td>
                                    <a href="<?= CoreHelper::url('/invoices/detail', ['id' => $p['invoice_id']]) ?>" class="fw-semibold text-decoration-none">
                                        <?= CoreHelper::e($p['invoice_code']) ?>
                                    </a>
                                    <small class="d-block text-muted">Kỳ T<?= (int) $p['month'] ?>/<?= (int) $p['year'] ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-dark"><?= CoreHelper::e($p['room_number']) ?></span>
                                    <span class="ms-1 fw-medium"><?= CoreHelper::e($p['tenant_name']) ?></span>
                                </td>
                                <td class="fw-bold text-success fs-6">+ <?= CoreHelper::formatCurrency($p['amount']) ?></td>
                                <td>
                                    <span class="badge <?= $mBadge['class'] ?>"><?= $mBadge['text'] ?></span>
                                    <?php if (!empty($p['transaction_ref'])): ?>
                                        <small class="d-block text-muted">Ref: <?= CoreHelper::e($p['transaction_ref']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?= CoreHelper::formatDate($p['payment_date'], 'd/m/Y H:i') ?></td>
                                <td class="small text-muted"><?= CoreHelper::e($p['receiver_name'] ?: 'Hệ thống') ?></td>
                                <td class="text-end">
                                    <?php if (CoreHelper::hasRole('admin')): ?>
                                        <form method="POST" action="<?= CoreHelper::url('/payments/delete') ?>" class="d-inline" onsubmit="return confirm('Hủy phiếu thu này và hoàn lại công nợ cho hóa đơn?');">
                                            <?= CoreHelper::csrfField() ?>
                                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Lập Phiếu thu mới -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= CoreHelper::url('/payments/store') ?>" class="modal-content">
            <?= CoreHelper::csrfField() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Ghi nhận Thu tiền Hóa đơn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Chọn Hóa đơn cần thu <span class="text-danger">*</span></label>
                    <select name="invoice_id" id="pay_invoice_id" class="form-select" required onchange="onPaymentInvoiceSelect(this)">
                        <option value="">-- Chọn hóa đơn chưa thu đủ --</option>
                        <?php foreach ($unpaidInvoices as $uInv): ?>
                            <option value="<?= (int) $uInv['id'] ?>" data-remaining="<?= (float) $uInv['remaining_amount'] ?>">
                                <?= CoreHelper::e($uInv['room_number']) ?> - <?= CoreHelper::e($uInv['invoice_code']) ?> (Còn nợ: <?= CoreHelper::formatCurrency($uInv['remaining_amount']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Số tiền thực thu (VNĐ) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" id="pay_amount" class="form-control fw-bold text-success" min="1000" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Phương thức</label>
                        <select name="payment_method" class="form-select">
                            <option value="bank_transfer">Chuyển khoản</option>
                            <option value="cash">Tiền mặt</option>
                            <option value="momo">Ví MoMo</option>
                            <option value="vnpay">VNPay</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Ngày thu</label>
                        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mã giao dịch ngân hàng (nếu có)</label>
                    <input type="text" name="transaction_ref" class="form-control" placeholder="VD: FT262938472">
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Ghi chú</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Ghi chú thu tiền..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-success">Lưu Phiếu thu</button>
            </div>
        </form>
    </div>
</div>

<script>
function onPaymentInvoiceSelect(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.dataset.remaining) {
        document.getElementById('pay_amount').value = opt.dataset.remaining;
    }
}
</script>
