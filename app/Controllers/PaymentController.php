<?php

namespace App\Controllers;

use App\Helpers\CoreHelper;
use App\Helpers\Validator;
use App\Models\InvoiceModel;
use App\Models\PaymentModel;

class PaymentController
{
    private PaymentModel $paymentModel;
    private InvoiceModel $invoiceModel;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
        $this->invoiceModel = new InvoiceModel();
    }

    public function index(): void
    {
        $filters = [
            'payment_method' => $_GET['payment_method'] ?? '',
            'keyword'        => $_GET['keyword'] ?? '',
        ];

        $payments = $this->paymentModel->getAll($filters);
        $unpaidInvoices = $this->invoiceModel->getUnpaidInvoices();

        CoreHelper::view('payments/index', [
            'pageTitle'      => 'Quản lý Thu tiền & Lịch sử Thanh toán',
            'payments'       => $payments,
            'unpaidInvoices' => $unpaidInvoices,
            'filters'        => $filters,
        ]);
    }

    public function store(): void
    {
        $validator = Validator::make($_POST, [
            'invoice_id'     => 'required|integer|min:1',
            'amount'         => 'required|numeric|min:1000',
            'payment_method' => 'required|in:cash,bank_transfer,momo,vnpay',
        ], [
            'invoice_id'     => 'Hóa đơn',
            'amount'         => 'Số tiền thu',
            'payment_method' => 'Phương thức thanh toán',
        ]);

        if ($validator->fails()) {
            CoreHelper::setFlash('danger', $validator->firstError());
            CoreHelper::redirect('/payments');
        }

        $invoiceId = (int) $_POST['invoice_id'];
        $invoice = $this->invoiceModel->findById($invoiceId);
        if (!$invoice) {
            CoreHelper::setFlash('danger', 'Không tìm thấy hóa đơn cần thanh toán.');
            CoreHelper::redirect('/payments');
        }

        $user = CoreHelper::user();
        $this->paymentModel->create([
            'invoice_id'      => $invoiceId,
            'amount'          => (float) $_POST['amount'],
            'payment_method'  => $_POST['payment_method'],
            'transaction_ref' => $_POST['transaction_ref'] ?? null,
            'payment_date'    => !empty($_POST['payment_date']) ? $_POST['payment_date'] . ' ' . date('H:i:s') : date('Y-m-d H:i:s'),
            'received_by'     => $user['id'] ?? null,
            'notes'           => $_POST['notes'] ?? '',
        ]);

        CoreHelper::setFlash('success', 'Đã ghi nhận thanh toán cho hóa đơn ' . $invoice['invoice_code'] . '.');

        if (!empty($_POST['redirect_to_detail'])) {
            CoreHelper::redirect('/invoices/detail', ['id' => $invoiceId]);
        }

        CoreHelper::redirect('/payments');
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($this->paymentModel->delete($id)) {
            CoreHelper::setFlash('success', 'Đã hủy phiếu thu và cập nhật lại công nợ hóa đơn.');
        } else {
            CoreHelper::setFlash('danger', 'Không tìm thấy phiếu thu cần xóa.');
        }
        CoreHelper::redirect('/payments');
    }
}
