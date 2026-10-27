/**
 * Hệ thống Quản lý Nhà trọ - Main Javascript (v5.0)
 */
document.addEventListener('DOMContentLoaded', function () {
    // 1. Đưa tất cả Bootstrap Modal ra trực tiếp dưới <body> để tránh bị lớp phủ .modal-backdrop che khuất
    document.querySelectorAll('.modal').forEach(function (modalEl) {
        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }
    });

    // 2. Khởi tạo tính tổng hóa đơn nếu đang ở trang lập hóa đơn
    if (document.getElementById('invoicePreviewTotal')) {
        previewInvoiceTotal();
    }
});

/**
 * Định dạng số tiền sang chuỗi VNĐ
 */
function formatVnd(amount) {
    const num = Number(amount) || 0;
    return new Intl.NumberFormat('vi-VN').format(Math.round(num)) + ' đ';
}

/**
 * Tự động lấy chỉ số điện nước cũ khi chọn phòng trong Modal Điện Nước
 */
function fetchLatestUtilityForRoom(roomId) {
    if (!roomId) return;
    const base = window.APP_BASE_URL || '';
    fetch(base + '/api/room-info?room_id=' + encodeURIComponent(roomId))
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            if (data.latest_utility) {
                const oldElec = document.getElementById('old_electric');
                const newElec = document.getElementById('new_electric');
                const oldWater = document.getElementById('old_water');
                const newWater = document.getElementById('new_water');

                if (oldElec) oldElec.value = data.latest_utility.new_electric || 0;
                if (newElec) newElec.value = data.latest_utility.new_electric || 0;
                if (oldWater) oldWater.value = data.latest_utility.new_water || 0;
                if (newWater) newWater.value = data.latest_utility.new_water || 0;
            }
            if (data.active_contract) {
                const eRate = document.getElementById('electric_rate');
                const wRate = document.getElementById('water_rate');
                const oFee = document.getElementById('other_fee');
                if (eRate && data.active_contract.electricity_rate) eRate.value = data.active_contract.electricity_rate;
                if (wRate && data.active_contract.water_rate) wRate.value = data.active_contract.water_rate;
                if (oFee && data.active_contract.service_fee) oFee.value = data.active_contract.service_fee;
            }
            previewUtilityCost();
        })
        .catch(() => {});
}

/**
 * Tính nhẩm tiền Điện + Nước + Dịch vụ trên form Chốt chỉ số
 */
function previewUtilityCost() {
    const oldE = Number(document.getElementById('old_electric')?.value || 0);
    const newE = Number(document.getElementById('new_electric')?.value || 0);
    const rateE = Number(document.getElementById('electric_rate')?.value || 3500);

    const oldW = Number(document.getElementById('old_water')?.value || 0);
    const newW = Number(document.getElementById('new_water')?.value || 0);
    const rateW = Number(document.getElementById('water_rate')?.value || 15000);

    const otherFee = Number(document.getElementById('other_fee')?.value || 0);

    const usedE = Math.max(0, newE - oldE);
    const usedW = Math.max(0, newW - oldW);
    const total = usedE * rateE + usedW * rateW + otherFee;

    const el = document.getElementById('utilityPreviewTotal');
    if (el) {
        el.innerText = formatVnd(total);
    }
}

/**
 * Tự động lấy tiền phòng và tiền điện nước khi chọn Hợp đồng trên form Lập Hóa đơn
 */
function onInvoiceContractChange(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    if (!opt || !opt.value) return;

    const roomId = opt.dataset.roomId;
    const rent = Number(opt.dataset.rent || 0);
    const service = Number(opt.dataset.service || 150000);
    const month = document.getElementById('inv_month')?.value || '';
    const year = document.getElementById('inv_year')?.value || '';

    document.getElementById('inv_room_fee').value = rent;
    document.getElementById('inv_service_fee').value = service;

    const base = window.APP_BASE_URL || '';
    fetch(`${base}/api/room-info?room_id=${encodeURIComponent(roomId)}&month=${encodeURIComponent(month)}&year=${encodeURIComponent(year)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.period_utility) {
                document.getElementById('inv_electric_fee').value = data.period_utility.electric_amount || 0;
                document.getElementById('inv_water_fee').value = data.period_utility.water_amount || 0;
                document.getElementById('inv_service_fee').value = data.period_utility.other_fee || service;
            }
            previewInvoiceTotal();
        })
        .catch(() => {
            previewInvoiceTotal();
        });
}

/**
 * Tính nhẩm tổng tiền Hóa đơn trực tiếp trên giao diện
 */
function previewInvoiceTotal() {
    const roomFee = Number(document.getElementById('inv_room_fee')?.value || 0);
    const elecFee = Number(document.getElementById('inv_electric_fee')?.value || 0);
    const waterFee = Number(document.getElementById('inv_water_fee')?.value || 0);
    const serviceFee = Number(document.getElementById('inv_service_fee')?.value || 0);
    const discount = Number(document.getElementById('inv_discount')?.value || 0);

    const total = Math.max(0, roomFee + elecFee + waterFee + serviceFee - discount);
    const el = document.getElementById('invoicePreviewTotal');
    if (el) {
        el.innerText = formatVnd(total);
    }
}
