<?php

use App\Helpers\CoreHelper;

?>
<?php if (!empty($isTenant)): ?>
    <!-- Giao diện Dashboard dành cho Khách thuê -->
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm glow-border-card h-100">
                <div class="card-header bg-gradient-primary text-white fw-bold py-3">
                    <i class="bi bi-house-heart-fill me-2"></i>Thông tin Phòng đang thuê
                </div>
                <div class="card-body">
                    <?php if (!empty($tenantInfo) && !empty($tenantInfo['room_number'])): ?>
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                            <div>
                                <div class="text-muted small">Phòng đang ở</div>
                                <h3 class="fw-extrabold text-primary mb-0"><?= CoreHelper::e($tenantInfo['room_number']) ?> <span class="fs-6 fw-medium text-muted">(Tầng <?= (int) $tenantInfo['floor'] ?>)</span></h3>
                            </div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
                                <span class="status-dot bg-success me-1"></span>Đang lưu trú
                            </span>
                        </div>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between px-0 py-2-5">
                                <span class="text-muted"><i class="bi bi-person me-2"></i>Họ và tên khách:</span>
                                <strong><?= CoreHelper::e($tenantInfo['full_name']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2-5">
                                <span class="text-muted"><i class="bi bi-file-earmark-text me-2"></i>Mã hợp đồng:</span>
                                <strong class="text-primary"><?= CoreHelper::e($tenantInfo['contract_code']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2-5">
                                <span class="text-muted"><i class="bi bi-tag me-2"></i>Giá thuê hàng tháng:</span>
                                <strong class="text-danger fs-6"><?= CoreHelper::formatCurrency($tenantInfo['monthly_rent']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2-5">
                                <span class="text-muted"><i class="bi bi-shield-check me-2"></i>Tiền đặt cọc:</span>
                                <strong><?= CoreHelper::formatCurrency($tenantInfo['deposit_amount']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2-5">
                                <span class="text-muted"><i class="bi bi-calendar-range me-2"></i>Thời hạn hợp đồng:</span>
                                <strong><?= CoreHelper::formatDate($tenantInfo['start_date']) ?> &rarr; <?= CoreHelper::formatDate($tenantInfo['end_date']) ?></strong>
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
    <!-- Giao diện Dashboard dành cho Chủ trọ / Quản lý -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
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

        <div class="col-sm-6 col-xl-3">
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

        <div class="col-sm-6 col-xl-3">
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

        <div class="col-sm-6 col-xl-3">
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

    <!-- Biểu đồ Doanh thu & Trạng thái phòng -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm glow-border-card h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="header-icon-pill bg-primary-subtle text-primary"><i class="bi bi-bar-chart-fill"></i></span>
                        <h6 class="mb-0 fw-bold">Biểu đồ Doanh thu &amp; Thực thu Năm <?= (int) $year ?></h6>
                    </div>
                    <a href="<?= CoreHelper::url('/reports') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        Báo cáo chi tiết <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="110"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm glow-border-card h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                    <span class="header-icon-pill bg-success-subtle text-success"><i class="bi bi-pie-chart-fill"></i></span>
                    <h6 class="mb-0 fw-bold">Cơ cấu Trạng thái Phòng</h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <div style="max-width: 220px; width: 100%; position: relative;">
                        <canvas id="roomStatusChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-around w-100 mt-4 pt-3 border-top small">
                        <div class="text-center">
                            <div class="text-muted mb-1"><span class="status-dot bg-success me-1"></span>Đang thuê</div>
                            <strong class="fs-6"><?= (int) $stats['rooms']['occupied'] ?></strong>
                        </div>
                        <div class="text-center">
                            <div class="text-muted mb-1"><span class="status-dot bg-primary me-1"></span>Phòng trống</div>
                            <strong class="fs-6"><?= (int) $stats['rooms']['available'] ?></strong>
                        </div>
                        <div class="text-center">
                            <div class="text-muted mb-1"><span class="status-dot bg-warning me-1"></span>Bảo trì</div>
                            <strong class="fs-6"><?= (int) $stats['rooms']['maintenance'] ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách Hóa đơn chưa thanh toán & Hợp đồng sắp hết hạn -->
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm glow-border-card h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="header-icon-pill bg-danger-subtle text-danger"><i class="bi bi-receipt-cutoff"></i></span>
                        <h6 class="mb-0 fw-bold text-dark">Hóa đơn Chưa thu / Còn nợ</h6>
                    </div>
                    <a href="<?= CoreHelper::url('/invoices/create') ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i class="bi bi-plus-lg me-1"></i>Lập hóa đơn
                    </a>
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
                                    <th class="text-end">Thao tác</th>
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
                                                <a href="<?= CoreHelper::url('/invoices/detail', ['id' => $inv['id']]) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                    Chi tiết / Thu
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

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm glow-border-card h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="header-icon-pill bg-warning-subtle text-warning-emphasis"><i class="bi bi-calendar-event"></i></span>
                        <h6 class="mb-0 fw-bold text-dark">Hợp đồng Sắp hết hạn (60 ngày)</h6>
                    </div>
                    <a href="<?= CoreHelper::url('/contracts') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Quản lý HĐ</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Phòng</th>
                                    <th>Khách thuê</th>
                                    <th>Ngày hết hạn</th>
                                    <th>Còn lại</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($expiringContracts)): ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">Không có hợp đồng nào sắp hết hạn trong 60 ngày tới.</td></tr>
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

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const monthlyData = <?= json_encode($monthlyRevenue) ?>;
        const roomStats = <?= json_encode($stats['rooms']) ?>;

        const revCtx = document.getElementById('revenueChart');
        if (revCtx && typeof Chart !== 'undefined') {
            const ctx = revCtx.getContext('2d');
            const gradBilled = ctx.createLinearGradient(0, 0, 0, 280);
            gradBilled.addColorStop(0, 'rgba(99, 102, 241, 0.35)');
            gradBilled.addColorStop(1, 'rgba(99, 102, 241, 0.05)');

            const gradCollected = ctx.createLinearGradient(0, 0, 0, 280);
            gradCollected.addColorStop(0, 'rgba(16, 185, 129, 0.9)');
            gradCollected.addColorStop(1, 'rgba(5, 150, 105, 0.65)');

            new Chart(revCtx, {
                type: 'bar',
                data: {
                    labels: monthlyData.map(item => 'T' + item.month),
                    datasets: [
                        {
                            label: 'Tổng phát sinh (VNĐ)',
                            data: monthlyData.map(item => item.total_billed),
                            backgroundColor: gradBilled,
                            borderColor: '#6366f1',
                            borderWidth: 1.5,
                            borderRadius: 8,
                            barPercentage: 0.7
                        },
                        {
                            label: 'Đã thu (VNĐ)',
                            data: monthlyData.map(item => item.total_collected),
                            backgroundColor: gradCollected,
                            borderRadius: 8,
                            barPercentage: 0.7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { usePointStyle: true, boxWidth: 8, font: { family: "'Be Vietnam Pro', sans-serif", weight: '600' } }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: "'Be Vietnam Pro', sans-serif" } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.15)', borderDash: [4, 4] },
                            ticks: {
                                font: { family: "'Be Vietnam Pro', sans-serif" },
                                callback: function(val) {
                                    return new Intl.NumberFormat('vi-VN').format(val) + ' đ';
                                }
                            }
                        }
                    }
                }
            });
        }

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
