<?php

use App\Helpers\CoreHelper;

$isAdminOrStaff = CoreHelper::hasRole(['admin', 'staff']);
?>
<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
    <a href="<?= $isAdminOrStaff ? CoreHelper::url('/invoices/create') : CoreHelper::url('/invoices/my-list') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách
    </a>
    <div class="d-flex gap-2">
        <?php if ($isAdminOrStaff && (float) $invoice['remaining_amount'] > 0): ?>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#quickPayModal">
                <i class="bi bi-cash-coin me-1"></i> Ghi nhận Thu tiền
            </button>
        <?php endif; ?>
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
            <i class="bi bi-printer-fill me-1"></i> In Phiếu Báo Tiền Trọ
        </button>
    </div>
</div>

<!-- Khung Phiếu Báo Tiền Trọ (Chuẩn in A4/A5) -->
<div class="card border-0 shadow-sm invoice-print-box mx-auto" style="max-width: 850px;">
    <div class="card-body p-4 p-md-5">
        <div class="row border-bottom pb-3 mb-4 align-items-center">
            <div class="col-sm-7">
                <h4 class="fw-bold text-primary mb-1">NHÀ TRỌ THÔNG MINH</h4>
                <div class="small text-muted">Hệ thống Quản lý Phòng trọ - Điện Nước - Hóa đơn</div>
                <div class="small text-muted">Hotline Quản lý: 0901.234.567</div>
            </div>
            <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
                <h5 class="fw-bold text-uppercase mb-1">PHIẾU BÁO TIỀN TRỌ</h5>
                <div class="small">Mã HĐ: <strong><?= CoreHelper::e($invoice['invoice_code']) ?></strong></div>
                <div class="small">Kỳ thanh toán: <strong>Tháng <?= (int) $invoice['month'] ?>/<?= (int) $invoice['year'] ?></strong></div>
            </div>
        </div>

        <div class="row mb-4 small">
            <div class="col-sm-6">
                <div class="mb-1">Phòng trọ: <strong class="fs-6 text-dark"><?= CoreHelper::e($invoice['room_number']) ?></strong> (Tầng <?= (int) $invoice['floor'] ?>)</div>
                <div class="mb-1">Khách đại diện: <strong><?= CoreHelper::e($invoice['tenant_name']) ?></strong></div>
                <div>Số điện thoại: <?= CoreHelper::e($invoice['tenant_phone']) ?></div>
            </div>
            <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                <div class="mb-1">Hợp đồng số: <strong><?= CoreHelper::e($invoice['contract_code']) ?></strong></div>
                <div class="mb-1">Hạn thanh toán: <strong class="text-danger"><?= CoreHelper::formatDate($invoice['due_date']) ?></strong></div>
                <div>
                    Trạng thái:
                    <?php if ($invoice['status'] === 'paid'): ?>
                        <span class="badge bg-success">ĐÃ THANH TOÁN ĐỦ</span>
                    <?php elseif ($invoice['status'] === 'partial'): ?>
                        <span class="badge bg-warning text-dark">ĐÃ ĐÓNG 1 PHẦN</span>
                    <?php elseif ($invoice['status'] === 'overdue'): ?>
                        <span class="badge bg-danger">QUÁ HẠN THANH TOÁN</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">CHƯA THANH TOÁN</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bảng chi tiết các khoản thu -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">STT</th>
                        <th>Khoản mục thu</th>
                        <th>Chi tiết chỉ số / Diễn giải</th>
                        <th class="text-end" style="width: 170px;">Thành tiền (VNĐ)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center">1</td>
                        <td class="fw-semibold">Tiền thuê phòng cố định</td>
                        <td class="small text-muted">Tiền phòng tháng <?= (int) $invoice['month'] ?>/<?= (int) $invoice['year'] ?></td>
                        <td class="text-end fw-semibold"><?= CoreHelper::formatCurrency($invoice['room_fee']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-center">2</td>
                        <td class="fw-semibold">Tiền điện sinh hoạt</td>
                        <td class="small text-muted">
                            <?php if ($invoice['old_electric'] !== null): ?>
                                Cũ: <?= (int) $invoice['old_electric'] ?> &rarr; Mới: <?= (int) $invoice['new_electric'] ?>
                                (Tiêu thụ: <strong><?= (int) $invoice['electric_used'] ?> kWh</strong> &times; <?= CoreHelper::formatCurrency($invoice['electric_rate']) ?>)
                            <?php else: ?>
                                Theo chỉ số điện tiêu thụ trong tháng
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-semibold"><?= CoreHelper::formatCurrency($invoice['electric_fee']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-center">3</td>
                        <td class="fw-semibold">Tiền nước sinh hoạt</td>
                        <td class="small text-muted">
                            <?php if ($invoice['old_water'] !== null): ?>
                                Cũ: <?= (int) $invoice['old_water'] ?> &rarr; Mới: <?= (int) $invoice['new_water'] ?>
                                (Tiêu thụ: <strong><?= (int) $invoice['water_used'] ?> m&sup3;</strong> &times; <?= CoreHelper::formatCurrency($invoice['water_rate']) ?>)
                            <?php else: ?>
                                Theo chỉ số nước tiêu thụ trong tháng
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-semibold"><?= CoreHelper::formatCurrency($invoice['water_fee']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-center">4</td>
                        <td class="fw-semibold">Phí dịch vụ chung</td>
                        <td class="small text-muted">Internet Wifi, vệ sinh rác, an ninh chung</td>
                        <td class="text-end fw-semibold"><?= CoreHelper::formatCurrency($invoice['service_fee']) ?></td>
                    </tr>
                    <?php $stt = 5; ?>
                    <?php if ((float) ($invoice['deposit_fee'] ?? 0) > 0 || ($invoice['invoice_type'] ?? '') === 'move_in'): ?>
                    <tr class="table-info bg-opacity-25">
                        <td class="text-center"><?= $stt++ ?></td>
                        <td class="fw-bold text-primary">Tiền đặt cọc khi vào ở (Bằng 1 tháng tiền phòng)</td>
                        <td class="small text-muted">Khoản tiền cọc đảm bảo hợp đồng (Hoàn lại khi kết thúc hợp đồng đúng hạn)</td>
                        <td class="text-end fw-bold text-primary">+ <?= CoreHelper::formatCurrency($invoice['deposit_fee'] ?? 0) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ((float) ($invoice['deposit_refund'] ?? 0) > 0 || ($invoice['invoice_type'] ?? '') === 'move_out'): ?>
                    <tr class="<?= (float) ($invoice['deposit_refund'] ?? 0) > 0 ? 'table-success' : 'table-warning' ?> bg-opacity-25">
                        <td class="text-center"><?= $stt++ ?></td>
                        <td class="fw-bold <?= (float) ($invoice['deposit_refund'] ?? 0) > 0 ? 'text-success' : 'text-danger' ?>">
                            Quyết toán Tiền đặt cọc khi rời đi
                        </td>
                        <td class="small text-muted">
                            <?php if ((float) ($invoice['deposit_refund'] ?? 0) > 0): ?>
                                Khách rời đi sau khi hết hạn hợp đồng &rarr; Được hoàn lại 100% tiền cọc
                            <?php else: ?>
                                Khách rời đi trước khi hết hạn hợp đồng &rarr; Mất tiền cọc (<?= CoreHelper::formatCurrency($invoice['contract_deposit_amount'] ?? 0) ?>)
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-bold <?= (float) ($invoice['deposit_refund'] ?? 0) > 0 ? 'text-success' : 'text-danger' ?>">
                            - <?= CoreHelper::formatCurrency($invoice['deposit_refund'] ?? 0) ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if ((float) $invoice['discount'] > 0): ?>
                    <tr>
                        <td class="text-center"><?= $stt++ ?></td>
                        <td class="fw-semibold text-success">Khuyến mãi / Giảm trừ</td>
                        <td class="small text-muted">Giảm trừ hỗ trợ khách thuê</td>
                        <td class="text-end fw-semibold text-success">- <?= CoreHelper::formatCurrency($invoice['discount']) ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <th colspan="3" class="text-end">TỔNG CỘNG PHẢI THU:</th>
                        <th class="text-end fs-5 text-primary"><?= CoreHelper::formatCurrency($invoice['total_amount']) ?></th>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end text-success">Đã thanh toán:</th>
                        <th class="text-end text-success"><?= CoreHelper::formatCurrency($invoice['paid_amount']) ?></th>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end text-danger">SỐ TIỀN CÒN PHẢI ĐÓNG:</th>
                        <th class="text-end fs-5 text-danger"><?= CoreHelper::formatCurrency($invoice['remaining_amount']) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if (!empty($invoice['notes'])): ?>
            <div class="alert alert-light border small mb-4">
                <strong>Ghi chú:</strong> <?= CoreHelper::e($invoice['notes']) ?>
            </div>
        <?php endif; ?>

        <!-- Lịch sử các lần đóng tiền của hóa đơn này -->
        <?php if (!empty($payments)): ?>
            <div class="mb-4">
                <h6 class="fw-bold small text-uppercase text-muted mb-2">Lịch sử giao dịch thanh toán</h6>
                <table class="table table-sm table-bordered small mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mã giao dịch</th>
                            <th>Ngày thu</th>
                            <th>Hình thức</th>
                            <th>Ghi chú</th>
                            <th class="text-end">Số tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?= CoreHelper::e($p['payment_code']) ?></td>
                                <td><?= CoreHelper::formatDate($p['payment_date'], 'd/m/Y H:i') ?></td>
                                <td><?= CoreHelper::e(strtoupper($p['payment_method'])) ?></td>
                                <td><?= CoreHelper::e($p['notes']) ?></td>
                                <td class="text-end fw-bold text-success"><?= CoreHelper::formatCurrency($p['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="row mt-5 pt-3 text-center small">
            <div class="col-6">
                <div class="fw-bold">NGƯỜI THU TIỀN / CHỦ TRỌ</div>
                <div class="text-muted fst-italic">(Ký và ghi rõ họ tên)</div>
            </div>
            <div class="col-6">
                <div class="fw-bold">KHÁCH THUÊ PHÒNG</div>
                <div class="text-muted fst-italic">(Ký và ghi rõ họ tên)</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ghi nhận Thu tiền nhanh -->
<?php if ($isAdminOrStaff && (float) $invoice['remaining_amount'] > 0): ?>
<div class="modal fade d-print-none" id="quickPayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= CoreHelper::url('/payments/store') ?>" class="modal-content">
            <?= CoreHelper::csrfField() ?>
            <input type="hidden" name="invoice_id" value="<?= (int) $invoice['id'] ?>">
            <input type="hidden" name="redirect_to_detail" value="1">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Ghi nhận Thanh toán - <?= CoreHelper::e($invoice['invoice_code']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Số tiền thu (VNĐ) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control fw-bold text-danger" value="<?= (float) $invoice['remaining_amount'] ?>" min="1000" max="<?= (float) $invoice['remaining_amount'] ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Hình thức thanh toán</label>
                    <select name="payment_method" class="form-select">
                        <option value="bank_transfer">Chuyển khoản Ngân hàng</option>
                        <option value="cash">Tiền mặt</option>
                        <option value="momo">Ví MoMo</option>
                        <option value="vnpay">VNPay</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mã tham chiếu / Ngân hàng</label>
                    <input type="text" name="transaction_ref" class="form-control" placeholder="VD: VCB998877">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Ghi chú</label>
                    <input type="text" name="notes" class="form-control" value="Thu tiền phòng <?= CoreHelper::e($invoice['room_number']) ?> kỳ T<?= (int) $invoice['month'] ?>/<?= (int) $invoice['year'] ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-success">Xác nhận Thu tiền</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
