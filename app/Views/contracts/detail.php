<?php
use App\Helpers\CoreHelper;

$isAdminOrStaff = CoreHelper::hasRole(['admin', 'staff']);
$sigType = $contract['signature_type'] ?? 'online';
$sigStatus = $contract['signature_status'] ?? 'pending';
$depositDate = !empty($contract['deposit_date']) ? $contract['deposit_date'] : $contract['start_date'];
$isAdvanceDeposit = (strtotime($depositDate) < strtotime($contract['start_date']));
?>

<!-- Thanh công cụ trên cùng (Ẩn khi in giấy) -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 d-print-none">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $isAdminOrStaff ? CoreHelper::url('/contracts') : CoreHelper::url('/dashboard') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Quay lại
        </a>
        <?php if ($sigType === 'online'): ?>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5">
                <i class="bi bi-vector-pen me-1"></i>Hợp đồng Điện tử (Ký Online)
            </span>
        <?php else: ?>
            <span class="badge bg-dark-subtle text-dark border px-2.5 py-1.5">
                <i class="bi bi-file-earmark-text me-1"></i>Hợp đồng Ký giấy
            </span>
        <?php endif; ?>

        <?php if ($sigStatus === 'signed'): ?>
            <span class="badge bg-success px-2.5 py-1.5"><i class="bi bi-check-circle-fill me-1"></i>Đã ký xác nhận</span>
        <?php else: ?>
            <span class="badge bg-warning text-dark px-2.5 py-1.5"><i class="bi bi-clock me-1"></i>Chờ ký</span>
        <?php endif; ?>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#signOnlineModal">
            <i class="bi bi-pen-fill me-1"></i>Ký Điện tử (Online)
        </button>
        <button type="button" class="btn btn-sm btn-outline-dark" onclick="window.print()">
            <i class="bi bi-printer-fill me-1"></i>In Hợp đồng Giấy (A4)
        </button>
        <?php if ($isAdminOrStaff): ?>
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#uploadScanModal">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i><?= !empty($contract['scan_file']) ? 'Cập nhật Bản Scan' : 'Lưu Bản Scan (Ký giấy)' ?>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Khung hiển thị Bản Scan nếu đã tải lên (Ẩn khi in văn bản hợp đồng) -->
<?php if (!empty($contract['scan_file'])): ?>
<div class="card border-0 shadow-sm mb-3 d-print-none border-start border-success border-4">
    <div class="card-body py-2.5 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="badge bg-success me-2"><i class="bi bi-file-earmark-check-fill me-1"></i>Đã lưu Bản Scan Hợp đồng giấy</span>
            <span class="small text-muted">Cập nhật lúc: <?= !empty($contract['signed_at']) ? CoreHelper::formatDate($contract['signed_at'], 'd/m/Y H:i') : 'Mới đây' ?></span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= CoreHelper::asset($contract['scan_file']) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                <i class="bi bi-eye-fill me-1"></i>Mở xem Bản Scan gốc
            </a>
            <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="collapse" data-bs-target="#scanPreviewBox">
                Ẩn/Hiện ảnh Scan
            </button>
        </div>
    </div>
    <div class="collapse show px-3 pb-3" id="scanPreviewBox">
        <?php if (str_ends_with(strtolower($contract['scan_file']), '.pdf')): ?>
            <iframe src="<?= CoreHelper::asset($contract['scan_file']) ?>" style="width:100%; height:480px;" class="border rounded"></iframe>
        <?php else: ?>
            <div class="text-center bg-light p-2 rounded border">
                <img src="<?= CoreHelper::asset($contract['scan_file']) ?>" alt="Bản scan hợp đồng" class="img-fluid rounded" style="max-height: 520px;">
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- VĂN BẢN HỢP ĐỒNG THUÊ PHÒNG TRỌ (CHUẨN A4 ĐỂ XEM & IN) -->
<div class="card border-0 shadow-sm mx-auto invoice-print-box" style="max-width: 860px; color: #111;">
    <div class="card-body p-4 p-md-5" style="font-size: 0.94rem; line-height: 1.65;">
        <!-- Quốc hiệu & Tiêu ngữ -->
        <div class="text-center mb-4">
            <div class="fw-bold text-uppercase" style="font-size: 1rem;">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
            <div class="fw-semibold pb-1 d-inline-block border-bottom border-dark">Độc lập - Tự do - Hạnh phúc</div>
            <h4 class="fw-bold text-uppercase mt-3 mb-1">HỢP ĐỒNG THUÊ PHÒNG TRỌ</h4>
            <div class="small text-muted">
                Số hợp đồng: <strong><?= CoreHelper::e($contract['contract_code']) ?></strong>
                &bull; Hình thức: <strong><?= $sigType === 'online' ? 'Hợp đồng Điện tử' : 'Hợp đồng Ký giấy' ?></strong>
            </div>
        </div>

        <p class="mb-3">
            Hôm nay, ngày <strong><?= date('d', strtotime($depositDate)) ?></strong> tháng <strong><?= date('m', strtotime($depositDate)) ?></strong> năm <strong><?= date('Y', strtotime($depositDate)) ?></strong>, tại <strong><?= CoreHelper::e($contract['building_name'] ?? 'Hệ thống Nhà trọ Smart Stay') ?></strong>, chúng tôi gồm có:
        </p>

        <!-- BÊN A & BÊN B -->
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="p-3 rounded border bg-light bg-opacity-50 h-100">
                    <div class="fw-bold text-uppercase mb-1">I. BÊN CHO THUÊ PHÒNG (BÊN A):</div>
                    <div><strong>Đơn vị / Tòa nhà:</strong> <?= CoreHelper::e($contract['building_name'] ?? 'Smart Stay') ?></div>
                    <div><strong>Người đại diện:</strong> <?= CoreHelper::e($contract['manager_name'] ?: 'Ban Quản Lý Tòa Nhà') ?></div>
                    <div><strong>Số điện thoại:</strong> <?= CoreHelper::e($contract['manager_phone'] ?: '0901.234.567') ?></div>
                    <div><strong>Địa chỉ:</strong> <?= CoreHelper::e($contract['building_address'] ?: 'TP. Hồ Chí Minh') ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 rounded border bg-light bg-opacity-50 h-100">
                    <div class="fw-bold text-uppercase mb-1">II. BÊN THUÊ PHÒNG (BÊN B):</div>
                    <div><strong>Ông / Bà:</strong> <?= CoreHelper::e($contract['tenant_name']) ?></div>
                    <div><strong>Số CCCD/CMND:</strong> <?= CoreHelper::e($contract['tenant_cccd'] ?? '—') ?></div>
                    <div><strong>Số điện thoại:</strong> <?= CoreHelper::e($contract['tenant_phone']) ?></div>
                    <div><strong>Thường trú / Quê quán:</strong> <?= CoreHelper::e($contract['tenant_hometown'] ?: '—') ?></div>
                </div>
            </div>
        </div>

        <p class="mb-2">Hai bên thống nhất thỏa thuận và ký kết Hợp đồng thuê phòng trọ với các điều khoản cụ thể sau đây:</p>

        <!-- ĐIỀU 1 -->
        <div class="mb-3">
            <div class="fw-bold text-uppercase">ĐIỀU 1: ĐỐI TƯỢNG VÀ THỜI HẠN HỢP ĐỒNG</div>
            <div>1.1. Bên A đồng ý cho Bên B thuê <strong>Phòng số <?= CoreHelper::e($contract['room_number']) ?></strong> (Tầng <?= (int) ($contract['floor'] ?? 1) ?>, Diện tích <?= (float) ($contract['area'] ?? 20) ?> m&sup2;, Loại phòng: <?= CoreHelper::e($contract['room_type_name'] ?? 'Tiêu chuẩn') ?>) thuộc <strong><?= CoreHelper::e($contract['building_name'] ?? 'Tòa nhà Smart Stay') ?></strong>.</div>
            <div>1.2. Tiện nghi bàn giao kèm theo phòng: <?= CoreHelper::e($contract['amenities'] ?? 'Máy lạnh, Nước nóng, Wifi, WC riêng') ?>.</div>
            <?php
            $startTs = strtotime($contract['start_date']);
            $endTs = strtotime($contract['end_date']);
            $durationMonths = max(1, (int) round((date('Y', $endTs) - date('Y', $startTs)) * 12 + (date('n', $endTs) - date('n', $startTs))));
            ?>
            <div>
                1.3. <strong>Thời hạn có hiệu lực của hợp đồng:</strong> Hợp đồng có thời hạn <strong><?= $durationMonths ?> tháng</strong>, chính thức có hiệu lực bắt đầu từ ngày <strong><?= CoreHelper::formatDate($contract['start_date']) ?></strong> (mùng 01 đầu tháng) đến ngày <strong><?= CoreHelper::formatDate($contract['end_date']) ?></strong> (thời hạn hết hợp đồng được tính tròn <?= $durationMonths ?> tháng kể từ ngày mùng 01 bắt đầu hiệu lực hợp đồng).
            </div>
        </div>

        <!-- ĐIỀU 2 -->
        <div class="mb-3">
            <div class="fw-bold text-uppercase">ĐIỀU 2: GIÁ THUÊ PHÒNG, TIỀN ĐẶT CỌC VÀ QUY TẮC TÍNH TIỀN</div>
            <div>2.1. <strong>Giá thuê phòng cố định:</strong> <strong class="text-danger"><?= CoreHelper::formatCurrency($contract['monthly_rent']) ?> / tháng</strong>.</div>
            <div>
                2.2. <strong>Tiền đặt cọc giữ phòng &amp; đảm bảo hợp đồng:</strong> <strong><?= CoreHelper::formatCurrency($contract['deposit_amount']) ?></strong> (bằng đúng 01 tháng tiền thuê phòng).
                <ul class="mb-1 mt-1">
                    <li>
                        <strong>Ngày đặt cọc thực tế:</strong> Ghi nhận Bên B đã đặt cọc vào ngày <strong><?= CoreHelper::formatDate($depositDate) ?></strong>
                        <?= $isAdvanceDeposit ? '(Đặt cọc trước, hợp đồng chính thức tính hiệu lực từ ngày ' . CoreHelper::formatDate($contract['start_date']) . ')' : '(Đặt cọc và nhận phòng)' ?>.
                    </li>
                    <li>
                        <strong>Quy định tính tiền khi vào ở:</strong> Nếu Bên B vào ở từ ngày mùng 01 đến ngày 15 của tháng thì tính 100% tiền phòng tháng đó và phí dịch vụ như bình thường; nếu vào ở sau ngày 15 của tháng thì tính nửa tháng tiền phòng (50%) và phí dịch vụ như bình thường.
                    </li>
                    <li>
                        <strong>Quy định hoàn trả tiền cọc khi rời đi:</strong> Khi Bên B trả phòng sau khi đã hết hạn hợp đồng (từ ngày <?= CoreHelper::formatDate($contract['end_date']) ?> trở đi), Bên A hoàn trả lại 100% tiền cọc (<?= CoreHelper::formatCurrency($contract['deposit_amount']) ?>) sau khi đối trừ công nợ điện nước (nếu có). Trường hợp Bên B tự ý chấm dứt hợp đồng và rời đi trước khi hết hạn hợp đồng thì sẽ <strong>mất toàn bộ tiền đặt cọc</strong>.
                    </li>
                </ul>
            </div>
        </div>

        <!-- ĐIỀU 3 -->
        <div class="mb-3">
            <div class="fw-bold text-uppercase">ĐIỀU 3: CHI PHÍ ĐIỆN, NƯỚC, DỊCH VỤ VÀ PHƯƠNG THỨC THANH TOÁN</div>
            <div>3.1. Đơn giá áp dụng hàng tháng:
                <strong>Tiền điện:</strong> <?= CoreHelper::formatCurrency($contract['electricity_rate']) ?>/kWh &bull;
                <strong>Tiền nước:</strong> <?= CoreHelper::formatCurrency($contract['water_rate']) ?>/m&sup3; &bull;
                <strong>Phí dịch vụ chung:</strong> <?= CoreHelper::formatCurrency($contract['service_fee']) ?>/tháng.
            </div>
            <div>3.2. Tiền phòng và chi phí dịch vụ được thông báo qua Phiếu báo tiền trọ hàng tháng và thanh toán từ ngày 01 đến ngày 05 đầu mỗi tháng bằng tiền mặt hoặc chuyển khoản.</div>
        </div>

        <!-- ĐIỀU 4 -->
        <div class="mb-3">
            <div class="fw-bold text-uppercase">ĐIỀU 4: GIA HẠN TỰ ĐỘNG VÀ TRÁCH NHIỆM CỦA HAI BÊN</div>
            <div>4.1. <strong>Thông báo gia hạn trước 01 tháng:</strong> Trước ngày hết hạn hợp đồng 01 tháng (30 ngày), hệ thống tự động gửi thông báo trên Trang Tổng quan của Bên B. Nếu Bên B xác nhận <em>"Đồng ý ở tiếp"</em> thì hợp đồng tự động được gia hạn; nếu Bên B từ chối hoặc không phản hồi thì mặc định hợp đồng sẽ kết thúc đúng hạn và không gia hạn.</div>
            <div>4.2. Bên B có trách nhiệm giữ gìn tài sản trong phòng, chấp hành quy định an ninh trật tự, phòng cháy chữa cháy của tòa nhà và đăng ký tạm trú theo quy định.</div>
            <div>4.3. Hợp đồng này được lập dưới hình thức <strong>Hợp đồng Điện tử (ký trực tuyến)</strong> hoặc <strong>Hợp đồng Ký giấy (in ra ký trực tiếp và lưu bản Scan trên hệ thống)</strong>, có giá trị pháp lý ngang nhau.</div>
            <?php if (!empty($contract['notes'])): ?>
                <div class="mt-1"><strong>Ghi chú bổ sung:</strong> <?= CoreHelper::e($contract['notes']) ?></div>
            <?php endif; ?>
        </div>

        <!-- CHỮ KÝ HAI BÊN -->
        <div class="row mt-4 pt-3 text-center">
            <div class="col-6">
                <div class="fw-bold text-uppercase">ĐẠI DIỆN BÊN CHO THUÊ (BÊN A)</div>
                <div class="small text-muted fst-italic mb-2">(Ký và ghi rõ họ tên)</div>
                <div style="min-height: 100px;" class="d-flex align-items-center justify-content-center">
                    <?php if (!empty($contract['manager_signature'])): ?>
                        <img src="<?= $contract['manager_signature'] ?>" alt="Chữ ký Bên A" style="max-height: 90px; max-width: 220px;">
                    <?php else: ?>
                        <span class="text-muted small d-print-none">(Chưa có chữ ký điện tử Bên A)</span>
                    <?php endif; ?>
                </div>
                <div class="fw-bold mt-1"><?= CoreHelper::e($contract['manager_name'] ?: 'Đại diện Quản lý Tòa nhà') ?></div>
            </div>

            <div class="col-6">
                <div class="fw-bold text-uppercase">ĐẠI DIỆN BÊN THUÊ PHÒNG (BÊN B)</div>
                <div class="small text-muted fst-italic mb-2">(Ký và ghi rõ họ tên)</div>
                <div style="min-height: 100px;" class="d-flex align-items-center justify-content-center">
                    <?php if (!empty($contract['tenant_signature'])): ?>
                        <img src="<?= $contract['tenant_signature'] ?>" alt="Chữ ký Bên B" style="max-height: 90px; max-width: 220px;">
                    <?php else: ?>
                        <span class="text-muted small d-print-none">(Chưa có chữ ký điện tử Bên B)</span>
                    <?php endif; ?>
                </div>
                <div class="fw-bold mt-1"><?= CoreHelper::e($contract['tenant_name']) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 1: KÝ HỢP ĐỒNG ĐIỆN TỬ (ONLINE) -->
<div class="modal fade d-print-none" id="signOnlineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= CoreHelper::url('/contracts/sign-online') ?>" id="signOnlineForm" class="modal-content">
            <?= CoreHelper::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $contract['id'] ?>">
            <input type="hidden" name="manager_signature" id="manager_signature_input">
            <input type="hidden" name="tenant_signature" id="tenant_signature_input">

            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-vector-pen text-primary me-2"></i>Ký Hợp đồng Điện tử (Online) — <?= CoreHelper::e($contract['contract_code']) ?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <?php if ($isAdminOrStaff): ?>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold mb-0">Chữ ký Bên A (Chủ trọ / Quản lý)</label>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="clearPad('managerPad')">Xóa ký lại</button>
                        </div>
                        <div class="border rounded bg-light text-center p-1">
                            <canvas id="managerPad" width="320" height="140" class="bg-white rounded border w-100" style="touch-action: none; cursor: crosshair;"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="<?= $isAdminOrStaff ? 'col-md-6' : 'col-12' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold mb-0">Chữ ký Bên B (Khách thuê: <?= CoreHelper::e($contract['tenant_name']) ?>)</label>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="clearPad('tenantPad')">Xóa ký lại</button>
                        </div>
                        <div class="border rounded bg-light text-center p-1">
                            <canvas id="tenantPad" width="320" height="140" class="bg-white rounded border w-100" style="touch-action: none; cursor: crosshair;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="small text-muted mt-2">
                    <i class="bi bi-info-circle me-1"></i>Dùng chuột hoặc màn hình cảm ứng ký trực tiếp vào khung trắng ở trên, sau đó nhấn <strong>Lưu Chữ ký Điện tử</strong>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-sm btn-primary px-3">
                    <i class="bi bi-check2-circle me-1"></i>Lưu Chữ ký Điện tử
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: LƯU BẢN SCAN HỢP ĐỒNG KÝ GIẤY -->
<?php if ($isAdminOrStaff): ?>
<div class="modal fade d-print-none" id="uploadScanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= CoreHelper::url('/contracts/upload-scan') ?>" enctype="multipart/form-data" class="modal-content">
            <?= CoreHelper::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $contract['id'] ?>">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-cloud-arrow-up-fill text-success me-2"></i>Lưu Bản Scan Hợp đồng Ký giấy</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    Sau khi in hợp đồng giấy ra ký trực tiếp, bạn hãy tải ảnh chụp hoặc file PDF bản Scan lên đây để lưu trữ vào hồ sơ hợp đồng <strong><?= CoreHelper::e($contract['contract_code']) ?></strong>.
                </p>
                <div class="mb-2">
                    <label class="form-label fw-semibold small">Chọn file Bản Scan (Ảnh JPG, PNG hoặc PDF) *</label>
                    <input type="file" name="scan_file" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-sm btn-success px-3">Tải lên &amp; Lưu Bản Scan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
const padState = {};

function initPad(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    ctx.strokeStyle = '#1d4ed8';
    ctx.lineWidth = 2.2;
    ctx.lineCap = 'round';
    padState[canvasId] = { drawing: false, dirty: false, canvas, ctx };

    const getPos = (e) => {
        const rect = canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return {
            x: (clientX - rect.left) * (canvas.width / rect.width),
            y: (clientY - rect.top) * (canvas.height / rect.height)
        };
    };

    const start = (e) => {
        e.preventDefault();
        const pos = getPos(e);
        padState[canvasId].drawing = true;
        padState[canvasId].dirty = true;
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
    };
    const move = (e) => {
        if (!padState[canvasId].drawing) return;
        e.preventDefault();
        const pos = getPos(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
    };
    const end = () => { padState[canvasId].drawing = false; };

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', end);
}

function clearPad(canvasId) {
    const st = padState[canvasId];
    if (!st) return;
    st.ctx.clearRect(0, 0, st.canvas.width, st.canvas.height);
    st.dirty = false;
}

document.addEventListener('DOMContentLoaded', function () {
    initPad('managerPad');
    initPad('tenantPad');

    const form = document.getElementById('signOnlineForm');
    if (form) {
        form.addEventListener('submit', function () {
            if (padState['managerPad'] && padState['managerPad'].dirty) {
                document.getElementById('manager_signature_input').value = padState['managerPad'].canvas.toDataURL('image/png');
            }
            if (padState['tenantPad'] && padState['tenantPad'].dirty) {
                document.getElementById('tenant_signature_input').value = padState['tenantPad'].canvas.toDataURL('image/png');
            }
        });
    }
});
</script>
