<?php
use App\Helpers\CoreHelper;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
            <i class="bi bi-compass-fill me-1"></i>
            <?= !empty($selectedBuildingId) ? 'Đang lọc theo 1 Tòa nhà' : 'Đang xem: Tất cả ' . count($buildings) . ' Tòa nhà' ?>
        </span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= CoreHelper::url('/buildings/select', ['id' => 0, 'redirect_to' => '/dashboard']) ?>"
           class="btn btn-sm rounded-pill px-3 <?= empty($selectedBuildingId) ? 'btn-dark' : 'btn-outline-dark' ?>">
            <i class="bi bi-globe-asia-australia me-1"></i>Xem Tất cả Tòa nhà
        </a>
        <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addBuildingModal">
            <i class="bi bi-plus-lg me-1"></i>Thêm Tòa nhà
        </button>
    </div>
</div>

<!-- Danh sách 3 Tòa nhà gọn gàng -->
<div class="row g-3">
    <?php foreach ($buildings as $bld): ?>
        <?php
        $isSelected = ((int) ($selectedBuildingId ?? 0) === (int) $bld['id']);
        $totalRooms = (int) ($bld['total_rooms'] ?? 0);
        $occupiedRooms = (int) ($bld['occupied_rooms'] ?? 0);
        $availableRooms = (int) ($bld['available_rooms'] ?? 0);
        $occupancyPct = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;
        ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 border-0 shadow-sm <?= $isSelected ? 'border border-2 border-primary' : '' ?>">
                <div class="card-body p-3 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-primary-subtle text-primary fw-bold"><?= CoreHelper::e($bld['building_code']) ?></span>
                            <?php if ($isSelected): ?>
                                <span class="badge bg-success ms-1">Đang chọn</span>
                            <?php endif; ?>
                            <h6 class="fw-bold mt-1 mb-0"><?= CoreHelper::e($bld['building_name']) ?></h6>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light py-0 px-1.5" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end small">
                                <li>
                                    <button type="button" class="dropdown-item btn-edit-building"
                                            data-id="<?= $bld['id'] ?>"
                                            data-code="<?= CoreHelper::e($bld['building_code']) ?>"
                                            data-name="<?= CoreHelper::e($bld['building_name']) ?>"
                                            data-address="<?= CoreHelper::e($bld['address'] ?? '') ?>"
                                            data-description="<?= CoreHelper::e($bld['description'] ?? '') ?>"
                                            data-manager="<?= CoreHelper::e($bld['manager_id'] ?? '') ?>"
                                            data-status="<?= CoreHelper::e($bld['status'] ?? 'active') ?>">
                                        <i class="bi bi-pencil-square me-2 text-primary"></i>Sửa
                                    </button>
                                </li>
                                <li>
                                    <form action="<?= CoreHelper::url('/buildings/delete') ?>" method="POST" onsubmit="return confirm('Xóa tòa nhà này?');">
                                        <?= CoreHelper::csrfField() ?>
                                        <input type="hidden" name="id" value="<?= $bld['id'] ?>">
                                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Xóa</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="text-muted small mb-2 text-truncate">
                        <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= CoreHelper::e($bld['address'] ?: 'Chưa cập nhật') ?>
                    </div>

                    <div class="p-2 rounded bg-light border small mb-3 d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="bi bi-person-badge text-primary me-1"></i>Quản lý riêng:</span>
                        <?php if (!empty($bld['manager_name'])): ?>
                            <strong class="text-dark"><?= CoreHelper::e($bld['manager_name']) ?> (<code><?= CoreHelper::e($bld['manager_username']) ?></code>)</strong>
                        <?php else: ?>
                            <span class="text-warning fw-semibold">Chưa gán</span>
                        <?php endif; ?>
                    </div>

                    <div class="row g-2 text-center mb-2">
                        <div class="col-4">
                            <div class="p-1.5 rounded bg-primary bg-opacity-10">
                                <div class="fw-bold text-primary"><?= $totalRooms ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;">Tổng</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-1.5 rounded bg-success bg-opacity-10">
                                <div class="fw-bold text-success"><?= $occupiedRooms ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;">Đang thuê</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-1.5 rounded bg-warning bg-opacity-10">
                                <div class="fw-bold text-warning-emphasis"><?= $availableRooms ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;">Phòng trống</div>
                            </div>
                        </div>
                    </div>

                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-primary" style="width: <?= $occupancyPct ?>%"></div>
                    </div>

                    <div class="mt-auto d-flex gap-2">
                        <a href="<?= CoreHelper::url('/buildings/select', ['id' => $bld['id'], 'redirect_to' => '/dashboard']) ?>"
                           class="btn btn-sm btn-primary w-100 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Vào quản lý Tòa này
                        </a>
                        <a href="<?= CoreHelper::url('/buildings/select', ['id' => $bld['id'], 'redirect_to' => '/rooms']) ?>"
                           class="btn btn-sm btn-outline-primary" title="Xem phòng">
                            <i class="bi bi-door-open"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal Thêm Tòa nhà -->
<div class="modal fade" id="addBuildingModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= CoreHelper::url('/buildings/store') ?>" method="POST" class="modal-content">
            <?= CoreHelper::csrfField() ?>
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Thêm Tòa nhà mới</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2.5">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Mã tòa *</label>
                        <input type="text" name="building_code" class="form-control form-control-sm" placeholder="TOA-D" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Tên tòa nhà *</label>
                        <input type="text" name="building_name" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Quản lý phụ trách</label>
                        <select name="manager_id" class="form-select form-select-sm">
                            <option value="">-- Chọn Quản lý --</option>
                            <?php foreach ($managers as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= CoreHelper::e($m['full_name']) ?> (@<?= CoreHelper::e($m['username']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Địa chỉ</label>
                        <input type="text" name="address" class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Mô tả</label>
                        <input type="text" name="description" class="form-control form-control-sm">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-sm btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa Tòa nhà -->
<div class="modal fade" id="editBuildingModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= CoreHelper::url('/buildings/update') ?>" method="POST" class="modal-content">
            <?= CoreHelper::csrfField() ?>
            <input type="hidden" name="id" id="edit_bld_id">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Cập nhật Tòa nhà</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2.5">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Mã tòa *</label>
                        <input type="text" name="building_code" id="edit_bld_code" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Tên tòa nhà *</label>
                        <input type="text" name="building_name" id="edit_bld_name" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Quản lý phụ trách</label>
                        <select name="manager_id" id="edit_bld_manager" class="form-select form-select-sm">
                            <option value="">-- Chọn Quản lý --</option>
                            <?php foreach ($managers as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= CoreHelper::e($m['full_name']) ?> (@<?= CoreHelper::e($m['username']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Địa chỉ</label>
                        <input type="text" name="address" id="edit_bld_address" class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Mô tả</label>
                        <input type="text" name="description" id="edit_bld_description" class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Trạng thái</label>
                        <select name="status" id="edit_bld_status" class="form-select form-select-sm">
                            <option value="active">Đang hoạt động</option>
                            <option value="inactive">Tạm ngưng</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-sm btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-edit-building').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit_bld_id').value = this.dataset.id;
            document.getElementById('edit_bld_code').value = this.dataset.code;
            document.getElementById('edit_bld_name').value = this.dataset.name;
            document.getElementById('edit_bld_address').value = this.dataset.address;
            document.getElementById('edit_bld_description').value = this.dataset.description;
            document.getElementById('edit_bld_manager').value = this.dataset.manager;
            document.getElementById('edit_bld_status').value = this.dataset.status;
            new bootstrap.Modal(document.getElementById('editBuildingModal')).show();
        });
    });
});
</script>
