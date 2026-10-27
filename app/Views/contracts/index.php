<?php
use App\Helpers\CoreHelper;

$nextMonthFirstDay = date('Y-m-01', strtotime('first day of next month'));
$nextYearEndDay = date('Y-m-01', strtotime($nextMonthFirstDay . ' +12 months'));
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="GET" action="<?= CoreHelper::url('/contracts') ?>" class="d-flex gap-2 flex-wrap">
        <input type="text" name="keyword" value="<?= CoreHelper::e($filters['keyword'] ?? '') ?>" class="form-control form-control-sm" style="width: 220px;" placeholder="Tìm mã HĐ, phòng, khách...">
        <select name="status" class="form-select form-select-sm" style="width: 155px;">
            <option value="">Tất cả trạng thái</option>
            <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Đang hiệu lực</option>
            <option value="terminated" <?= ($filters['status'] ?? '') === 'terminated' ? 'selected' : '' ?>>Đã thanh lý</option>
            <option value="expired" <?= ($filters['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Hết hạn</option>
        </select>
        <button type="submit" class="btn btn-sm btn-secondary"><i class="bi bi-search me-1"></i>Lọc</button>
    </form>

    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1.5" data-bs-toggle="modal" data-bs-target="#contractModal">
        <i class="bi bi-file-earmark-plus me-1"></i>Lập Hợp đồng mới
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Mã HĐ</th>
                        <th>Phòng</th>
                        <th>Khách thuê</th>
                        <th>Ngày cọc &amp; Hiệu lực</th>
                        <th>Giá thuê / Cọc</th>
                        <th>Hình thức ký</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contracts)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Chưa có hợp đồng nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($contracts as $c): ?>
                            <?php
                            $depStatus = $c['deposit_status'] ?? 'held';
                            $renStatus = $c['renewal_status'] ?? 'none';
                            $sigType = $c['signature_type'] ?? 'online';
                            $sigStatus = $c['signature_status'] ?? 'pending';
                            $depositDate = !empty($c['deposit_date']) ? $c['deposit_date'] : $c['start_date'];
                            ?>
                            <tr>
                                <td>
                                    <a href="<?= CoreHelper::url('/contracts/detail', ['id' => $c['id']]) ?>" class="fw-bold text-primary text-decoration-none">
                                        <?= CoreHelper::e($c['contract_code']) ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-dark"><?= CoreHelper::e($c['room_number']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= CoreHelper::e($c['tenant_name']) ?></div>
                                    <div class="text-muted"><?= CoreHelper::e($c['tenant_phone']) ?></div>
                                </td>
                                <td>
                                    <div class="text-muted">Cọc: <strong class="text-dark"><?= CoreHelper::formatDate($depositDate) ?></strong></div>
                                    <div>Hiệu lực: <strong><?= CoreHelper::formatDate($c['start_date']) ?></strong> &rarr; <strong><?= CoreHelper::formatDate($c['end_date']) ?></strong></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-danger"><?= CoreHelper::formatCurrency($c['monthly_rent']) ?></div>
                                    <div class="text-muted">
                                        Cọc: <?= CoreHelper::formatCurrency($c['deposit_amount']) ?>
                                        <?php if ($depStatus === 'refunded'): ?>
                                            <span class="badge bg-success-subtle text-success ms-1">Đã hoàn</span>
                                        <?php elseif ($depStatus === 'forfeited'): ?>
                                            <span class="badge bg-danger-subtle text-danger ms-1">Mất cọc</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($sigType === 'online'): ?>
                                        <span class="badge bg-primary-subtle text-primary"><i class="bi bi-vector-pen me-1"></i>Điện tử</span>
                                    <?php else: ?>
                                        <span class="badge bg-dark-subtle text-dark"><i class="bi bi-printer me-1"></i>Ký giấy</span>
                                    <?php endif; ?>

                                    <?php if (!empty($c['scan_file'])): ?>
                                        <a href="<?= CoreHelper::asset($c['scan_file']) ?>" target="_blank" class="badge bg-success text-decoration-none ms-1">
                                            <i class="bi bi-file-earmark-image me-1"></i>Bản Scan
                                        </a>
                                    <?php elseif ($sigStatus === 'signed'): ?>
                                        <span class="badge bg-success-subtle text-success ms-1">Đã ký</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis ms-1">Chờ ký</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($c['status'] === 'active'): ?>
                                        <span class="badge bg-success">Hiệu lực</span>
                                    <?php elseif ($c['status'] === 'terminated'): ?>
                                        <span class="badge bg-secondary">Đã thanh lý</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Hết hạn</span>
                                    <?php endif; ?>

                                    <?php if ($renStatus === 'renewed'): ?>
                                        <span class="badge bg-info-subtle text-info ms-1">Đã gia hạn</span>
                                    <?php elseif ($renStatus === 'declined' || $renStatus === 'auto_no_renew'): ?>
                                        <span class="badge bg-secondary-subtle text-secondary ms-1">K.Gia hạn</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= CoreHelper::url('/contracts/detail', ['id' => $c['id']]) ?>" class="btn btn-sm btn-primary py-0 px-2" title="Xem điều khoản, Ký online, In hoặc tải bản Scan">
                                            <i class="bi bi-file-earmark-text me-1"></i>Bản HĐ / Ký
                                        </a>
                                        <?php if ($c['status'] === 'active'): ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-warning text-dark py-0 px-2"
                                                    onclick="openTerminateModal(<?= (int) $c['id'] ?>, '<?= CoreHelper::e($c['contract_code']) ?>', '<?= CoreHelper::e($c['room_number']) ?>', '<?= CoreHelper::e($c['end_date']) ?>', <?= (float) $c['deposit_amount'] ?>)">
                                                Thanh lý
                                            </button>
                                        <?php endif; ?>
                                        <?php if (CoreHelper::hasRole('admin')): ?>
                                            <form method="POST" action="<?= CoreHelper::url('/contracts/delete') ?>" class="d-inline" onsubmit="return confirm('Xóa hợp đồng này?');">
                                                <?= CoreHelper::csrfField() ?>
                                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1.5"><i class="bi bi-trash"></i></button>
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

<!-- Modal Thanh lý Hợp đồng -->
<div class="modal fade" id="terminateContractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= CoreHelper::url('/contracts/terminate') ?>" class="modal-content">
            <?= CoreHelper::csrfField() ?>
            <input type="hidden" name="id" id="term_contract_id">
            <input type="hidden" id="term_end_date_raw">
            <input type="hidden" id="term_deposit_raw">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Thanh lý Hợp đồng — <span id="term_contract_code" class="text-primary"></span> (P.<span id="term_room_number"></span>)</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between small p-2 rounded bg-light border mb-3">
                    <span>Hạn HĐ: <strong id="term_end_date_display"></strong></span>
                    <span>Tiền cọc: <strong id="term_deposit_display" class="text-danger"></strong></span>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Ngày khách rời đi *</label>
                    <input type="date" name="move_out_date" id="term_move_out_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required onchange="previewDepositSettlement()">
                </div>

                <div id="term_settlement_alert" class="alert alert-warning py-2 px-3 small mb-3"></div>

                <div class="mb-1">
                    <label class="form-label small fw-semibold">Ghi chú</label>
                    <input type="text" name="termination_reason" class="form-control form-control-sm" placeholder="Bàn giao phòng, chốt điện nước...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-sm btn-warning fw-bold">Xác nhận Thanh lý</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Lập Hợp đồng mới -->
<div class="modal fade" id="contractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= CoreHelper::url('/contracts/store') ?>" class="modal-content">
            <?= CoreHelper::csrfField() ?>
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Lập Hợp đồng Thuê phòng mới</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2.5">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Phòng trọ <span class="text-danger">*</span></label>
                        <select name="room_id" id="contract_room_id" class="form-select form-select-sm" required onchange="onContractRoomSelect(this)">
                            <option value="">-- Chọn phòng trống --</option>
                            <?php foreach ($rooms as $r): ?>
                                <option value="<?= (int) $r['id'] ?>" data-price="<?= (float) $r['price'] ?>" <?= $r['status'] !== 'available' ? 'disabled' : '' ?>>
                                    <?= CoreHelper::e($r['room_number']) ?> - <?= CoreHelper::formatCurrency($r['price']) ?> <?= $r['status'] !== 'available' ? '(Đã thuê)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Khách thuê <span class="text-danger">*</span></label>
                        <select name="tenant_id" class="form-select form-select-sm" required>
                            <option value="">-- Chọn khách thuê --</option>
                            <?php foreach ($tenants as $t): ?>
                                <option value="<?= (int) $t['id'] ?>">
                                    <?= CoreHelper::e($t['full_name']) ?> - <?= CoreHelper::e($t['phone']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Ngày đặt cọc <span class="text-danger">*</span></label>
                        <input type="date" name="deposit_date" id="contract_deposit_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required onchange="onDepositDateChange()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Hiệu lực từ (Mùng 1) <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="contract_start_date" class="form-control form-control-sm fw-bold text-primary" value="<?= $nextMonthFirstDay ?>" required onchange="syncContractEndDate()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Thời hạn thuê</label>
                        <select name="duration_months" id="contract_duration_months" class="form-select form-select-sm" onchange="syncContractEndDate()">
                            <option value="6">6 tháng (từ mùng 1)</option>
                            <option value="12" selected>12 tháng (từ mùng 1)</option>
                            <option value="24">24 tháng (từ mùng 1)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Hết hạn (Tính từ mùng 1) *</label>
                        <input type="date" name="end_date" id="contract_end_date" class="form-control form-control-sm bg-light fw-bold text-danger" value="<?= $nextYearEndDay ?>" readonly required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Giá thuê/tháng (VNĐ) *</label>
                        <input type="number" step="50000" name="monthly_rent" id="contract_monthly_rent" class="form-control form-control-sm" required oninput="syncDepositAndPreview()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Tiền cọc (= 1 tháng)</label>
                        <input type="number" name="deposit_amount" id="contract_deposit" class="form-control form-control-sm bg-light fw-bold text-primary" value="0" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Hình thức ký hợp đồng</label>
                        <select name="signature_type" class="form-select form-select-sm">
                            <option value="online">Hợp đồng Điện tử (Ký Online)</option>
                            <option value="paper">Hợp đồng Ký giấy (In &amp; Lưu Scan)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Giá điện (đ/kWh)</label>
                        <input type="number" name="electricity_rate" class="form-control form-control-sm" value="3500">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Giá nước (đ/m&sup3;)</label>
                        <input type="number" name="water_rate" class="form-control form-control-sm" value="15000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Phí dịch vụ (đ/tháng)</label>
                        <input type="number" name="service_fee" id="contract_service_fee" class="form-control form-control-sm" value="150000" oninput="previewMoveInCalculation()">
                    </div>

                    <div class="col-12">
                        <div id="move_in_preview_box" class="p-2 rounded bg-primary-subtle text-dark small">
                            Chọn phòng để tự động tính Tiền cọc 1 tháng và Hóa đơn đầu kỳ.
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1">Ghi chú thêm</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Ghi chú thỏa thuận riêng (nếu có)...">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-sm btn-primary px-3">Tạo &amp; Mở Bản Hợp đồng để Ký</button>
            </div>
        </form>
    </div>
</div>

<script>
function formatVnd(n) {
    return new Intl.NumberFormat('vi-VN').format(Math.round(n || 0)) + ' đ';
}

function onContractRoomSelect(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    if (opt && opt.dataset.price) {
        document.getElementById('contract_monthly_rent').value = opt.dataset.price;
        syncDepositAndPreview();
    }
}

function syncDepositAndPreview() {
    const rent = parseFloat(document.getElementById('contract_monthly_rent').value || 0);
    document.getElementById('contract_deposit').value = rent;
    previewMoveInCalculation();
}

function onDepositDateChange() {
    const depVal = document.getElementById('contract_deposit_date').value;
    if (!depVal) return;
    const parts = depVal.split('-');
    let y = parseInt(parts[0], 10);
    let m = parseInt(parts[1], 10) + 1;
    if (m > 12) {
        m = 1;
        y += 1;
    }
    const nextFirst = `${y}-${String(m).padStart(2, '0')}-01`;
    document.getElementById('contract_start_date').value = nextFirst;
    syncContractEndDate();
}

function syncContractEndDate() {
    const startVal = document.getElementById('contract_start_date').value;
    const months = parseInt(document.getElementById('contract_duration_months')?.value || '12', 10);
    if (!startVal) return;

    const parts = startVal.split('-');
    let y = parseInt(parts[0], 10);
    let m = parseInt(parts[1], 10);

    // Chuẩn hóa start_date luôn là mùng 01
    const normalizedStart = `${y}-${String(m).padStart(2, '0')}-01`;
    document.getElementById('contract_start_date').value = normalizedStart;

    const totalMonths = (y * 12 + (m - 1)) + months;
    const endY = Math.floor(totalMonths / 12);
    const endM = (totalMonths % 12) + 1;
    document.getElementById('contract_end_date').value = `${endY}-${String(endM).padStart(2, '0')}-01`;
    previewMoveInCalculation();
}

function previewMoveInCalculation() {
    const rent = parseFloat(document.getElementById('contract_monthly_rent').value || 0);
    const service = parseFloat(document.getElementById('contract_service_fee').value || 0);
    const startDate = document.getElementById('contract_start_date').value;
    const box = document.getElementById('move_in_preview_box');
    if (!startDate || rent <= 0) return;

    const day = parseInt(startDate.split('-')[2], 10);
    const isAfter15 = day > 15;
    const roomFee = isAfter15 ? (rent / 2) : rent;
    const deposit = rent;
    const total = roomFee + service + deposit;

    box.innerHTML = `
        <strong>Dự tính Hóa đơn đầu kỳ:</strong> Tiền phòng (${isAfter15 ? '50%' : '100%'}): <strong>${formatVnd(roomFee)}</strong>
        + Dịch vụ: <strong>${formatVnd(service)}</strong> + Cọc 1 tháng: <strong>${formatVnd(deposit)}</strong>
        = <strong class="text-danger">${formatVnd(total)}</strong>
    `;
}

function openTerminateModal(id, code, room, endDate, deposit) {
    document.getElementById('term_contract_id').value = id;
    document.getElementById('term_contract_code').textContent = code;
    document.getElementById('term_room_number').textContent = room;
    document.getElementById('term_end_date_raw').value = endDate;
    document.getElementById('term_end_date_display').textContent = endDate.split('-').reverse().join('/');
    document.getElementById('term_deposit_raw').value = deposit;
    document.getElementById('term_deposit_display').textContent = formatVnd(deposit);
    previewDepositSettlement();
    new bootstrap.Modal(document.getElementById('terminateContractModal')).show();
}

function previewDepositSettlement() {
    const moveOut = document.getElementById('term_move_out_date').value;
    const endDate = document.getElementById('term_end_date_raw').value;
    const deposit = parseFloat(document.getElementById('term_deposit_raw').value || 0);
    const alertBox = document.getElementById('term_settlement_alert');

    if (moveOut >= endDate) {
        alertBox.className = 'alert alert-success py-2 px-3 small mb-3';
        alertBox.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i>Rời đi đúng/sau hạn HĐ: <strong>Hoàn trả 100% cọc (${formatVnd(deposit)})</strong>.`;
    } else {
        alertBox.className = 'alert alert-danger py-2 px-3 small mb-3';
        alertBox.innerHTML = `<i class="bi bi-x-circle-fill me-1"></i>Rời đi trước hạn HĐ: <strong>Khách mất cọc (${formatVnd(deposit)})</strong>.`;
    }
}
</script>
