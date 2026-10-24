<?php

use App\Helpers\CoreHelper;

?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 d-print-none">
    <form method="GET" action="<?= CoreHelper::url('/reports') ?>" class="d-flex gap-2 align-items-center">
        <label class="fw-semibold small text-muted">Chọn năm báo cáo:</label>
        <select name="year" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= (int) $year === $y ? 'selected' : '' ?>>Năm <?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>

    <div class="d-flex gap-2">
        <a href="<?= CoreHelper::url('/reports/export', ['year' => $year]) ?>" class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Xuất Excel (CSV)
        </a>
        <button type="button" class="btn btn-sm btn-primary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> In Báo cáo
        </button>
    </div>
</div>

<!-- Tổng hợp chỉ số tài chính năm -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body">
                <div class="small text-white-50">TỔNG DOANH THU PHÁT SINH (<?= (int) $year ?>)</div>
                <h3 class="fw-bold mb-0 mt-1"><?= CoreHelper::formatCurrency($stats['total_billed']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body">
                <div class="small text-white-50">TỔNG THỰC THU ĐÃ NHẬN (<?= (int) $year ?>)</div>
                <h3 class="fw-bold mb-0 mt-1"><?= CoreHelper::formatCurrency($stats['total_collected']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-danger text-white">
            <div class="card-body">
                <div class="small text-white-50">TỔNG CÔNG NỢ CÒN PHẢI THU</div>
                <h3 class="fw-bold mb-0 mt-1"><?= CoreHelper::formatCurrency($stats['total_unpaid']) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Bảng chi tiết doanh thu 12 tháng -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-table text-primary me-2"></i>Bảng Tổng hợp Doanh thu theo Tháng - Năm <?= (int) $year ?></h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0 small">
                <thead class="table-light text-center">
                    <tr>
                        <th>Tháng</th>
                        <th>Tiền phòng</th>
                        <th>Tiền điện</th>
                        <th>Tiền nước</th>
                        <th>Phí dịch vụ</th>
                        <th>Tổng phát sinh</th>
                        <th>Đã thu</th>
                        <th>Còn nợ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sumRoom = $sumElec = $sumWater = $sumService = $sumBilled = $sumCollected = $sumDebt = 0;
                    foreach ($monthlyRevenue as $row):
                        $sumRoom += $row['room_revenue'];
                        $sumElec += $row['electric_revenue'];
                        $sumWater += $row['water_revenue'];
                        $sumService += $row['service_revenue'];
                        $sumBilled += $row['total_billed'];
                        $sumCollected += $row['total_collected'];
                        $sumDebt += $row['total_debt'];
                    ?>
                        <tr class="text-end">
                            <td class="text-center fw-semibold">Tháng <?= (int) $row['month'] ?>/<?= (int) $year ?></td>
                            <td><?= CoreHelper::formatCurrency($row['room_revenue']) ?></td>
                            <td><?= CoreHelper::formatCurrency($row['electric_revenue']) ?></td>
                            <td><?= CoreHelper::formatCurrency($row['water_revenue']) ?></td>
                            <td><?= CoreHelper::formatCurrency($row['service_revenue']) ?></td>
                            <td class="fw-bold text-primary"><?= CoreHelper::formatCurrency($row['total_billed']) ?></td>
                            <td class="fw-bold text-success"><?= CoreHelper::formatCurrency($row['total_collected']) ?></td>
                            <td class="fw-bold <?= $row['total_debt'] > 0 ? 'text-danger' : 'text-muted' ?>"><?= CoreHelper::formatCurrency($row['total_debt']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light fw-bold text-end">
                    <tr>
                        <td class="text-center">TỔNG CẢ NĂM</td>
                        <td><?= CoreHelper::formatCurrency($sumRoom) ?></td>
                        <td><?= CoreHelper::formatCurrency($sumElec) ?></td>
                        <td><?= CoreHelper::formatCurrency($sumWater) ?></td>
                        <td><?= CoreHelper::formatCurrency($sumService) ?></td>
                        <td class="text-primary"><?= CoreHelper::formatCurrency($sumBilled) ?></td>
                        <td class="text-success"><?= CoreHelper::formatCurrency($sumCollected) ?></td>
                        <td class="text-danger"><?= CoreHelper::formatCurrency($sumDebt) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
