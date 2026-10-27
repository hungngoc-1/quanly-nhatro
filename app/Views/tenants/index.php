<?php

use App\Helpers\CoreHelper;

?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <form method="GET" action="<?= CoreHelper::url('/tenants') ?>" class="d-flex gap-2 flex-wrap">
        <input type="text" name="keyword" value="<?= CoreHelper::e($filters['keyword'] ?? '') ?>" class="form-control form-control-sm" style="width: 240px;" placeholder="Tìm tên, CCCD, SĐT, phòng...">
        <select name="status" class="form-select form-select-sm" style="width: 160px;">
            <option value="">-- Trạng thái --</option>
            <option value="staying" <?= ($filters['status'] ?? '') === 'staying' ? 'selected' : '' ?>>Đang lưu trú</option>
            <option value="moved_out" <?= ($filters['status'] ?? '') === 'moved_out' ? 'selected' : '' ?>>Đã chuyển đi</option>
        </select>
        <button type="submit" class="btn btn-sm btn-secondary"><i class="bi bi-search me-1"></i>Tìm</button>
        <a href="<?= CoreHelper::url('/tenants') ?>" class="btn btn-sm btn-outline-secondary">Làm mới</a>
    </form>

    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tenantModal" onclick="openCreateTenantModal()">
        <i class="bi bi-person-plus-fill me-1"></i> Thêm Khách thuê
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Họ và tên</th>
                        <th>CCCD / CMND</th>
                        <th>Điện thoại</th>
                        <th>Phòng &amp; Thời hạn HĐ (Từ Mùng 01)</th>
                        <th>Quê quán / Nghề nghiệp</th>
                        <th>Tài khoản ĐN</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tenants)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Chưa có dữ liệu khách thuê.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tenants as $t): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= CoreHelper::e($t['full_name']) ?></div>
                                    <small class="text-muted"><?= CoreHelper::e($t['gender']) ?> &bull; NS: <?= CoreHelper::formatDate($t['dob']) ?></small>
                                </td>
                                <td><code><?= CoreHelper::e($t['cccd']) ?></code></td>
                                <td>
                                    <div><?= CoreHelper::e($t['phone']) ?></div>
                                    <small class="text-muted"><?= CoreHelper::e($t['email'] ?: '') ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($t['current_room'])): ?>
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <span class="badge bg-primary"><?= CoreHelper::e($t['current_room']) ?></span>
                                            <?php if (!empty($t['building_name'])): ?>
                                                <span class="badge bg-light text-dark border"><?= CoreHelper::e($t['building_name']) ?></span>
                                            <?php endif; ?>
                                            <small class="text-muted fw-semibold"><?= CoreHelper::e($t['active_contract_code']) ?></small>
                                        </div>
                                        <?php if (!empty($t['contract_start_date']) && !empty($t['contract_end_date'])): ?>
                                            <div class="small">
                                                <?php if (!empty($t['contract_deposit_date']) && $t['contract_deposit_date'] !== $t['contract_start_date']): ?>
                                                    <span class="text-muted">Cọc: <?= CoreHelper::formatDate($t['contract_deposit_date']) ?> &bull; </span>
                                                <?php endif; ?>
                                                <span class="text-dark fw-semibold"><?= CoreHelper::formatDate($t['contract_start_date']) ?></span>
                                                <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                                <span class="text-danger fw-bold"><?= CoreHelper::formatDate($t['contract_end_date']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Chưa xếp phòng</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <div><?= CoreHelper::e($t['hometown'] ?: '-') ?></div>
                                    <div class="text-muted"><?= CoreHelper::e($t['occupation'] ?: '') ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($t['login_username'])): ?>
                                        <span class="badge bg-info-subtle text-info-emphasis border"><?= CoreHelper::e($t['login_username']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($t['status'] === 'staying'): ?>
                                        <span class="badge bg-success">Đang lưu trú</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Đã trả phòng</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick='openEditTenantModal(<?= json_encode($t, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if (CoreHelper::hasRole('admin')): ?>
                                            <form method="POST" action="<?= CoreHelper::url('/tenants/delete') ?>" onsubmit="return confirm('Xóa khách thuê <?= CoreHelper::e($t['full_name']) ?>?');">
                                                <?= CoreHelper::csrfField() ?>
                                                <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
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

<!-- Modal Thêm / Sửa Khách thuê -->
<div class="modal fade" id="tenantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="tenantForm" action="<?= CoreHelper::url('/tenants/store') ?>" class="modal-content">
            <?= CoreHelper::csrfField() ?>
            <input type="hidden" name="id" id="tenant_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="tenantModalTitle">Thêm Khách thuê mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="tenant_full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Số CCCD / CMND (9-12 số) <span class="text-danger">*</span></label>
                        <input type="text" name="cccd" id="tenant_cccd" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="text" name="phone" id="tenant_phone" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Ngày sinh</label>
                        <input type="date" name="dob" id="tenant_dob" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Giới tính</label>
                        <select name="gender" id="tenant_gender" class="form-select">
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" id="tenant_email" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nghề nghiệp / Nơi công tác</label>
                        <input type="text" name="occupation" id="tenant_occupation" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Quê quán / Địa chỉ thường trú</label>
                        <input type="text" name="hometown" id="tenant_hometown" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Liên hệ khẩn cấp</label>
                        <input type="text" name="emergency_contact" id="tenant_emergency" class="form-control" placeholder="SĐT người thân">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select name="status" id="tenant_status" class="form-select">
                            <option value="staying">Đang lưu trú</option>
                            <option value="moved_out">Đã chuyển đi</option>
                        </select>
                    </div>

                    <div class="col-12" id="accountCreationSection">
                        <div class="bg-light p-3 rounded-3 border mt-2">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="create_account" value="1" id="createAccountCheck" onchange="document.getElementById('accountFields').classList.toggle('d-none', !this.checked)">
                                <label class="form-check-label fw-semibold" for="createAccountCheck">
                                    Tạo tài khoản đăng nhập cho khách thuê để tự tra cứu hóa đơn online
                                </label>
                            </div>
                            <div class="row g-2 d-none" id="accountFields">
                                <div class="col-md-6">
                                    <input type="text" name="username" class="form-control form-control-sm" placeholder="Tên đăng nhập (VD: khachthue5)">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="password" class="form-control form-control-sm" value="123456" placeholder="Mật khẩu (mặc định: 123456)">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu thông tin</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateTenantModal() {
    document.getElementById('tenantModalTitle').innerText = 'Thêm Khách thuê mới';
    document.getElementById('tenantForm').action = '<?= CoreHelper::url('/tenants/store') ?>';
    document.getElementById('tenantForm').reset();
    document.getElementById('tenant_id').value = '';
    document.getElementById('accountCreationSection').classList.remove('d-none');
    document.getElementById('accountFields').classList.add('d-none');
}

function openEditTenantModal(t) {
    document.getElementById('tenantModalTitle').innerText = 'Cập nhật Khách thuê: ' + t.full_name;
    document.getElementById('tenantForm').action = '<?= CoreHelper::url('/tenants/update') ?>';
    document.getElementById('tenant_id').value = t.id;
    document.getElementById('tenant_full_name').value = t.full_name;
    document.getElementById('tenant_cccd').value = t.cccd;
    document.getElementById('tenant_phone').value = t.phone;
    document.getElementById('tenant_dob').value = t.dob || '';
    document.getElementById('tenant_gender').value = t.gender || 'Nam';
    document.getElementById('tenant_email').value = t.email || '';
    document.getElementById('tenant_occupation').value = t.occupation || '';
    document.getElementById('tenant_hometown').value = t.hometown || '';
    document.getElementById('tenant_emergency').value = t.emergency_contact || '';
    document.getElementById('tenant_status').value = t.status || 'staying';
    document.getElementById('accountCreationSection').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('tenantModal')).show();
}
</script>
