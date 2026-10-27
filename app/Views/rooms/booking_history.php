<?php

use App\Helpers\CoreHelper;

?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= CoreHelper::url('/rooms') ?>" class="btn btn-sm btn-outline-dark rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i>Quay lại Quản lý Phòng
            <?php if (!empty($pendingCount)): ?>
                <span class="badge bg-danger ms-1"><?= (int) $pendingCount ?> chờ xử lý</span>
            <?php endif; ?>
        </a>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
            <i class="bi bi-clock-history me-1"></i>Đã xử lý: <?= count($historyBookings) ?> yêu cầu
        </span>
    </div>

    <div class="d-flex align-items-center gap-2">
        <a href="<?= CoreHelper::url('/cho-thue') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
            <i class="bi bi-badge-vr-fill text-danger me-1"></i>Xem Web Cho Thuê 360°
        </a>
        <a href="<?= CoreHelper::url('/contracts') ?>" class="btn btn-sm btn-gradient-primary rounded-pill px-3">
            <i class="bi bi-file-earmark-plus me-1"></i>Quản lý Hợp đồng
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm glow-border-card">
    <div class="card-header bg-white py-2-5 px-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <span class="header-icon-pill bg-success-subtle text-success"><i class="bi bi-journal-check"></i></span>
            <div>
                <h6 class="mb-0 fw-bold text-dark">Lịch sử Yêu cầu Đặt trước &amp; Giữ phòng Đã xử lý</h6>
                <div class="text-muted" style="font-size: 0.73rem;">Danh sách khách hàng đã được nhân viên liên hệ tư vấn từ trang Web 360°</div>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if (empty($historyBookings)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-clock-history fs-2 d-block mb-2 opacity-50"></i>
                Chưa có yêu cầu đặt phòng nào trong lịch sử đã xử lý.
                <div class="mt-2">
                    <a href="<?= CoreHelper::url('/rooms') ?>" class="btn btn-sm btn-primary rounded-pill px-3">Về trang Quản lý Phòng</a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Phòng đặt</th>
                            <th>Họ và tên khách</th>
                            <th>Số điện thoại</th>
                            <th>Ngày dự kiến vào ở</th>
                            <th>Lời nhắn / Ghi chú</th>
                            <th>Thời gian gửi</th>
                            <th>Trạng thái xử lý</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historyBookings as $idx => $bk): ?>
                            <tr>
                                <td class="text-muted fw-semibold"><?= $idx + 1 ?></td>
                                <td><span class="badge bg-primary">Phòng P.<?= CoreHelper::e($bk['room_number']) ?></span></td>
                                <td class="fw-bold text-dark"><?= CoreHelper::e($bk['full_name']) ?></td>
                                <td>
                                    <a href="tel:<?= CoreHelper::e($bk['phone']) ?>" class="fw-semibold text-primary text-decoration-none">
                                        <i class="bi bi-telephone-fill me-1"></i><?= CoreHelper::e($bk['phone']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?= !empty($bk['move_in_date']) ? date('d/m/Y', strtotime($bk['move_in_date'])) : '<span class="text-muted">Chưa chọn</span>' ?>
                                </td>
                                <td class="text-muted" style="max-width: 240px; overflow: hidden; text-overflow: ellipsis;">
                                    <?= CoreHelper::e($bk['notes'] ?: '—') ?>
                                </td>
                                <td class="text-muted" style="font-size: 0.75rem;">
                                    <?= date('H:i - d/m/Y', strtotime($bk['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if ($bk['status'] === 'contacted'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle-fill me-1"></i>Đã liên hệ
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary">Đã hủy</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= CoreHelper::url('/contracts') ?>" class="btn btn-sm btn-primary py-0 px-2" style="font-size: 0.72rem;" title="Tạo hợp đồng thuê phòng cho khách này">
                                            <i class="bi bi-file-earmark-plus me-1"></i>Lập HĐ
                                        </a>
                                        <form method="POST" action="<?= CoreHelper::url('/booking/status') ?>" class="d-inline">
                                            <input type="hidden" name="id" value="<?= (int) $bk['id'] ?>">
                                            <input type="hidden" name="status" value="pending">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.72rem;" title="Chuyển lại về danh sách Chờ liên hệ">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Hoàn tác
                                            </button>
                                        </form>
                                        <form method="POST" action="<?= CoreHelper::url('/booking/delete') ?>" class="d-inline" onsubmit="return confirm('Xóa vĩnh viễn lịch sử đặt phòng này?')">
                                            <input type="hidden" name="id" value="<?= (int) $bk['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.72rem;" title="Xóa khỏi lịch sử">
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
