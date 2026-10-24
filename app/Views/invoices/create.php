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
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Phí dịch vụ (VNĐ)</label>
                            <input type="number" name="service_fee" id="inv_service_fee" class="form-control form-control-sm" value="<?= (float) ($prefillUtility['other_fee'] ?? 150000) ?>" oninput="previewInvoiceTotal()">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Giảm giá (VNĐ)</label>
                            <input type="number" name="discount" id="inv_discount" class="form-control form-control-sm" value="0" oninput="previewInvoiceTotal()">
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

    <!-- Cột phải: Danh sách Hóa đơn -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul text-primary me-2"></i>Danh sách Hóa đơn đã phát hành</h6>
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
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">Không có hóa đơn nào.</td></tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr>
                                        <td class="fw-bold"><?= CoreHelper::e($inv['invoice_code']) ?></td>
                                        <td>
                                            <span class="badge bg-dark"><?= CoreHelper::e($inv['room_number']) ?></span>
                                            <div class="text-muted"><?= CoreHelper::e($inv['tenant_name']) ?></div>
                                        </td>
                                        <td>T<?= (int) $inv['month'] ?>/<?= (int) $inv['year'] ?></td>
                                        <td class="fw-bold"><?= CoreHelper::formatCurrency($inv['total_amount']) ?></td>
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
    </div>
</div>

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
