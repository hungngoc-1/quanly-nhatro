<?php

use App\Helpers\CoreHelper;

?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <form method="GET" action="<?= CoreHelper::url('/contracts') ?>" class="d-flex gap-2 flex-wrap">
        <input type="text" name="keyword" value="<?= CoreHelper::e($filters['keyword'] ?? '') ?>" class="form-control form-control-sm" style="width: 240px;" placeholder="Mã HĐ, mã phòng, tên khách...">
        <select name="status" class="form-select form-select-sm" style="width: 170px;">
            <option value="">-- Tất cả trạng thái --</option>
            <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Đang hiệu lực</option>
            <option value="terminated" <?= ($filters['status'] ?? '') === 'terminated' ? 'selected' : '' ?>>Đã thanh lý</option>
            <option value="expired" <?= ($filters['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Hết hạn</option>
        </select>
        <button type="submit" class="btn btn-sm btn-secondary"><i class="bi bi-search me-1"></i>Lọc</button>
    </form>

    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#contractModal">
        <i class="bi bi-file-earmark-plus me-1"></i> Lập Hợp đồng mới
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã Hợp đồng</th>
                        <th>Phòng</th>
                        <th>Khách thuê</th>
                        <th>Thời hạn thuê</th>
                        <th>Giá thuê / Tiền cọc</th>
                        <th>Đơn giá Điện / Nước / DV</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contracts)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Chưa có hợp đồng nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($contracts as $c): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?= CoreHelper::e($c['contract_code']) ?></td>
                                <td><span class="badge bg-dark fs-6"><?= CoreHelper::e($c['room_number']) ?></span></td>
                                <td>
                                    <div class="fw-semibold"><?= CoreHelper::e($c['tenant_name']) ?></div>
                                    <small class="text-muted"><?= CoreHelper::e($c['tenant_phone']) ?></small>
                                </td>
                                <td class="small">
                                    <div>Từ: <strong><?= CoreHelper::formatDate($c['start_date']) ?></strong></div>
                                    <div>Đến: <strong><?= CoreHelper::formatDate($c['end_date']) ?></strong></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-danger"><?= CoreHelper::formatCurrency($c['monthly_rent']) ?>/tháng</div>
                                    <small class="text-muted">Cọc: <?= CoreHelper::formatCurrency($c['deposit_amount']) ?></small>
                                </td>
                                <td class="small text-muted">
                                    <div>Điện: <?= CoreHelper::formatCurrency($c['electricity_rate']) ?>/kWh</div>
                                    <div>Nước: <?= CoreHelper::formatCurrency($c['water_rate']) ?>/m&sup3;</div>
                                    <div>Dịch vụ: <?= CoreHelper::formatCurrency($c['service_fee']) ?></div>
                                </td>
                                <td>
                                    <?php if ($c['status'] === 'active'): ?>
                                        <span class="badge bg-success">Đang hiệu lực</span>
                                    <?php elseif ($c['status'] === 'terminated'): ?>
                                        <span class="badge bg-secondary">Đã thanh lý</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Hết hạn</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <?php if ($c['status'] === 'active'): ?>
                                            <form method="POST" action="<?= CoreHelper::url('/contracts/terminate') ?>" onsubmit="return confirm('Xác nhận thanh lý hợp đồng <?= CoreHelper::e($c['contract_code']) ?> và trả phòng <?= CoreHelper::e($c['room_number']) ?> về trạng thái trống?');">
                                                <?= CoreHelper::csrfField() ?>
                                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning text-dark" title="Thanh lý hợp đồng">
                                                    <i class="bi bi-box-arrow-right"></i> Thanh lý
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (CoreHelper::hasRole('admin')): ?>
                                            <form method="POST" action="<?= CoreHelper::url('/contracts/delete') ?>" onsubmit="return confirm('Xóa hợp đồng này?');">
                                                <?= CoreHelper::csrfField() ?>
                                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
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

<!-- Modal Lập Hợp đồng mới -->
<div class="modal fade" id="contractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= CoreHelper::url('/contracts/store') ?>" class="modal-content">
            <?= CoreHelper::csrfField() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Lập Hợp đồng Thuê phòng mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Chọn Phòng trọ <span class="text-danger">*</span></label>
                        <select name="room_id" id="contract_room_id" class="form-select" required onchange="onContractRoomSelect(this)">
                            <option value="">-- Chọn phòng --</option>
                            <?php foreach ($rooms as $r): ?>
                                <option value="<?= (int) $r['id'] ?>" data-price="<?= (float) $r['price'] ?>" <?= $r['status'] !== 'available' ? 'disabled' : '' ?>>
                                    <?= CoreHelper::e($r['room_number']) ?> - <?= CoreHelper::formatCurrency($r['price']) ?> (<?= $r['status'] === 'available' ? 'Đang trống' : 'Đã thuê/Bảo trì' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Chọn Khách thuê <span class="text-danger">*</span></label>
                        <select name="tenant_id" class="form-select" required>
                            <option value="">-- Chọn khách thuê --</option>
                            <?php foreach ($tenants as $t): ?>
                                <option value="<?= (int) $t['id'] ?>">
                                    <?= CoreHelper::e($t['full_name']) ?> - SĐT: <?= CoreHelper::e($t['phone']) ?> (CCCD: <?= CoreHelper::e($t['cccd']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ngày bắt đầu <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ngày kết thúc <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giá thuê hàng tháng (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" step="50000" name="monthly_rent" id="contract_monthly_rent" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tiền đặt cọc (VNĐ)</label>
                        <input type="number" step="50000" name="deposit_amount" id="contract_deposit" class="form-control" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Giá điện (VNĐ/kWh)</label>
                        <input type="number" name="electricity_rate" class="form-control" value="3500">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Giá nước (VNĐ/m&sup3;)</label>
                        <input type="number" name="water_rate" class="form-control" value="15000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Phí dịch vụ chung (VNĐ)</label>
                        <input type="number" name="service_fee" class="form-control" value="150000">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Điều khoản / Ghi chú thêm</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Thanh toán từ ngày 01 đến 05 hàng tháng..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary">Ký &amp; Lưu Hợp đồng</button>
            </div>
        </form>
    </div>
</div>

<script>
function onContractRoomSelect(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    if (opt && opt.dataset.price) {
        document.getElementById('contract_monthly_rent').value = opt.dataset.price;
        document.getElementById('contract_deposit').value = opt.dataset.price;
    }
}
</script>
