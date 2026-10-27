<?php

use App\Helpers\CoreHelper;

?>
<div class="row g-4">
    <!-- Cột trái: Form Lập hóa đơn mới & Lập hàng loạt -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-primary text-white fw-bold py-3">
                <i class="bi bi-file-earmark-plus me-2"></i>Lập Hóa đơn Tiền trọ
            </div>
            <div class="card-body">
                <form method="POST" action="<?= CoreHelper::url('/invoices/store') ?>">
                    <?= CoreHelper::csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Chọn Hợp đồng / Phòng <span class="text-danger">*</span></label>
                        <select name="contract_id" id="inv_contract_id" class="form-select" required onchange="onInvoiceContractChange(this)">
                            <option value="">-- Chọn phòng đang thuê --</option>
                            <?php foreach ($activeContracts as $ac): ?>
                                <?php
                                $selected = (!empty($prefillUtility) && (int) $prefillUtility['room_id'] === (int) $ac['room_id']) ? 'selected' : '';
                                ?>
                                <option value="<?= (int) $ac['id'] ?>"
                                        data-room-id="<?= (int) $ac['room_id'] ?>"
                                        data-rent="<?= (float) $ac['monthly_rent'] ?>"
                                        data-service="<?= (float) $ac['service_fee'] ?>"
                                        <?= $selected ?>>
                                    <?= CoreHelper::e($ac['room_number']) ?> - <?= CoreHelper::e($ac['tenant_name']) ?> (<?= CoreHelper::formatCurrency($ac['monthly_rent']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Tháng</label>
                            <input type="number" name="month" id="inv_month" class="form-control" value="<?= (int) ($prefillUtility['month'] ?? date('n')) ?>" min="1" max="12" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Năm</label>
                            <input type="number" name="year" id="inv_year" class="form-control" value="<?= (int) ($prefillUtility['year'] ?? date('Y')) ?>" min="2020" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Loại hóa đơn</label>
                        <select name="invoice_type" id="inv_type" class="form-select form-select-sm" onchange="onInvoiceTypeChange()">
                            <option value="monthly">Hóa đơn Tiền phòng hàng tháng</option>
                            <option value="move_in">Hóa đơn Vào ở &amp; Thu tiền cọc (1 tháng)</option>
                            <option value="move_out">Hóa đơn Rời đi / Quyết toán tiền cọc</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Tiền thuê phòng (VNĐ)</label>
                        <input type="number" name="room_fee" id="inv_room_fee" class="form-control form-control-sm" value="0" oninput="previewInvoiceTotal()">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Tiền điện (VNĐ)</label>
                            <input type="number" name="electric_fee" id="inv_electric_fee" class="form-control form-control-sm" value="<?= (float) ($prefillUtility['electric_amount'] ?? 0) ?>" oninput="previewInvoiceTotal()">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Tiền nước (VNĐ)</label>
                            <input type="number" name="water_fee" id="inv_water_fee" class="form-control form-control-sm" value="<?= (float) ($prefillUtility['water_amount'] ?? 0) ?>" oninput="previewInvoiceTotal()">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Phí dịch vụ (VNĐ)</label>
                            <input type="number" name="service_fee" id="inv_service_fee" class="form-control form-control-sm" value="<?= (float) ($prefillUtility['other_fee'] ?? 150000) ?>" oninput="previewInvoiceTotal()">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Giảm giá (VNĐ)</label>
                            <input type="number" name="discount" id="inv_discount" class="form-control form-control-sm" value="0" oninput="previewInvoiceTotal()">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1 text-primary">Thu tiền cọc (VNĐ)</label>
                            <input type="number" name="deposit_fee" id="inv_deposit_fee" class="form-control form-control-sm" value="0" oninput="previewInvoiceTotal()">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1 text-success">Hoàn trả cọc (VNĐ)</label>
                            <input type="number" name="deposit_refund" id="inv_deposit_refund" class="form-control form-control-sm" value="0" oninput="previewInvoiceTotal()">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-1">Hạn thanh toán</label>
                        <input type="date" name="due_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+5 days')) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-1">Ghi chú</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Ghi chú trên phiếu báo tiền...">
                    </div>

                    <div class="bg-light border rounded-3 p-2 mb-3 d-flex justify-content-between align-items-center">
                        <span class="small fw-bold">Tổng cộng hóa đơn:</span>
                        <span class="fs-5 fw-bold text-danger" id="invoicePreviewTotal">0 đ</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Lập Hóa đơn
                    </button>
                </form>
            </div>
        </div>

        <!-- Tạo hóa đơn tự động hàng loạt -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white fw-bold py-2 small">
                <i class="bi bi-magic me-1"></i> Phát hành Hóa đơn Hàng loạt
            </div>
            <div class="card-body">
                <form method="POST" action="<?= CoreHelper::url('/invoices/generate-batch') ?>" onsubmit="return confirm('Phát hành hóa đơn tự động cho tất cả các phòng đang thuê?');">
                    <?= CoreHelper::csrfField() ?>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <input type="number" name="month" class="form-control form-control-sm" value="<?= (int) date('n') ?>" min="1" max="12" placeholder="Tháng">
                        </div>
                        <div class="col-6">
                            <input type="number" name="year" class="form-control form-control-sm" value="<?= (int) date('Y') ?>" placeholder="Năm">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-dark w-100">
                        <i class="bi bi-lightning-fill text-warning me-1"></i> Tạo tự động cho tất cả phòng
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Cột phải: Bảng Hóa đơn & Thu tiền tích hợp -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Danh sách Hóa đơn &amp; Thu tiền</h6>
                    <?php if (!empty($payments)): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2.5" style="font-size: 0.72rem;" data-bs-toggle="collapse" data-bs-target="#paymentHistoryCollapse">
                            <i class="bi bi-clock-history me-1"></i>Lịch sử phiếu thu (<?= count($payments) ?>)
                        </button>
                    <?php endif; ?>
                </div>
                <form method="GET" action="<?= CoreHelper::url('/invoices/create') ?>" class="d-flex gap-2 flex-wrap">
                    <select name="month" class="form-select form-select-sm" style="width: 110px;">
                        <option value="">Tất cả tháng</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= ((string) ($filters['month'] ?? '') === (string) $m) ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="status" class="form-select form-select-sm" style="width: 140px;">
                        <option value="">Mọi trạng thái</option>
                        <option value="unpaid" <?= ($filters['status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>Chưa đóng</option>
                        <option value="partial" <?= ($filters['status'] ?? '') === 'partial' ? 'selected' : '' ?>>Đóng 1 phần</option>
                        <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                        <option value="overdue" <?= ($filters['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Quá hạn</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-secondary">Lọc</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Mã HĐ</th>
                                <th>Phòng / Khách</th>
                                <th>Kỳ</th>
                                <th>Tổng cộng</th>
                                <th>Đã đóng</th>
                                <th>Còn nợ</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác &amp; Thu tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">Không có hóa đơn nào.</td></tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= CoreHelper::e($inv['invoice_code']) ?></div>
                                            <?php $iType = $inv['invoice_type'] ?? 'monthly'; ?>
                                            <?php if ($iType === 'move_in'): ?>
                                                <span class="badge bg-info-subtle text-info">Vào ở &amp; Đặt cọc</span>
                                            <?php elseif ($iType === 'move_out'): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis">Rời đi / Trả phòng</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-secondary border">Hàng tháng</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark"><?= CoreHelper::e($inv['room_number']) ?></span>
                                            <div class="text-muted"><?= CoreHelper::e($inv['tenant_name']) ?></div>
                                        </td>
                                        <td>T<?= (int) $inv['month'] ?>/<?= (int) $inv['year'] ?></td>
                                        <td>
                                            <div class="fw-bold"><?= CoreHelper::formatCurrency($inv['total_amount']) ?></div>
                                            <?php if ((float) ($inv['deposit_fee'] ?? 0) > 0): ?>
                                                <div class="small text-primary">+Cọc: <?= CoreHelper::formatCurrency($inv['deposit_fee']) ?></div>
                                            <?php endif; ?>
                                            <?php if ((float) ($inv['deposit_refund'] ?? 0) > 0): ?>
                                                <div class="small text-success">-Hoàn cọc: <?= CoreHelper::formatCurrency($inv['deposit_refund']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-success"><?= CoreHelper::formatCurrency($inv['paid_amount']) ?></td>
                                        <td class="fw-bold text-danger"><?= CoreHelper::formatCurrency($inv['remaining_amount']) ?></td>
                                        <td>
                                            <?php if ($inv['status'] === 'paid'): ?>
                                                <span class="badge bg-success">Đã thu đủ</span>
                                            <?php elseif ($inv['status'] === 'partial'): ?>
                                                <span class="badge bg-warning text-dark">Đóng 1 phần</span>
                                            <?php elseif ($inv['status'] === 'overdue'): ?>
                                                <span class="badge bg-danger">Quá hạn</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Chưa đóng</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <?php if ((float) $inv['remaining_amount'] > 0): ?>
                                                    <button type="button"
                                                            class="btn btn-sm btn-success"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#quickPayModal"
                                                            onclick="openQuickPayModal(<?= (int) $inv['id'] ?>, '<?= CoreHelper::e($inv['invoice_code']) ?>', '<?= CoreHelper::e($inv['room_number']) ?>', '<?= CoreHelper::e($inv['tenant_name']) ?>', <?= (float) $inv['remaining_amount'] ?>)"
                                                            title="Ghi nhận thu tiền ngay cho hóa đơn này">
                                                        <i class="bi bi-cash-coin me-1"></i>Thu tiền
                                                    </button>
                                                <?php endif; ?>
                                                <a href="<?= CoreHelper::url('/invoices/detail', ['id' => $inv['id']]) ?>" class="btn btn-sm btn-outline-primary" title="Xem & In hóa đơn">
                                                    <i class="bi bi-printer"></i> Chi tiết
                                                </a>
                                                <?php if (CoreHelper::hasRole('admin')): ?>
                                                    <form method="POST" action="<?= CoreHelper::url('/invoices/delete') ?>" onsubmit="return confirm('Xóa hóa đơn <?= CoreHelper::e($inv['invoice_code']) ?>?');">
                                                        <?= CoreHelper::csrfField() ?>
                                                        <input type="hidden" name="id" value="<?= (int) $inv['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Bảng Lịch sử Phiếu thu tích hợp ngay bên dưới (Mở ra khi cần đối soát) -->
        <?php if (!empty($payments)): ?>
            <?php
            $methodLabels = [
                'bank_transfer' => ['text' => 'Chuyển khoản', 'class' => 'bg-primary'],
                'cash'          => ['text' => 'Tiền mặt', 'class' => 'bg-success'],
                'momo'          => ['text' => 'Ví MoMo', 'class' => 'bg-danger'],
                'vnpay'         => ['text' => 'VNPay', 'class' => 'bg-info text-dark'],
            ];
            ?>
            <div class="collapse" id="paymentHistoryCollapse">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-2-5 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-success"><i class="bi bi-check2-all me-2"></i>Lịch sử Phiếu thu đã ghi nhận</h6>
                        <span class="small text-muted">Tự động cập nhật vào công nợ Hóa đơn</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã Phiếu</th>
                                        <th>Hóa đơn</th>
                                        <th>Phòng / Khách</th>
                                        <th>Số tiền thu</th>
                                        <th>Hình thức</th>
                                        <th>Thời gian</th>
                                        <th class="text-end">Hủy</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($payments, 0, 15) as $p): ?>
                                        <?php $mBadge = $methodLabels[$p['payment_method']] ?? ['text' => $p['payment_method'], 'class' => 'bg-secondary']; ?>
                                        <tr>
                                            <td class="fw-bold"><?= CoreHelper::e($p['payment_code']) ?></td>
                                            <td><?= CoreHelper::e($p['invoice_code']) ?></td>
                                            <td><span class="badge bg-dark"><?= CoreHelper::e($p['room_number']) ?></span> <?= CoreHelper::e($p['tenant_name']) ?></td>
                                            <td class="fw-bold text-success">+ <?= CoreHelper::formatCurrency($p['amount']) ?></td>
                                            <td><span class="badge <?= $mBadge['class'] ?>"><?= $mBadge['text'] ?></span></td>
                                            <td class="text-muted"><?= CoreHelper::formatDate($p['payment_date'], 'd/m/Y H:i') ?></td>
                                            <td class="text-end">
                                                <?php if (CoreHelper::hasRole('admin')): ?>
                                                    <form method="POST" action="<?= CoreHelper::url('/payments/delete') ?>" class="d-inline" onsubmit="return confirm('Hủy phiếu thu này và hoàn lại công nợ cho hóa đơn?');">
                                                        <?= CoreHelper::csrfField() ?>
                                                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1.5"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Thu Tiền Nhanh tích hợp trực tiếp trong bảng Hóa đơn -->
<div class="modal fade" id="quickPayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= CoreHelper::url('/payments/store') ?>" class="modal-content">
            <?= CoreHelper::csrfField() ?>
            <input type="hidden" name="invoice_id" id="qp_invoice_id">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-cash-coin text-success me-2"></i>Thu tiền Hóa đơn <span id="qp_invoice_code" class="text-primary"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-2.5 mb-3 rounded-3 bg-light border d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted">Phòng / Khách thuê</div>
                        <div class="fw-bold text-dark" id="qp_room_tenant">—</div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">Còn phải thu</div>
                        <div class="fw-bold text-danger fs-6" id="qp_remaining_display">0 đ</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Số tiền thực thu (VNĐ) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" id="qp_amount" class="form-control fw-bold text-success" min="1000" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Hình thức thanh toán</label>
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
                <div class="mb-2">
                    <label class="form-label fw-semibold">Mã giao dịch / Ghi chú</label>
                    <input type="text" name="notes" class="form-control" placeholder="VD: Đã nhận chuyển khoản đủ...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-sm btn-success px-3">
                    <i class="bi bi-check2-circle me-1"></i>Xác nhận Đã thu tiền
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openQuickPayModal(id, code, room, tenant, remaining) {
    document.getElementById('qp_invoice_id').value = id;
    document.getElementById('qp_invoice_code').textContent = code;
    document.getElementById('qp_room_tenant').textContent = 'Phòng ' + room + ' - ' + tenant;
    document.getElementById('qp_remaining_display').textContent = new Intl.NumberFormat('vi-VN').format(remaining) + ' đ';
    document.getElementById('qp_amount').value = remaining;
}

function onInvoiceTypeChange() {
    const sel = document.getElementById('inv_contract_id');
    const opt = sel && sel.selectedIndex > 0 ? sel.options[sel.selectedIndex] : null;
    const rent = opt ? parseFloat(opt.dataset.rent || 0) : parseFloat(document.getElementById('inv_room_fee').value || 0);
    const iType = document.getElementById('inv_type').value;

    if (iType === 'move_in') {
        document.getElementById('inv_deposit_fee').value = rent;
        document.getElementById('inv_deposit_refund').value = 0;
    } else if (iType === 'move_out') {
        document.getElementById('inv_deposit_fee').value = 0;
        document.getElementById('inv_deposit_refund').value = rent;
    } else {
        document.getElementById('inv_deposit_fee').value = 0;
        document.getElementById('inv_deposit_refund').value = 0;
    }
    previewInvoiceTotal();
}
</script>

<?php if (!empty($prefillUtility)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('inv_contract_id');
    if (sel && sel.selectedIndex > 0) {
        const opt = sel.options[sel.selectedIndex];
        document.getElementById('inv_room_fee').value = opt.dataset.rent || 0;
        previewInvoiceTotal();
    }
});
</script>
<?php endif; ?>
