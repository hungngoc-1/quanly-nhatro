<?php

namespace App\Controllers;

use App\Helpers\CoreHelper;
use App\Models\BookingModel;
use App\Models\RoomModel;

class PublicController
{
    private RoomModel $roomModel;
    private BookingModel $bookingModel;

    public function __construct()
    {
        $this->roomModel = new RoomModel();
        $this->bookingModel = new BookingModel();
    }

    /**
     * Trang công khai giới thiệu nhà trọ, danh sách phòng trống & trải nghiệm xem phòng 360 độ
     */
    public function index(): void
    {
        $filters = [
            'floor'   => $_GET['floor'] ?? '',
            'status'  => $_GET['status'] ?? 'available',
            'keyword' => $_GET['keyword'] ?? '',
        ];

        $queryFilters = $filters;
        if ($queryFilters['status'] === 'all') {
            $queryFilters['status'] = '';
        }

        $rooms = $this->roomModel->getAll($queryFilters);

        $priceRange = $_GET['price_range'] ?? '';
        if ($priceRange !== '') {
            $rooms = array_values(array_filter($rooms, function (array $r) use ($priceRange): bool {
                $price = (float) $r['price'];
                return match ($priceRange) {
                    'under_3m' => $price < 3000000,
                    '3m_to_35m' => $price >= 3000000 && $price <= 3500000,
                    'over_35m' => $price > 3500000,
                    default => true,
                };
            }));
        }

        $allRooms = $this->roomModel->getAll();
        $availableRooms = array_values(array_filter($allRooms, fn(array $r) => $r['status'] === 'available'));
        $counts = $this->roomModel->getCountsByStatus();

        CoreHelper::view('public/showcase', [
            'pageTitle'      => 'Khám phá & Thuê Phòng Trọ 360° - SMART STAY',
            'rooms'          => $rooms,
            'allRooms'       => $allRooms,
            'availableRooms' => $availableRooms,
            'counts'         => $counts,
            'filters'        => [
                'floor'       => $filters['floor'],
                'status'      => $filters['status'],
                'keyword'     => $filters['keyword'],
                'price_range' => $priceRange,
            ],
        ], false);
    }

    /**
     * Xử lý gửi yêu cầu đặt lịch xem phòng / giữ phòng từ khách vãng lai (Lưu vào CSDL để thông báo cho Chủ trọ)
     */
    public function bookRoom(): void
    {
        $roomNumber = trim((string) ($_POST['room_number'] ?? ''));
        $fullName   = trim((string) ($_POST['full_name'] ?? ''));
        $phone      = trim((string) ($_POST['phone'] ?? ''));
        $moveInDate = trim((string) ($_POST['move_in_date'] ?? ''));
        $notes      = trim((string) ($_POST['notes'] ?? ''));

        if ($fullName === '' || $phone === '') {
            CoreHelper::setFlash('danger', 'Vui lòng nhập đầy đủ Họ tên và Số điện thoại liên hệ.');
            CoreHelper::redirect('/cho-thue');
        }

        $this->bookingModel->create([
            'room_number'  => $roomNumber,
            'full_name'    => $fullName,
            'phone'        => $phone,
            'move_in_date' => $moveInDate,
            'notes'        => $notes,
        ]);

        $dateStr = $moveInDate !== '' ? CoreHelper::formatDate($moveInDate) : 'Sớm nhất';
        CoreHelper::setFlash(
            'success',
            "Đăng ký giữ chỗ & hẹn xem Phòng {$roomNumber} thành công! Yêu cầu của {$fullName} ({$phone}, dự kiến vào ở: {$dateStr}) đã được gửi tới Ban quản lý."
        );
        CoreHelper::redirect('/cho-thue');
    }

    /**
     * Trang Lịch sử các yêu cầu đặt trước / giữ phòng đã xử lý (Đã liên hệ / Đã hủy)
     */
    public function bookingHistory(): void
    {
        $historyBookings = $this->bookingModel->getHistory(100);
        $pendingCount = $this->bookingModel->getPendingCount();

        CoreHelper::view('rooms/booking_history', [
            'pageTitle'       => 'Lịch sử Yêu cầu Đặt trước & Giữ phòng',
            'historyBookings' => $historyBookings,
            'pendingCount'    => $pendingCount,
        ]);
    }

    /**
     * Cập nhật trạng thái yêu cầu giữ phòng (Dành cho Admin / Nhân viên)
     */
    public function updateBookingStatus(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? 'contacted'));
        if ($id > 0) {
            $this->bookingModel->updateStatus($id, $status);
            if ($status === 'contacted') {
                CoreHelper::setFlash('success', 'Đã xử lý xong! Yêu cầu đặt phòng đã được chuyển vào trang Lịch sử.');
            } elseif ($status === 'pending') {
                CoreHelper::setFlash('info', 'Đã khôi phục yêu cầu về danh sách Chờ liên hệ.');
            } else {
                CoreHelper::setFlash('success', 'Đã cập nhật trạng thái yêu cầu giữ phòng.');
            }
        }
        CoreHelper::redirect($_SERVER['HTTP_REFERER'] ?? '/rooms');
    }

    /**
     * Xóa yêu cầu giữ phòng
     */
    public function deleteBooking(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->bookingModel->delete($id);
            CoreHelper::setFlash('success', 'Đã xóa yêu cầu giữ phòng.');
        }
        CoreHelper::redirect($_SERVER['HTTP_REFERER'] ?? '/rooms');
    }
}
