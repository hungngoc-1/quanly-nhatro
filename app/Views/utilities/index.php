<?php

use App\Helpers\CoreHelper;

?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <form method="GET" action="<?= CoreHelper::url('/utilities') ?>" class="d-flex gap-2 align-items-center flex-wrap">
        <label class="small fw-semibold text-muted">Kỳ chốt sổ:</label>
        <select name="month" class="form-select form-select-sm" style="width: 120px;">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= (int) ($filters['month'] ?? date('n')) === $m ? 'selected' : '' ?>>Tháng <?= $m ?></option>
            <?php endfor; ?>
        </select>
        <select name="year" class="form-select form-select-sm" style="width: 110px;">
            <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= (int) ($filters['year'] ?? date('Y')) === $y ? 'selected' : '' ?>>Năm <?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-secondary"><i class="bi bi-funnel me-1"></i>Xem kỳ</button>
    </form>

    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#utilityModal">
        <i class="bi bi-lightning-charge-fill me-1"></i> Ghi chỉ số Điện Nước
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Phòng / Khách</th>
                        <th>Kỳ</th>
                        <th>Chỉ số Điện (kWh)</th>
                        <th>Thành tiền Điện</th>
                        <th>Chỉ số Nước (m&sup3;)</th>
                        <th>Thành tiền Nước</th>
                        <th>Phí DV</th>
                        <th>Hóa đơn</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($utilities)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">Chưa có bản ghi chỉ số điện nước trong kỳ này.</td></tr>
                    <?php else: ?>
                        <?php foreach ($utilities as $u): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-dark"><?= CoreHelper::e($u['room_number']) ?></span>
                                    <div class="small text-muted"><?= CoreHelper::e($u['tenant_name'] ?: '-') ?></div>
                                </td>
                                <td class="fw-semibold">T<?= (int) $u['month'] ?>/<?= (int) $u['year'] ?></td>
                                <td class="small">
                                    <div>Cũ: <strong><?= (int) $u['old_electric'] ?></strong> &rarr; Mới: <strong><?= (int) $u['new_electric'] ?></strong></div>
                                    <span class="badge bg-warning-subtle text-warning-emphasis">Tiêu thụ: <?= (int) $u['electric_used'] ?> kWh</span>
                                </td>
                                <td class="fw-semibold text-danger"><?= CoreHelper::formatCurrency($u['electric_amount']) ?></td>
                                <td class="small">
                                    <div>Cũ: <strong><?= (int) $u['old_water'] ?></strong> &rarr; Mới: <strong><?= (int) $u['new_water'] ?></strong></div>
                                    <span class="badge bg-info-subtle text-info-emphasis">Tiêu thụ: <?= (int) $u['water_used'] ?> m&sup3;</span>
                                </td>
                                <td class="fw-semibold text-primary"><?= CoreHelper::formatCurrency($u['water_amount']) ?></td>
                                <td><?= CoreHelper::formatCurrency($u['other_fee']) ?></td>
                                <td>
                                    <?php if (!empty($u['invoice_id'])): ?>
                                        <a href="<?= CoreHelper::url('/invoices/detail', ['id' => $u['invoice_id']]) ?>" class="badge bg-success text-decoration-none">
                                            <i class="bi bi-check-circle me-1"></i><?= CoreHelper::e($u['invoice_code']) ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= CoreHelper::url('/invoices/create', ['utility_id' => $u['id']]) ?>" class="btn btn-xs btn-outline-primary py-0 px-2 small">
                                            + Tạo hóa đơn
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if (CoreHelper::hasRole('admin')): ?>
                                        <form method="POST" action="<?= CoreHelper::url('/utilities/delete') ?>" class="d-inline" onsubmit="return confirm('Xóa chỉ số điện nước này?');">
                                            <?= CoreHelper::csrfField() ?>
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
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

<!-- Modal Chốt chỉ số Điện Nước -->
<div class="modal fade" id="utilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= CoreHelper::url('/utilities/store') ?>" class="modal-content">
            <?= CoreHelper::csrfField() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Chốt chỉ số Điện &amp; Nước</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Chọn Phòng <span class="text-danger">*</span></label>
                        <select name="room_id" id="util_room_id" class="form-select" required onchange="fetchLatestUtilityForRoom(this.value)">
                            <option value="">-- Chọn phòng --</option>
                            <?php foreach ($rooms as $r): ?>
                                <option value="<?= (int) $r['id'] ?>">
                                    <?= CoreHelper::e($r['room_number']) ?> <?= !empty($r['current_tenant_name']) ? '(' . CoreHelper::e($r['current_tenant_name']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tháng</label>
                        <input type="number" name="month" class="form-control" value="<?= (int) ($filters['month'] ?? date('n')) ?>" min="1" max="12" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Năm</label>
                        <input type="number" name="year" class="form-control" value="<?= (int) ($filters['year'] ?? date('Y')) ?>" min="2020" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-warning-emphasis">Chỉ số Điện CŨ (kWh)</label>
                        <input type="number" name="old_electric" id="old_electric" class="form-control" value="0" min="0" required oninput="previewUtilityCost()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-warning-emphasis">Chỉ số Điện MỚI (kWh) <span class="text-danger">*</span></label>
                        <input type="number" name="new_electric" id="new_electric" class="form-control" value="0" min="0" required oninput="previewUtilityCost()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Đơn giá Điện (VNĐ/kWh)</label>
                        <input type="number" name="electric_rate" id="electric_rate" class="form-control" value="3500" oninput="previewUtilityCost()">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-primary">Chỉ số Nước CŨ (m&sup3;)</label>
                        <input type="number" name="old_water" id="old_water" class="form-control" value="0" min="0" required oninput="previewUtilityCost()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-primary">Chỉ số Nước MỚI (m&sup3;) <span class="text-danger">*</span></label>
                        <input type="number" name="new_water" id="new_water" class="form-control" value="0" min="0" required oninput="previewUtilityCost()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Đơn giá Nước (VNĐ/m&sup3;)</label>
                        <input type="number" name="water_rate" id="water_rate" class="form-control" value="15000" oninput="previewUtilityCost()">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phí dịch vụ khác (Rác, Wifi, Giữ xe)</label>
                        <input type="number" name="other_fee" id="other_fee" class="form-control" value="150000" oninput="previewUtilityCost()">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ngày ghi sổ</label>
                        <input type="date" name="recorded_at" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="col-12">
                        <div class="alert alert-info mb-0 d-flex justify-content-between align-items-center">
                            <span><strong>Tạm tính Điện + Nước + Dịch vụ:</strong></span>
                            <span class="fs-5 fw-bold text-danger" id="utilityPreviewTotal">150.000 đ</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu chỉ số</button>
            </div>
        </form>
    </div>
</div>
