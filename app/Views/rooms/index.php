<?php

use App\Helpers\CoreHelper;

?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div class="d-flex gap-2 flex-wrap filter-pill-group">
        <a href="<?= CoreHelper::url('/rooms') ?>" class="btn btn-sm rounded-pill px-3 <?= empty($filters['status']) ? 'btn-dark shadow-sm' : 'btn-outline-dark' ?>">
            <i class="bi bi-grid-fill me-1"></i>Tất cả (<?= (int) $counts['total'] ?>)
        </a>
        <a href="<?= CoreHelper::url('/rooms', ['status' => 'available']) ?>" class="btn btn-sm rounded-pill px-3 <?= ($filters['status'] ?? '') === 'available' ? 'btn-primary shadow-sm' : 'btn-outline-primary' ?>">
            <span class="status-dot bg-primary me-1"></span>Phòng trống (<?= (int) $counts['available'] ?>)
        </a>
        <a href="<?= CoreHelper::url('/rooms', ['status' => 'occupied']) ?>" class="btn btn-sm rounded-pill px-3 <?= ($filters['status'] ?? '') === 'occupied' ? 'btn-success shadow-sm' : 'btn-outline-success' ?>">
            <span class="status-dot bg-success me-1"></span>Đang thuê (<?= (int) $counts['occupied'] ?>)
        </a>
        <a href="<?= CoreHelper::url('/rooms', ['status' => 'maintenance']) ?>" class="btn btn-sm rounded-pill px-3 <?= ($filters['status'] ?? '') === 'maintenance' ? 'btn-warning shadow-sm' : 'btn-outline-warning' ?>">
            <span class="status-dot bg-warning me-1"></span>Bảo trì (<?= (int) $counts['maintenance'] ?>)
        </a>
    </div>

    <button type="button" class="btn btn-gradient-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#roomModal" onclick="openCreateRoomModal()">
        <i class="bi bi-plus-circle-fill me-1"></i> Thêm Phòng mới
    </button>
</div>

<!-- Bộ lọc tìm kiếm -->
<div class="card border-0 shadow-sm glow-border-card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= CoreHelper::url('/rooms') ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted fw-semibold mb-1"><i class="bi bi-search me-1"></i>Từ khóa tìm kiếm</label>
                <input type="text" name="keyword" value="<?= CoreHelper::e($filters['keyword'] ?? '') ?>" class="form-control form-control-sm" placeholder="Mã phòng, tiện nghi, tên khách thuê...">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted fw-semibold mb-1"><i class="bi bi-layers me-1"></i>Tầng</label>
                <select name="floor" class="form-select form-select-sm">
                    <option value="">-- Tất cả tầng --</option>
                    <?php for ($f = 1; $f <= 5; $f++): ?>
                        <option value="<?= $f ?>" <?= ((string) ($filters['floor'] ?? '') === (string) $f) ? 'selected' : '' ?>>Tầng <?= $f ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted fw-semibold mb-1"><i class="bi bi-funnel me-1"></i>Trạng thái</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="available" <?= ($filters['status'] ?? '') === 'available' ? 'selected' : '' ?>>Phòng trống</option>
                    <option value="occupied" <?= ($filters['status'] ?? '') === 'occupied' ? 'selected' : '' ?>>Đang thuê</option>
                    <option value="maintenance" <?= ($filters['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Đang bảo trì</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i>Lọc</button>
                <a href="<?= CoreHelper::url('/rooms') ?>" class="btn btn-sm btn-outline-secondary">Xóa</a>
            </div>
        </form>
    </div>
</div>

<!-- Danh sách Phòng dạng Card hiện đại -->
<div class="row g-4">
    <?php if (empty($rooms)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm glow-border-card p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                Không tìm thấy phòng trọ nào phù hợp với bộ lọc.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($rooms as $room): ?>
            <?php
            $statusBadge = match ($room['status']) {
                'occupied'    => ['text' => 'Đang thuê', 'class' => 'bg-success-subtle text-success border border-success-subtle', 'dot' => 'bg-success', 'accent' => 'room-accent-occupied'],
                'maintenance' => ['text' => 'Bảo trì', 'class' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'dot' => 'bg-warning', 'accent' => 'room-accent-maintenance'],
                default       => ['text' => 'Phòng trống', 'class' => 'bg-primary-subtle text-primary border border-primary-subtle', 'dot' => 'bg-primary', 'accent' => 'room-accent-available'],
            };
            ?>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm room-luxury-card glow-border-card <?= $statusBadge['accent'] ?> h-100">
                    <div class="card-body d-flex flex-column p-3-5">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="room-number-badge">
                                    <?= CoreHelper::e($room['room_number']) ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark lh-1 mb-1">Phòng <?= CoreHelper::e($room['room_number']) ?></div>
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        Tầng <?= (int) $room['floor'] ?> &bull; <?= (float) $room['area'] ?> m&sup2; &bull; <i class="bi bi-people"></i> <?= (int) $room['max_occupants'] ?>
                                    </small>
                                </div>
                            </div>
                            <span class="badge rounded-pill <?= $statusBadge['class'] ?>">
                                <span class="status-dot <?= $statusBadge['dot'] ?> me-1"></span><?= $statusBadge['text'] ?>
                            </span>
                        </div>

                        <div class="room-price-banner mb-3">
                            <span class="fs-5 fw-extrabold text-danger"><?= CoreHelper::formatCurrency($room['price']) ?></span>
                            <span class="text-muted small"> / tháng</span>
                        </div>

                        <div class="small text-muted mb-3 d-flex align-items-start gap-1">
                            <i class="bi bi-stars text-warning mt-0-5"></i>
                            <span><?= CoreHelper::e($room['amenities'] ?: 'Tiêu chuẩn') ?></span>
                        </div>

                        <?php if (!empty($room['current_tenant_name'])): ?>
                            <div class="room-tenant-box occupied mb-3">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="bi bi-person-check-fill text-success"></i>
                                    <strong class="text-dark"><?= CoreHelper::e($room['current_tenant_name']) ?></strong>
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">
                                    <i class="bi bi-telephone me-1"></i><?= CoreHelper::e($room['current_tenant_phone']) ?> &bull; <span class="text-primary fw-semibold"><?= CoreHelper::e($room['contract_code']) ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="room-tenant-box available mb-3">
                                <i class="bi bi-check2-circle text-primary me-1"></i><?= CoreHelper::e($room['description'] ?: 'Sẵn sàng đón khách thuê mới') ?>
                            </div>
                        <?php endif; ?>

                        <div class="mt-auto pt-3 border-top d-flex justify-content-between gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill flex-grow-1"
                                    onclick='openEditRoomModal(<?= json_encode($room, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                <i class="bi bi-pencil-square me-1"></i>Chỉnh sửa
                            </button>
                            <?php if (CoreHelper::hasRole('admin')): ?>
                                <form method="POST" action="<?= CoreHelper::url('/rooms/delete') ?>" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phòng <?= CoreHelper::e($room['room_number']) ?>?');">
                                    <?= CoreHelper::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int) $room['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle" style="width: 32px; height: 32px; padding: 0;" title="Xóa phòng"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Thêm / Sửa Phòng -->
<div class="modal fade" id="roomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="roomForm" action="<?= CoreHelper::url('/rooms/store') ?>" class="modal-content">
            <?= CoreHelper::csrfField() ?>
            <input type="hidden" name="id" id="room_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="roomModalTitle">Thêm Phòng trọ mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Mã phòng <span class="text-danger">*</span></label>
                        <input type="text" name="room_number" id="room_number" class="form-control" placeholder="VD: P104" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tầng <span class="text-danger">*</span></label>
                        <input type="number" name="floor" id="room_floor" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Diện tích (m&sup2;) <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" name="area" id="room_area" class="form-control" value="22" min="5" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Số người tối đa <span class="text-danger">*</span></label>
                        <input type="number" name="max_occupants" id="room_max_occupants" class="form-control" value="2" min="1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giá thuê (VNĐ/tháng) <span class="text-danger">*</span></label>
                        <input type="number" step="50000" name="price" id="room_price" class="form-control" value="2800000" min="100000" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select name="status" id="room_status" class="form-select">
                            <option value="available">Phòng trống</option>
                            <option value="occupied">Đang thuê</option>
                            <option value="maintenance">Đang bảo trì</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Tiện nghi trong phòng</label>
                        <input type="text" name="amenities" id="room_amenities" class="form-control" value="Máy lạnh, Nước nóng, Wifi, Kệ bếp, WC riêng">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Ghi chú / Mô tả</label>
                        <textarea name="description" id="room_description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-gradient-primary rounded-pill px-4">Lưu thông tin</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateRoomModal() {
    document.getElementById('roomModalTitle').innerText = 'Thêm Phòng trọ mới';
    document.getElementById('roomForm').action = '<?= CoreHelper::url('/rooms/store') ?>';
    document.getElementById('roomForm').reset();
    document.getElementById('room_id').value = '';
}

function openEditRoomModal(room) {
    document.getElementById('roomModalTitle').innerText = 'Cập nhật Phòng ' + room.room_number;
    document.getElementById('roomForm').action = '<?= CoreHelper::url('/rooms/update') ?>';
    document.getElementById('room_id').value = room.id;
    document.getElementById('room_number').value = room.room_number;
    document.getElementById('room_floor').value = room.floor;
    document.getElementById('room_area').value = room.area;
    document.getElementById('room_max_occupants').value = room.max_occupants;
    document.getElementById('room_price').value = room.price;
    document.getElementById('room_status').value = room.status;
    document.getElementById('room_amenities').value = room.amenities || '';
    document.getElementById('room_description').value = room.description || '';
    new bootstrap.Modal(document.getElementById('roomModal')).show();
}
</script>
