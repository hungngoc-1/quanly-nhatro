<?php

use App\Helpers\CoreHelper;
use App\Models\BookingModel;

$roomBookings = [];
$pendingRoomBookings = 0;
$historyRoomBookingsCount = 0;
try {
    $bookingModel = new BookingModel();
    $roomBookings = $bookingModel->getPending(30);
    $pendingRoomBookings = count($roomBookings);
    $historyRoomBookingsCount = $bookingModel->getHistoryCount();
} catch (\Throwable $e) {
    $roomBookings = [];
}
?>
<!-- Thanh trạng thái & Thêm phòng nằm gọn trên 1 hàng ngang -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
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

    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm <?= $pendingRoomBookings > 0 ? 'btn-danger' : 'btn-outline-secondary' ?> rounded-pill px-3 py-1.5" type="button" data-bs-toggle="collapse" data-bs-target="#bookingRequestsPanel" aria-expanded="<?= $pendingRoomBookings > 0 ? 'true' : 'false' ?>">
            <i class="bi bi-bell-fill me-1"></i>Đặt trước chờ xử lý (<?= $pendingRoomBookings ?>)
        </button>
        <a href="<?= CoreHelper::url('/booking/history') ?>" class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1.5">
            <i class="bi bi-clock-history me-1"></i>Lịch sử đặt phòng (<?= $historyRoomBookingsCount ?>)
        </a>
        <a href="<?= CoreHelper::url('/cho-thue') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5">
            <i class="bi bi-badge-vr-fill text-danger me-1"></i>Xem Web 360°
        </a>
        <button type="button" class="btn btn-sm btn-gradient-primary rounded-pill px-3 py-1.5" data-bs-toggle="modal" data-bs-target="#roomModal" onclick="openCreateRoomModal()">
            <i class="bi bi-plus-circle-fill me-1"></i>Thêm Phòng mới
        </button>
    </div>
</div>

<!-- Bảng thông báo Yêu cầu Giữ phòng / Đặt lịch xem phòng chờ xử lý từ trang Web 360° -->
<div class="collapse <?= $pendingRoomBookings > 0 ? 'show' : '' ?> mb-3" id="bookingRequestsPanel">
    <div class="card border-0 shadow-sm" style="border-left: 4px solid #4f46e5 !important;">
        <div class="card-header bg-white py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-subtle text-danger"><i class="bi bi-megaphone-fill me-1"></i>Chờ xử lý (<?= $pendingRoomBookings ?>)</span>
                <span class="fw-bold text-dark" style="font-size: 0.84rem;">Danh sách Khách Đặt trước / Giữ phòng từ Web 360°</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted d-none d-md-inline" style="font-size: 0.73rem;">Ấn <strong>"Đã liên hệ"</strong> để chuyển vào Lịch sử</span>
                <a href="<?= CoreHelper::url('/booking/history') ?>" class="btn btn-sm btn-outline-primary py-0 px-2.5 rounded-pill" style="font-size: 0.73rem;">
                    <i class="bi bi-clock-history me-1"></i>Xem trang Lịch sử (<?= $historyRoomBookingsCount ?>)
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($roomBookings)): ?>
                <div class="p-3 text-center text-muted" style="font-size: 0.8rem;">
                    Hiện không có yêu cầu giữ phòng nào đang chờ xử lý. Các yêu cầu đã liên hệ được lưu tại <a href="<?= CoreHelper::url('/booking/history') ?>" class="fw-bold text-primary">Trang Lịch sử đặt phòng (<?= $historyRoomBookingsCount ?>)</a>.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Phòng</th>
                                <th>Khách hàng</th>
                                <th>Số điện thoại</th>
                                <th>Ngày dự kiến dọn vào</th>
                                <th>Ghi chú</th>
                                <th>Thời gian gửi</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác xử lý</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roomBookings as $bk): ?>
                                <tr class="table-warning bg-opacity-10">
                                    <td><span class="badge bg-primary">Phòng P.<?= CoreHelper::e($bk['room_number']) ?></span></td>
                                    <td class="fw-bold text-dark"><?= CoreHelper::e($bk['full_name']) ?></td>
                                    <td>
                                        <a href="tel:<?= CoreHelper::e($bk['phone']) ?>" class="fw-semibold text-primary text-decoration-none">
                                            <i class="bi bi-telephone-fill me-1"></i><?= CoreHelper::e($bk['phone']) ?>
                                        </a>
                                    </td>
                                    <td><?= !empty($bk['move_in_date']) ? date('d/m/Y', strtotime($bk['move_in_date'])) : '<span class="text-muted">Chưa chọn</span>' ?></td>
                                    <td class="text-muted" style="max-width: 220px; overflow: hidden; text-overflow: ellipsis;"><?= CoreHelper::e($bk['notes'] ?: '—') ?></td>
                                    <td class="text-muted" style="font-size: 0.74rem;"><?= date('H:i - d/m/Y', strtotime($bk['created_at'])) ?></td>
                                    <td><span class="badge bg-danger-subtle text-danger border border-danger-subtle">Chờ liên hệ</span></td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <form method="POST" action="<?= CoreHelper::url('/booking/status') ?>" class="d-inline">
                                                <input type="hidden" name="id" value="<?= (int) $bk['id'] ?>">
                                                <input type="hidden" name="status" value="contacted">
                                                <button type="submit" class="btn btn-sm btn-success py-0 px-2" style="font-size: 0.72rem;" title="Đánh dấu đã liên hệ và chuyển sang trang Lịch sử">
                                                    <i class="bi bi-check2-circle me-1"></i>Đã liên hệ
                                                </button>
                                            </form>
                                            <a href="<?= CoreHelper::url('/contracts') ?>" class="btn btn-sm btn-primary py-0 px-2" style="font-size: 0.72rem;" title="Tạo hợp đồng cho khách">
                                                <i class="bi bi-file-earmark-plus me-1"></i>Lập HĐ
                                            </a>
                                            <form method="POST" action="<?= CoreHelper::url('/booking/delete') ?>" class="d-inline" onsubmit="return confirm('Xóa yêu cầu giữ phòng này?')">
                                                <input type="hidden" name="id" value="<?= (int) $bk['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.72rem;" title="Xóa yêu cầu">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Thanh tìm kiếm & lọc 1 hàng ngang -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2-5 px-3">
        <form method="GET" action="<?= CoreHelper::url('/rooms') ?>" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="keyword" value="<?= CoreHelper::e($filters['keyword'] ?? '') ?>" class="form-control border-start-0" placeholder="Tìm mã phòng, tiện nghi, tên khách thuê...">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select name="floor" class="form-select form-select-sm">
                    <option value="">-- Tất cả tầng --</option>
                    <?php for ($f = 1; $f <= 5; $f++): ?>
                        <option value="<?= $f ?>" <?= ((string) ($filters['floor'] ?? '') === (string) $f) ? 'selected' : '' ?>>Tầng <?= $f ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Mọi trạng thái --</option>
                    <option value="available" <?= ($filters['status'] ?? '') === 'available' ? 'selected' : '' ?>>Phòng trống</option>
                    <option value="occupied" <?= ($filters['status'] ?? '') === 'occupied' ? 'selected' : '' ?>>Đang thuê</option>
                    <option value="maintenance" <?= ($filters['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Đang bảo trì</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-1.5">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-search me-1"></i>Lọc</button>
                <a href="<?= CoreHelper::url('/rooms') ?>" class="btn btn-sm btn-outline-secondary">Xóa</a>
            </div>
        </form>
    </div>
</div>

<!-- Danh sách Phòng: Rộng rãi như cũ, tất cả các ô bằng nhau tuyệt đối 100% (Width & Height) -->
<div class="row g-3 room-equal-grid">
    <?php if (empty($rooms)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm p-4 text-center text-muted">
                <i class="bi bi-inbox fs-3 d-block mb-1 opacity-50"></i>
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
            $roomJson = htmlspecialchars(json_encode($room, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 d-flex align-items-stretch">
                <div class="card border-0 shadow-sm room-luxury-card <?= $statusBadge['accent'] ?> w-100">
                    <div class="card-body d-flex flex-column p-3">
                        <!-- 1. Đầu thẻ: Mã phòng + Thông số + Nhãn trạng thái (Chiều cao cố định) -->
                        <div class="room-card-header-row mb-2.5">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <div class="room-number-badge flex-shrink-0">
                                    <?= CoreHelper::e($room['room_number']) ?>
                                </div>
                                <div class="min-w-0">
                                    <div class="fw-bold text-dark text-truncate lh-sm">Phòng <?= CoreHelper::e($room['room_number']) ?></div>
                                    <div class="text-muted text-nowrap" style="font-size: 0.72rem;">
                                        Tầng <?= (int) $room['floor'] ?> &bull; <?= (float) $room['area'] ?>m&sup2; &bull; <i class="bi bi-people"></i> <?= (int) $room['max_occupants'] ?>
                                    </div>
                                </div>
                            </div>
                            <span class="badge rounded-pill <?= $statusBadge['class'] ?> flex-shrink-0">
                                <span class="status-dot <?= $statusBadge['dot'] ?> me-1"></span><?= $statusBadge['text'] ?>
                            </span>
                        </div>

                        <!-- 2. Khung Giá thuê (Chiều cao cố định) -->
                        <div class="room-price-banner mb-2.5">
                            <span class="text-muted small fw-medium">Giá thuê:</span>
                            <div class="text-nowrap">
                                <span class="room-price-text fw-bold text-danger"><?= CoreHelper::formatCurrency($room['price']) ?></span>
                                <span class="text-muted" style="font-size: 0.72rem;">/tháng</span>
                            </div>
                        </div>

                        <!-- 3. Dòng Tiện nghi (Chiều cao 1 dòng cố định, chống làm lệch chiều cao ô) -->
                        <div class="room-amenities-row text-muted mb-2.5" title="<?= CoreHelper::e($room['amenities'] ?: 'Tiêu chuẩn') ?>">
                            <i class="bi bi-stars text-warning flex-shrink-0"></i>
                            <span class="text-truncate"><?= CoreHelper::e($room['amenities'] ?: 'Tiêu chuẩn') ?></span>
                        </div>

                        <!-- 4. Khung Khách thuê / Trạng thái (Chiều cao cố định 60px đồng nhất cho mọi phòng) -->
                        <?php if (!empty($room['current_tenant_name'])): ?>
                            <div class="room-tenant-box occupied mb-2.5">
                                <div class="d-flex align-items-center gap-1.5 text-truncate mb-0.5">
                                    <i class="bi bi-person-check-fill text-success flex-shrink-0"></i>
                                    <strong class="text-dark text-truncate"><?= CoreHelper::e($room['current_tenant_name']) ?></strong>
                                </div>
                                <div class="text-muted text-truncate" style="font-size: 0.74rem;">
                                    <i class="bi bi-telephone me-1"></i><?= CoreHelper::e($room['current_tenant_phone']) ?> &bull; <span class="text-primary fw-semibold"><?= CoreHelper::e($room['contract_code']) ?></span>
                                </div>
                            </div>
                        <?php elseif ($room['status'] === 'maintenance'): ?>
                            <div class="room-tenant-box maintenance mb-2.5">
                                <div class="d-flex align-items-center gap-1.5 text-truncate mb-0.5">
                                    <i class="bi bi-tools text-warning-emphasis flex-shrink-0"></i>
                                    <strong class="text-warning-emphasis text-truncate">Đang bảo trì sửa chữa</strong>
                                </div>
                                <div class="text-muted text-truncate" style="font-size: 0.74rem;" title="<?= CoreHelper::e($room['description'] ?: 'Tạm ngưng đón khách để bảo dưỡng') ?>">
                                    <?= CoreHelper::e($room['description'] ?: 'Tạm ngưng đón khách để bảo dưỡng') ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="room-tenant-box available mb-2.5">
                                <div class="d-flex align-items-center gap-1.5 text-truncate mb-0.5">
                                    <i class="bi bi-check2-circle text-primary flex-shrink-0"></i>
                                    <strong class="text-primary text-truncate">Phòng trống sẵn sàng</strong>
                                </div>
                                <div class="text-muted text-truncate" style="font-size: 0.74rem;" title="<?= CoreHelper::e($room['description'] ?: 'Sẵn sàng đón khách thuê mới') ?>">
                                    <?= CoreHelper::e($room['description'] ?: 'Sẵn sàng đón khách thuê mới') ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 5. Chân thẻ: Nút thao tác luôn nằm thẳng hàng dưới đáy -->
                        <div class="mt-auto pt-2.5 border-top d-flex justify-content-between align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill flex-grow-1"
                                    onclick="openEditRoomModal(<?= $roomJson ?>)">
                                <i class="bi bi-pencil-square me-1"></i>Chỉnh sửa
                            </button>
                            <?php if (CoreHelper::hasRole('admin')): ?>
                                <form method="POST" action="<?= CoreHelper::url('/rooms/delete') ?>" class="m-0" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phòng <?= CoreHelper::e($room['room_number']) ?>?');">
                                    <?= CoreHelper::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int) $room['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 31px; height: 31px; padding: 0;" title="Xóa phòng">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
                <h6 class="modal-title fw-bold" id="roomModalTitle">Thêm Phòng trọ mới</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2.5">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Mã phòng <span class="text-danger">*</span></label>
                        <input type="text" name="room_number" id="room_number" class="form-control form-control-sm" placeholder="VD: P104" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Tầng <span class="text-danger">*</span></label>
                        <input type="number" name="floor" id="room_floor" class="form-control form-control-sm" value="1" min="1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Diện tích (m&sup2;) <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" name="area" id="room_area" class="form-control form-control-sm" value="22" min="5" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Số người tối đa <span class="text-danger">*</span></label>
                        <input type="number" name="max_occupants" id="room_max_occupants" class="form-control form-control-sm" value="2" min="1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Giá thuê (VNĐ/tháng) <span class="text-danger">*</span></label>
                        <input type="number" step="50000" name="price" id="room_price" class="form-control form-control-sm" value="2800000" min="100000" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Trạng thái</label>
                        <select name="status" id="room_status" class="form-select form-select-sm">
                            <option value="available">Phòng trống</option>
                            <option value="occupied">Đang thuê</option>
                            <option value="maintenance">Đang bảo trì</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1">Tiện nghi trong phòng</label>
                        <input type="text" name="amenities" id="room_amenities" class="form-control form-control-sm" value="Máy lạnh, Nước nóng, Wifi, Kệ bếp, WC riêng">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1">Ghi chú / Mô tả</label>
                        <textarea name="description" id="room_description" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-sm btn-gradient-primary px-3">Lưu thông tin</button>
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
    bootstrap.Modal.getOrCreateInstance(document.getElementById('roomModal')).show();
}
</script>
