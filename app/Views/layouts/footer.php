<?php

use App\Helpers\CoreHelper;
?>
        </main>

        <!-- Footer -->
        <footer class="modern-footer d-print-none mt-auto">
            <div class="app-container">
                <div class="footer-glass-card d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="footer-badge"><i class="bi bi-buildings-fill"></i></span>
                        <span>&copy; <?= date('Y') ?> <strong>SMART STAY - Quản lý Nhà trọ Chuyên nghiệp</strong></span>
                    </div>
                    <div class="text-muted small d-flex align-items-center gap-3">
                        <span><i class="bi bi-shield-check text-success me-1"></i>WAMP MVC Architecture</span>
                        <span class="d-none d-sm-inline">&bull;</span>
                        <span class="d-none d-sm-inline"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Tự động hóa Điện, Nước &amp; Hóa đơn</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= CoreHelper::asset('js/main.js') ?>?v=5.0"></script>
</body>
</html>
