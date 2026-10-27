<?php

use App\Helpers\CoreHelper;

?>
<?php if (!empty($isTenant)): ?>
    <!-- Giao diện Dashboard dành cho Khách thuê -->
    <?php
    $daysLeft = isset($tenantInfo['days_left']) ? (int) $tenantInfo['days_left'] : 999;
    $renewalStatus = $tenantInfo['renewal_status'] ?? 'none';
    $hasContract = !empty($tenantInfo) && !empty($tenantInfo['contract_id']);
    $showRenewalNotice = $hasContract && (($daysLeft >= 0 && $daysLeft <= 30) || $renewalStatus === 'notified');
    ?>

    <?php if ($hasContract && $showRenewalNotice && !in_array($renewalStatus, ['declined', 'auto_no_renew'], true)): ?>
        <!-- Thông báo tự động trước khi hết hợp đồng 1 tháng (Gọn gàng) -->
        <div class="card border-0 shadow-sm mb-3 border-start border-warning border-4">
            <div class="card-body py-3 px-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-warning text-dark"><i class="bi bi-bell-fill me-1"></i>Thông báo gia hạn trước 1 tháng</span>
                        <span class="badge bg-danger">Còn <?= max(0, $daysLeft) ?> ngày</span>
                    </div>
                    <div class="fw-bold text-dark">
                        Hợp đồng <?= CoreHelper::e($tenantInfo['contract_code']) ?> hết hạn ngày <?= CoreHelper::formatDate($tenantInfo['end_date']) ?>. Bạn có muốn gia hạn ở tiếp không?
                    </div>
                    <div class="small text-muted">Nếu không phản hồi, hệ thống tự động hiểu là không gia hạn khi hết hợp đồng.</div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <form method="POST" action="<?= CoreHelper::url('/contracts/renew') ?>" class="d-flex gap-1.5 m-0">
                        <?= CoreHelper::csrfField() ?>
                        <input type="hidden" name="contract_id" value="<?= (int) $tenantInfo['contract_id'] ?>">
                        <input type="hidden" name="decision" value="accept">
                        <input type="hidden" name="redirect_to" value="/dashboard">
                        <select name="renewal_months" class="form-select form-select-sm" style="width: 130px;">
                            <option value="6" selected>+6 tháng</option>
                            <option value="12">+12 tháng</option>
                            <option value="3">+3 tháng</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-success fw-semibold text-nowrap">
                            <i class="bi bi-check2-circle me-1"></i>Đồng ý ở tiếp
                        </button>
                    </form>

                    <form method="POST" action="<?= CoreHelper::url('/contracts/renew') ?>" class="m-0">
                        <?= CoreHelper::csrfField() ?>
                        <input type="hidden" name="contract_id" value="<?= (int) $tenantInfo['contract_id'] ?>">
                        <input type="hidden" name="decision" value="decline">
                        <input type="hidden" name="redirect_to" value="/dashboard">
                        <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap">Không gia hạn</button>
                    </form>
                </div>
            </div>
        </div>
    <?php elseif ($hasContract && $renewalStatus === 'renewed'): ?>
        <div class="alert alert-success border-0 shadow-sm py-2 px-3 small mb-3">
            <i class="bi bi-check-circle-fill me-1"></i>Đã gia hạn hợp đồng đến ngày <strong><?= CoreHelper::formatDate($tenantInfo['end_date']) ?></strong>.
        </div>
    <?php elseif ($hasContract && in_array($renewalStatus, ['declined', 'auto_no_renew'], true)): ?>
        <div class="alert alert-secondary border-0 shadow-sm py-2 px-3 small d-flex justify-content-between align-items-center mb-3">
            <span><i class="bi bi-info-circle-fill me-1"></i>Không gia hạn — Hợp đồng kết thúc ngày <strong><?= CoreHelper::formatDate($tenantInfo['end_date']) ?></strong>.</span>
            <?php if ($daysLeft >= 0): ?>
                <form method="POST" action="<?= CoreHelper::url('/contracts/renew') ?>" class="m-0">
                    <?= CoreHelper::csrfField() ?>
                    <input type="hidden" name="contract_id" value="<?= (int) $tenantInfo['contract_id'] ?>">
                    <input type="hidden" name="decision" value="accept">
                    <input type="hidden" name="renewal_months" value="6">
                    <button type="submit" class="btn btn-sm btn-primary py-0 px-2">Gia hạn lại</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm glow-border-card h-100">
                <div class="card-header bg-gradient-primary text-white fw-bold py-2.5 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-house-heart-fill me-2"></i>Phòng &amp; Hợp đồng</span>
                    <?php if (!empty($tenantInfo['contract_id'])): ?>
                        <a href="<?= CoreHelper::url('/contracts/detail', ['id' => $tenantInfo['contract_id']]) ?>" class="btn btn-sm btn-light text-primary fw-bold py-0 px-2.5">
                            <i class="bi bi-vector-pen me-1"></i>Xem &amp; Ký HĐ
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($tenantInfo) && !empty($tenantInfo['room_number'])): ?>
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div>
                                <div class="text-muted small">
                                    <?= CoreHelper::e($tenantInfo['building_name'] ?? 'Tòa A') ?>
                                    <?php if (!empty($tenantInfo['room_type_name'])): ?>
                                        &bull; <span class="text-primary fw-semibold"><?= CoreHelper::e($tenantInfo['room_type_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="fw-extrabold text-primary mb-0"><?= CoreHelper::e($tenantInfo['room_number']) ?> <span class="fs-6 fw-medium text-muted">(Tầng <?= (int) $tenantInfo['floor'] ?>)</span></h3>
                            </div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5">
                                Đang lưu trú
                            </span>
                        </div>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Khách thuê:</span>
                                <strong><?= CoreHelper::e($tenantInfo['full_name']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Mã hợp đồng:</span>
                                <a href="<?= CoreHelper::url('/contracts/detail', ['id' => $tenantInfo['contract_id']]) ?>" class="fw-bold text-primary text-decoration-none">
                                    <?= CoreHelper::e($tenantInfo['contract_code']) ?> <i class="bi bi-box-arrow-up-right ms-1"></i>
                                </a>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Giá thuê:</span>
                                <strong class="text-danger"><?= CoreHelper::formatCurrency($tenantInfo['monthly_rent']) ?>/tháng</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Tiền cọc (1 tháng):</span>
                                <strong>
                                    <?= CoreHelper::formatCurrency($tenantInfo['deposit_amount']) ?>
                                    <?php if (!empty($tenantInfo['deposit_date'])): ?>
                                        <span class="text-muted fw-normal">(Cọc ngày <?= CoreHelper::formatDate($tenantInfo['deposit_date']) ?>)</span>
                                    <?php endif; ?>
                                </strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Hiệu lực HĐ (Mùng 01):</span>
                                <strong class="text-primary"><?= CoreHelper::formatDate($tenantInfo['start_date']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Hết hạn HĐ (Tính từ mùng 01):</span>
                                <strong class="text-danger">
                                    <?= CoreHelper::formatDate($tenantInfo['end_date']) ?>
                                    <?php if (isset($tenantInfo['days_left']) && (int) $tenantInfo['days_left'] >= 0): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis ms-1">Còn <?= (int) $tenantInfo['days_left'] ?> ngày</span>
                                    <?php endif; ?>
                                </strong>
                            </li>
                        </ul>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-info-circle fs-1 d-block mb-2 text-primary opacity-50"></i>
                            Hiện chưa có hợp đồng phòng đang hoạt động gắn với tài khoản này.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm glow-border-card h-100">
                <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Hóa đơn gần đây của bạn</span>
                    <a href="<?= CoreHelper::url('/invoices/my-list') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Xem tất cả</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kỳ</th>
                                    <th>Tổng tiền</th>
                                    <th>Còn nợ</th>
                                    <th>Trạng thái</th>
                                    <th class="text-end">Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($myInvoices)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Chưa có hóa đơn nào.</td></tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($myInvoices, 0, 5) as $inv): ?>
                                        <tr>
                                            <td class="fw-semibold">Tháng <?= (int) $inv['month'] ?>/<?= (int) $inv['year'] ?></td>
                                            <td class="fw-bold"><?= CoreHelper::formatCurrency($inv['total_amount']) ?></td>
                                            <td class="text-danger fw-semibold"><?= CoreHelper::formatCurrency($inv['remaining_amount']) ?></td>
                                            <td>
                                                <?php if ($inv['status'] === 'paid'): ?>
                                                    <span class="badge bg-success">Đã thanh toán</span>
                                                <?php elseif ($inv['status'] === 'partial'): ?>
                                                    <span class="badge bg-warning text-dark">Đóng 1 phần</span>
                                                <?php elseif ($inv['status'] === 'overdue'): ?>
                                                    <span class="badge bg-danger">Quá hạn</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Chưa đóng</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= CoreHelper::url('/invoices/detail', ['id' => $inv['id']]) ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                                    <i class="bi bi-eye me-1"></i>Xem
                                                </a>
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
<?php else: ?>
    <!-- 1. ƯU TIÊN HIỂN THỊ ĐẦU TIÊN KHI MỞ TRANG: Hóa đơn Thu nợ & Các Thông báo (Đặt phòng + Hợp đồng sắp hết hạn) -->
    <div class="row g-3 mb-3">
        <!-- Cột Trái: Hóa đơn Chưa thu / Thu nợ -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm glow-border-card h-100">
                <div class="card-header bg-white py-2-5 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="header-icon-pill bg-danger-subtle text-danger"><i class="bi bi-receipt-cutoff"></i></span>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">Hóa đơn Cần Thu Nợ (<?= count($unpaidInvoices) ?>)</h6>
                        </div>
                    </div>
                    <div class="d-flex gap-1.5">
                        <a href="<?= CoreHelper::url('/invoices/create') ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                            <i class="bi bi-receipt-cutoff me-1"></i>Quản lý &amp; Lập hóa đơn
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã HĐ</th>
                                    <th>Phòng / Khách</th>
                                    <th>Kỳ</th>
                                    <th>Còn phải thu</th>
                                    <th>Hạn đóng</th>
                                    <th class="text-end">Thao tác &amp; Thu tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($unpaidInvoices)): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">Tuyệt vời! Không có hóa đơn nào tồn đọng công nợ.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($unpaidInvoices as $inv): ?>
                                        <tr>
                                            <td class="fw-bold text-primary"><?= CoreHelper::e($inv['invoice_code']) ?></td>
                                            <td>
                                                <span class="badge bg-dark"><?= CoreHelper::e($inv['room_number']) ?></span>
                                                <span class="ms-1 fw-medium"><?= CoreHelper::e($inv['tenant_name']) ?></span>
                                            </td>
                                            <td>T<?= (int) $inv['month'] ?>/<?= (int) $inv['year'] ?></td>
                                            <td class="fw-bold text-danger"><?= CoreHelper::formatCurrency($inv['remaining_amount']) ?></td>
                                            <td>
                                                <?= CoreHelper::formatDate($inv['due_date']) ?>
                                                <?php if ($inv['status'] === 'overdue'): ?>
                                                    <span class="badge bg-danger ms-1">Quá hạn</span>
                                                <?php elseif ($inv['status'] === 'partial'): ?>
                                                    <span class="badge bg-warning text-dark ms-1">1 phần</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1">
                                                    <button type="button"
                                                            class="btn btn-sm btn-success rounded-pill px-2.5"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#dashQuickPayModal"
                                                            onclick="openDashQuickPay(<?= (int) $inv['id'] ?>, '<?= CoreHelper::e($inv['invoice_code']) ?>', '<?= CoreHelper::e($inv['room_number']) ?>', '<?= CoreHelper::e($inv['tenant_name']) ?>', <?= (float) $inv['remaining_amount'] ?>)">
                                                        <i class="bi bi-cash-coin me-1"></i>Thu tiền
                                                    </button>
                                                    <a href="<?= CoreHelper::url('/invoices/detail', ['id' => $inv['id']]) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5">
                                                        Chi tiết
                                                    </a>
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

        <!-- Cột Phải: Trung tâm Thông báo (Khách đặt trước phòng 360° & Hợp đồng sắp hết hạn) -->
        <div class="col-lg-5 d-flex flex-column gap-3">
            <!-- Thông báo 1: Khách đặt trước / giữ phòng từ Web 360° -->
            <div class="card border-0 shadow-sm glow-border-card">
                <div class="card-header bg-white py-2-5 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="header-icon-pill bg-primary-subtle text-primary"><i class="bi bi-bell-fill"></i></span>
                        <h6 class="mb-0 fw-bold text-dark">
                            Thông báo Đặt trước Phòng
                            <?php if (!empty($pendingBookings)): ?>
                                <span class="badge bg-danger ms-1"><?= count($pendingBookings) ?> mới</span>
                            <?php endif; ?>
                        </h6>
                    </div>
                    <a href="<?= CoreHelper::url('/booking/history') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" style="font-size: 0.72rem;">
                        <i class="bi bi-clock-history me-1"></i>Lịch sử (<?= (int) ($historyBookingsCount ?? 0) ?>)
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($pendingBookings)): ?>
                        <div class="py-3 px-3 text-center text-muted small">
                            Hiện không có yêu cầu giữ phòng mới chờ xử lý.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Phòng</th>
                                        <th>Khách đặt / SĐT</th>
                                        <th>Vào ở</th>
                                        <th class="text-end">Xử lý</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($pendingBookings, 0, 4) as $bk): ?>
                                        <tr class="table-warning bg-opacity-10">
                                            <td><span class="badge bg-primary">P.<?= CoreHelper::e($bk['room_number']) ?></span></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= CoreHelper::e($bk['full_name']) ?></div>
                                                <a href="tel:<?= CoreHelper::e($bk['phone']) ?>" class="text-primary text-decoration-none fw-semibold"><?= CoreHelper::e($bk['phone']) ?></a>
                                            </td>
                                            <td><?= !empty($bk['move_in_date']) ? date('d/m', strtotime($bk['move_in_date'])) : 'Sớm nhất' ?></td>
                                            <td class="text-end">
                                                <form method="POST" action="<?= CoreHelper::url('/booking/status') ?>" class="d-inline">
                                                    <input type="hidden" name="id" value="<?= (int) $bk['id'] ?>">
                                                    <input type="hidden" name="status" value="contacted">
                                                    <button type="submit" class="btn btn-sm btn-success py-0 px-2" style="font-size: 0.7rem;" title="Đánh dấu đã liên hệ và chuyển sang Lịch sử">
                                                        <i class="bi bi-check2-circle me-1"></i>Đã liên hệ
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Thông báo 2: Hợp đồng Sắp hết hạn (60 ngày) -->
            <div class="card border-0 shadow-sm glow-border-card flex-grow-1">
                <div class="card-header bg-white py-2-5 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="header-icon-pill bg-warning-subtle text-warning-emphasis"><i class="bi bi-calendar-event"></i></span>
                        <h6 class="mb-0 fw-bold text-dark">Hợp đồng Sắp hết hạn (60 ngày)</h6>
                    </div>
                    <a href="<?= CoreHelper::url('/contracts') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" style="font-size: 0.72rem;">Quản lý HĐ</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Phòng</th>
                                    <th>Khách thuê</th>
                                    <th>Ngày hết hạn</th>
                                    <th>Còn lại / Gia hạn</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($expiringContracts)): ?>
                                    <tr><td colspan="4" class="text-center py-3 text-muted">Không có hợp đồng nào sắp hết hạn trong 60 ngày tới.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($expiringContracts as $ec): ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?= CoreHelper::e($ec['room_number']) ?></span></td>
                                            <td>
                                                <div class="fw-semibold"><?= CoreHelper::e($ec['tenant_name']) ?></div>
                                                <div class="text-muted"><?= CoreHelper::e($ec['tenant_phone']) ?></div>
                                            </td>
                                            <td><?= CoreHelper::formatDate($ec['end_date']) ?></td>
                                            <td>
                                                <?php $days = (int) $ec['days_left']; ?>
                                                <?php if ($days < 0): ?>
                                                    <span class="badge bg-danger">Quá hạn <?= abs($days) ?> ngày</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Còn <?= $days ?> ngày</span>
                                                <?php endif; ?>
                                                <?php $rStatus = $ec['renewal_status'] ?? 'none'; ?>
                                                <div class="mt-1">
                                                    <?php if ($rStatus === 'notified' || ($days >= 0 && $days <= 30 && $rStatus === 'none')): ?>
                                                        <span class="badge bg-info-subtle text-info" title="Nếu khách không trả lời sẽ tự động hiểu không gia hạn">Đã báo khách (Chờ phản hồi)</span>
                                                    <?php elseif ($rStatus === 'renewed'): ?>
                                                        <span class="badge bg-success-subtle text-success">Khách đồng ý gia hạn</span>
                                                    <?php elseif ($rStatus === 'declined' || $rStatus === 'auto_no_renew'): ?>
                                                        <span class="badge bg-secondary-subtle text-secondary">Không gia hạn</span>
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

    <!-- 2. Bốn thẻ Chỉ số Tổng quan & Cơ cấu Trạng thái Phòng nằm gọn trên 1 hàng -->
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="row g-2.5 h-100">
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm stat-card glow-border-card stat-card-indigo h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="stat-label">Tỷ lệ lấp đầy phòng</div>
                                    <h3 class="stat-value mb-0"><?= (int) $stats['rooms']['occupied'] ?> <span class="fs-6 text-muted fw-normal">/ <?= (int) $stats['rooms']['total'] ?> phòng</span></h3>
                                </div>
                                <div class="stat-icon-modern icon-indigo">
                                    <i class="bi bi-door-open-fill"></i>
                                </div>
                            </div>
                            <div class="progress stat-progress mb-2" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= min(100, (float) $stats['occupancy_rate']) ?>%"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small">
                                <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i><?= $stats['occupancy_rate'] ?>% đang thuê</span>
                                <span class="badge bg-primary-subtle text-primary rounded-pill"><?= (int) $stats['rooms']['available'] ?> phòng trống</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm stat-card glow-border-card stat-card-emerald h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="stat-label">Thực thu năm <?= (int) $year ?></div>
                                    <h3 class="stat-value text-success mb-0"><?= CoreHelper::formatCurrency($stats['total_collected']) ?></h3>
                                </div>
                                <div class="stat-icon-modern icon-emerald">
                                    <i class="bi bi-cash-stack"></i>
                                </div>
                            </div>
                            <div class="pt-2 border-top d-flex justify-content-between align-items-center small">
                                <span class="text-muted">Tổng phát sinh:</span>
                                <span class="fw-bold text-dark"><?= CoreHelper::formatCurrency($stats['total_billed']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm stat-card glow-border-card stat-card-rose h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="stat-label">Công nợ chưa thu</div>
                                    <h3 class="stat-value text-danger mb-0"><?= CoreHelper::formatCurrency($stats['total_unpaid']) ?></h3>
                                </div>
                                <div class="stat-icon-modern icon-rose">
                                    <i class="bi bi-exclamation-octagon-fill"></i>
                                </div>
                            </div>
                            <div class="pt-2 border-top d-flex justify-content-between align-items-center small">
                                <span class="text-danger fw-semibold"><i class="bi bi-clock-history me-1"></i>Hóa đơn tồn đọng:</span>
                                <span class="badge bg-danger-subtle text-danger rounded-pill"><?= (int) $stats['unpaid_invoice_count'] ?> hóa đơn</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm stat-card glow-border-card stat-card-cyan h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="stat-label">Khách &amp; Hợp đồng</div>
                                    <h3 class="stat-value mb-0"><?= (int) $stats['active_tenants'] ?> <span class="fs-6 text-muted fw-normal">khách lưu trú</span></h3>
                                </div>
                                <div class="stat-icon-modern icon-cyan">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                            </div>
                            <div class="pt-2 border-top d-flex justify-content-between align-items-center small">
                                <span class="text-muted">Hợp đồng hiệu lực:</span>
                                <span class="badge bg-info-subtle text-info-emphasis rounded-pill"><?= (int) $stats['active_contracts'] ?> hợp đồng</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm glow-border-card h-100">
                <div class="card-header bg-white py-2-5 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="header-icon-pill bg-success-subtle text-success"><i class="bi bi-pie-chart-fill"></i></span>
                        <h6 class="mb-0 fw-bold">Cơ cấu Trạng thái Phòng</h6>
                    </div>
                    <a href="<?= CoreHelper::url('/rooms') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5" style="font-size: 0.72rem;">
                        Quản lý phòng
                    </a>
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <div style="max-width: 170px; width: 100%; position: relative;">
                        <canvas id="roomStatusChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-around w-100 mt-3 pt-2 border-top small">
                        <div class="text-center">
                            <div class="text-muted mb-0.5"><span class="status-dot bg-success me-1"></span>Đang thuê</div>
                            <strong class="fs-6"><?= (int) $stats['rooms']['occupied'] ?></strong>
                        </div>
                        <div class="text-center">
                            <div class="text-muted mb-0.5"><span class="status-dot bg-primary me-1"></span>Phòng trống</div>
                            <strong class="fs-6"><?= (int) $stats['rooms']['available'] ?></strong>
                        </div>
                        <div class="text-center">
                            <div class="text-muted mb-0.5"><span class="status-dot bg-warning me-1"></span>Bảo trì</div>
                            <strong class="fs-6"><?= (int) $stats['rooms']['maintenance'] ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Thu Tiền Nhanh trực tiếp tại Bảng Hóa đơn Cần Thu Nợ -->
    <div class="modal fade" id="dashQuickPayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="<?= CoreHelper::url('/payments/store') ?>" class="modal-content">
                <?= CoreHelper::csrfField() ?>
                <input type="hidden" name="invoice_id" id="dqp_invoice_id">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i class="bi bi-cash-coin text-success me-2"></i>Thu tiền Hóa đơn <span id="dqp_invoice_code" class="text-primary"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="p-2.5 mb-3 rounded-3 bg-light border d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted">Phòng / Khách thuê</div>
                            <div class="fw-bold text-dark" id="dqp_room_tenant">—</div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Còn phải thu</div>
                            <div class="fw-bold text-danger fs-6" id="dqp_remaining_display">0 đ</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số tiền thực thu (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="dqp_amount" class="form-control fw-bold text-success" min="1000" required>
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
    function openDashQuickPay(id, code, room, tenant, remaining) {
        document.getElementById('dqp_invoice_id').value = id;
        document.getElementById('dqp_invoice_code').textContent = code;
        document.getElementById('dqp_room_tenant').textContent = 'Phòng ' + room + ' - ' + tenant;
        document.getElementById('dqp_remaining_display').textContent = new Intl.NumberFormat('vi-VN').format(remaining) + ' đ';
        document.getElementById('dqp_amount').value = remaining;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const roomStats = <?= json_encode($stats['rooms']) ?>;
        const statusCtx = document.getElementById('roomStatusChart');
        if (statusCtx && typeof Chart !== 'undefined') {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Đang thuê', 'Phòng trống', 'Đang bảo trì'],
                    datasets: [{
                        data: [roomStats.occupied, roomStats.available, roomStats.maintenance],
                        backgroundColor: ['#10b981', '#6366f1', '#f59e0b'],
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        hoverOffset: 8
                    }]
                },
                options: {
                    cutout: '72%',
                    plugins: { legend: { display: false } }
                }
            });
        }
    });
    </script>
<?php endif; ?>
