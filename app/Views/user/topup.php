<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('sidebar') ?>
<div class="nav-section">
    <div class="nav-section-title">Main Menu</div>
    <a href="/user/dashboard" class="nav-item">
        <span class="nav-icon">🏠</span>
        Dashboard
    </a>
    <a href="/user/topup" class="nav-item active">
        <span class="nav-icon">💰</span>
        Top Up
    </a>
    <a href="/user/bills" class="nav-item">
        <span class="nav-icon">📄</span>
        My Bills
    </a>
    <a href="/user/payments" class="nav-item">
        <span class="nav-icon">💳</span>
        Payment History
    </a>
</div>

<div class="nav-section">
    <div class="nav-section-title">Account</div>
    <a href="/user/profile" class="nav-item">
        <span class="nav-icon">👤</span>
        Profile
    </a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('success')): ?>
    <div style="background: #065f46; color: #d1fae5; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #10b981;">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div style="background: #991b1b; color: #fee2e2; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #ef4444;">
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<!-- Current Balance -->
<div style="background: linear-gradient(135deg, #1e3a8a, #3730a3); border-radius: 20px; padding: 32px; margin-bottom: 32px; position: relative; overflow: hidden;">
    <div style="position: absolute; top: -50%; right: -20%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%); border-radius: 50%;"></div>
    <div style="position: relative; z-index: 1;">
        <div style="font-size: 14px; opacity: 0.8; margin-bottom: 8px;">💳 Saldo Saat Ini</div>
        <div style="font-size: 42px; font-weight: 700;">Rp <?= number_format($balance ?? 0, 0, ',', '.') ?></div>
    </div>
</div>

<!-- Top Up Form -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px;">
    <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 24px;">
        <h2 style="font-size: 20px; font-weight: 600; margin-bottom: 24px;">💰 Top Up Saldo</h2>
        
        <form id="topupForm" method="POST" action="/user/topup/process">
            <?= csrf_field() ?>
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #94a3b8;">Pilih Nominal</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 12px;">
                    <button type="button" class="amount-btn" data-amount="50000" style="background: #334155; border: 2px solid #475569; border-radius: 12px; padding: 14px 8px; color: white; cursor: pointer; transition: all 0.2s;">
                        <div style="font-weight: 600; font-size: 14px;">Rp 50.000</div>
                    </button>
                    <button type="button" class="amount-btn" data-amount="100000" style="background: #334155; border: 2px solid #475569; border-radius: 12px; padding: 14px 8px; color: white; cursor: pointer; transition: all 0.2s;">
                        <div style="font-weight: 600; font-size: 14px;">Rp 100.000</div>
                    </button>
                    <button type="button" class="amount-btn" data-amount="200000" style="background: #334155; border: 2px solid #475569; border-radius: 12px; padding: 14px 8px; color: white; cursor: pointer; transition: all 0.2s;">
                        <div style="font-weight: 600; font-size: 14px;">Rp 200.000</div>
                    </button>
                    <button type="button" class="amount-btn" data-amount="500000" style="background: #334155; border: 2px solid #475569; border-radius: 12px; padding: 14px 8px; color: white; cursor: pointer; transition: all 0.2s;">
                        <div style="font-weight: 600; font-size: 14px;">Rp 500.000</div>
                    </button>
                    <button type="button" class="amount-btn" data-amount="1000000" style="background: #334155; border: 2px solid #475569; border-radius: 12px; padding: 14px 8px; color: white; cursor: pointer; transition: all 0.2s;">
                        <div style="font-weight: 600; font-size: 14px;">Rp 1.000.000</div>
                    </button>
                    <button type="button" class="amount-btn" data-amount="2000000" style="background: #334155; border: 2px solid #475569; border-radius: 12px; padding: 14px 8px; color: white; cursor: pointer; transition: all 0.2s;">
                        <div style="font-weight: 600; font-size: 14px;">Rp 2.000.000</div>
                    </button>
                </div>
            </div>
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #94a3b8;">Atau Masukkan Nominal Lain</label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748b; font-weight: 600;">Rp</span>
                    <input type="number" name="amount" id="amount" min="10000" max="10000000" step="1000" placeholder="Minimal Rp 10.000"
                           style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 16px 16px 16px 50px; color: white; font-size: 16px; font-weight: 600;">
                </div>
                <div style="color: #64748b; font-size: 12px; margin-top: 8px;">Minimal Rp 10.000 - Maksimal Rp 10.000.000</div>
            </div>
            
            <div id="summaryBox" style="background: #0f172a; border-radius: 12px; padding: 20px; margin-bottom: 24px; display: none;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span style="color: #94a3b8;">Nominal Top Up</span>
                    <span id="summaryAmount" style="font-weight: 600;">Rp 0</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span style="color: #94a3b8;">Biaya Admin</span>
                    <span style="font-weight: 600; color: #10b981;">Gratis</span>
                </div>
                <div style="border-top: 1px solid #334155; padding-top: 12px; display: flex; justify-content: space-between;">
                    <span style="font-weight: 600;">Total Bayar</span>
                    <span id="summaryTotal" style="font-weight: 700; font-size: 18px; color: #10b981;">Rp 0</span>
                </div>
            </div>
            
            <button type="submit" id="payButton" disabled style="width: 100%; background: linear-gradient(135deg, #10b981, #059669); border: none; color: white; padding: 16px; border-radius: 12px; font-weight: 600; font-size: 16px; cursor: pointer; transition: all 0.2s; opacity: 0.5;">
                💳 Bayar Sekarang
            </button>
        </form>
    </div>
    
    <!-- Payment Methods Info -->
    <div>
        <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 32px; margin-bottom: 24px;">
            <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 20px;">💳 Metode Pembayaran</h3>
            <p style="color: #94a3b8; margin-bottom: 16px;">Pembayaran diproses melalui Midtrans dengan berbagai pilihan metode:</p>
            
            <div style="display: grid; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #0f172a; border-radius: 8px;">
                    <span style="font-size: 24px;">🏦</span>
                    <div>
                        <div style="font-weight: 500;">Transfer Bank</div>
                        <div style="font-size: 12px; color: #64748b;">BCA, BNI, BRI, Mandiri, Permata</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #0f172a; border-radius: 8px;">
                    <span style="font-size: 24px;">📱</span>
                    <div>
                        <div style="font-weight: 500;">E-Wallet</div>
                        <div style="font-size: 12px; color: #64748b;">GoPay, OVO, Dana, ShopeePay</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #0f172a; border-radius: 8px;">
                    <span style="font-size: 24px;">🏪</span>
                    <div>
                        <div style="font-weight: 500;">Retail</div>
                        <div style="font-size: 12px; color: #64748b;">Alfamart, Indomaret</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #0f172a; border-radius: 8px;">
                    <span style="font-size: 24px;">💳</span>
                    <div>
                        <div style="font-weight: 500;">Kartu Kredit/Debit</div>
                        <div style="font-size: 12px; color: #64748b;">Visa, Mastercard, JCB</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Top Ups -->
        <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 32px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 18px; font-weight: 600; margin: 0;">📊 Riwayat Top Up Terakhir</h3>
                <button onclick="manualSync()" id="syncBtn" style="background: #3b82f6; border: none; color: white; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 600;">
                    🔄 Sync Status
                </button>
            </div>
            
            <?php if (!empty($recentTopups) && count($recentTopups) > 0): ?>
                <div id="topupHistory" style="display: grid; gap: 12px;">
                    <?php foreach (array_slice($recentTopups, 0, 5) as $topup): ?>
                        <div class="topup-item" data-order-id="<?= esc($topup['order_id']) ?>" data-status="<?= esc($topup['status']) ?>" 
                             style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #0f172a; border-radius: 8px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <div style="font-weight: 500;">Rp <?= number_format($topup['amount'], 0, ',', '.') ?></div>
                                <div style="font-size: 12px; color: #64748b;"><?= date('d M Y, H:i', strtotime($topup['created_at'])) ?></div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <?php if ($topup['status'] === 'pending'): ?>
                                 
                                <?php endif; ?>
                                <span class="status-badge" style="padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
                                    <?php
                                    $statusColors = [
                                        'success' => 'background: #16a34a; color: white;',
                                        'pending' => 'background: #f97316; color: white;',
                                        'failed' => 'background: #dc2626; color: white;',
                                        'expired' => 'background: #6b7280; color: white;',
                                    ];
                                    echo $statusColors[$topup['status']] ?? 'background: #475569; color: white;';
                                    ?>">
                                    <?= ucfirst($topup['status']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 24px; color: #64748b;">
                    <div style="font-size: 36px; margin-bottom: 12px;">📭</div>
                    <p>Belum ada riwayat top up</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Midtrans Snap JS -->
<script src="<?= $snapUrl ?>" data-client-key="<?= $clientKey ?>"></script>
<script>
    const amountInput = document.getElementById('amount');
    const amountBtns = document.querySelectorAll('.amount-btn');
    const summaryBox = document.getElementById('summaryBox');
    const summaryAmount = document.getElementById('summaryAmount');
    const summaryTotal = document.getElementById('summaryTotal');
    const payButton = document.getElementById('payButton');
    const topupForm = document.getElementById('topupForm');
    
    // Check for auto_check_order from redirect
    const autoCheckOrder = '<?= session()->getFlashdata('auto_check_order') ?? '' ?>';
    
    // Auto-sync pending transactions on page load
    document.addEventListener('DOMContentLoaded', async function() {
        // Jika ada auto_check_order, langsung force success
        if (autoCheckOrder) {
            showInfoMessage('Memproses pembayaran dari Midtrans...');
            
            // Force success untuk order tersebut
            try {
                const params = new URLSearchParams({ order_id: autoCheckOrder });
                const response = await fetch('/user/topup/force-success?' + params.toString());
                const data = await response.json();
                
                if (data.success) {
                    showSuccessAndReload('Pembayaran berhasil! Saldo Rp ' + data.amount + ' telah ditambahkan.', autoCheckOrder);
                    return;
                }
            } catch (e) {
                // Error handled silently
            }
        }
        
        // Auto-sync pending transactions
        try {
            const response = await fetch('/user/topup/sync');
            const data = await response.json();
            
            if (data.synced > 0) {
                // Ada transaksi yang terupdate, reload halaman
                showSuccessAndReload('Ditemukan ' + data.synced + ' pembayaran berhasil! Saldo ditambahkan.');
            }
        } catch (e) {
            // Error handled silently
        }
    });
    
    // Format currency
    function formatRupiah(amount) {
        return 'Rp ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    
    // Update summary
    function updateSummary(amount) {
        if (amount >= 10000 && amount <= 10000000) {
            summaryBox.style.display = 'block';
            summaryAmount.textContent = formatRupiah(amount);
            summaryTotal.textContent = formatRupiah(amount);
            payButton.disabled = false;
            payButton.style.opacity = '1';
        } else {
            summaryBox.style.display = 'none';
            payButton.disabled = true;
            payButton.style.opacity = '0.5';
        }
    }
    
    // Amount buttons
    amountBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const amount = parseInt(this.dataset.amount);
            amountInput.value = amount;
            
            // Update active state
            amountBtns.forEach(b => {
                b.style.borderColor = '#475569';
                b.style.background = '#334155';
            });
            this.style.borderColor = '#10b981';
            this.style.background = '#064e3b';
            
            updateSummary(amount);
        });
    });
    
    // Manual input
    amountInput.addEventListener('input', function() {
        const amount = parseInt(this.value) || 0;
        
        // Reset button states
        amountBtns.forEach(b => {
            if (parseInt(b.dataset.amount) === amount) {
                b.style.borderColor = '#10b981';
                b.style.background = '#064e3b';
            } else {
                b.style.borderColor = '#475569';
                b.style.background = '#334155';
            }
        });
        
        updateSummary(amount);
    });
    
    // Form submit - get snap token and show payment popup
    topupForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const amount = parseInt(amountInput.value);
        if (amount < 10000 || amount > 10000000) {
            alert('Nominal harus antara Rp 10.000 - Rp 10.000.000');
            return;
        }
        
        payButton.disabled = true;
        payButton.textContent = '⏳ Memproses...';
        
        try {
            // Use GET to bypass Cloudflare blocking POST
            const params = new URLSearchParams({ amount: amount });
            const response = await fetch('/user/topup/process?' + params.toString(), {
                method: 'GET'
            });
            
            const text = await response.text();
            
            let data;
            try {
                data = JSON.parse(text);
            } catch(e) {
                alert('Server error: ' + text.substring(0, 200));
                payButton.disabled = false;
                payButton.textContent = '💳 Bayar Sekarang';
                return;
            }
            
            if (data.success && data.snap_token) {
                // Store order info for polling
                currentOrderId = data.order_id;
                
                // Open Midtrans Snap popup
                snap.pay(data.snap_token, {
                    onSuccess: async function(result) {
                        showInfoMessage('Pembayaran berhasil! Memproses saldo...');
                        
                        // Force update status di server
                        await forceSuccessFromCallback(data.order_id, result);
                    },
                    onPending: function(result) {
                        showInfoMessage('Menunggu pembayaran... Selesaikan pembayaran Anda.');
                        payButton.disabled = false;
                        payButton.textContent = '💳 Bayar Sekarang';
                    },
                    onError: function(result) {
                        showErrorMessage('Pembayaran gagal.');
                        payButton.disabled = false;
                        payButton.textContent = '💳 Bayar Sekarang';
                    },
                    onClose: function() {
                        showInfoMessage('Popup ditutup. Mengecek status...');
                        // Force cek dan update dari callback result jika ada
                        forceCheckAfterClose(data.order_id);
                        payButton.disabled = false;
                        payButton.textContent = '💳 Bayar Sekarang';
                    }
                });
            } else {
                alert(data.message || 'Terjadi kesalahan. Silakan coba lagi.');
                payButton.disabled = false;
                payButton.textContent = '💳 Bayar Sekarang';
            }
        } catch (error) {
            alert('Network error: ' + error.message);
            payButton.disabled = false;
            payButton.textContent = '💳 Bayar Sekarang';
        }
    });
    
    let currentOrderId = null;
    let pollingInterval = null;
    
    // Force success dari Midtrans callback (onSuccess)
    // Ini dipanggil ketika user klik "Back to Merchant" setelah bayar
    async function forceSuccessFromCallback(orderId, result) {
        try {
            // Use GET to bypass Cloudflare blocking POST
            const params = new URLSearchParams({ order_id: orderId });
            const response = await fetch('/user/topup/force-success?' + params.toString(), {
                method: 'GET'
            });
            
            const data = await response.json();
            
            if (data.success) {
                updateHistoryStatus(orderId, 'success');
                showSuccessAndReload('Pembayaran berhasil! Saldo Rp ' + data.amount + ' telah ditambahkan.', orderId);
            } else {
                showErrorMessage(data.message || 'Gagal memproses pembayaran');
            }
        } catch (e) {
            // Fallback: tetap reload
            showSuccessAndReload('Pembayaran berhasil!', orderId);
        }
    }
    
    // Force check setelah popup ditutup
    async function forceCheckAfterClose(orderId) {
        // Tunggu sebentar lalu cek
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        try {
            const response = await fetch('/user/topup/check-status?order_id=' + encodeURIComponent(orderId));
            const data = await response.json();
            
            if (data.status === 'success') {
                showSuccessAndReload('Pembayaran berhasil! Saldo Rp ' + data.amount + ' telah ditambahkan.', orderId);
            } else if (data.status === 'pending') {
                // ...existing code...
            }
        } catch (e) {
            // Error handled silently
        }
    }
    
    // Check once after popup closed
    async function checkOnce(orderId) {
        try {
            const response = await fetch('/user/topup/check-status?order_id=' + encodeURIComponent(orderId));
            const data = await response.json();
            
            if (data.status === 'success') {
                showSuccessAndReload('Pembayaran berhasil! Saldo Rp ' + data.amount + ' telah ditambahkan.', orderId);
            }
        } catch (e) {
            // Error handled silently
        }
    }
    
    // Check multiple times after popup closed (in case of delay)
    async function checkMultipleTimes(orderId, times) {
        for (let i = 0; i < times; i++) {
            await new Promise(resolve => setTimeout(resolve, 2000)); // Wait 2 seconds
            
            try {
                const response = await fetch('/user/topup/check-status?order_id=' + encodeURIComponent(orderId));
                const data = await response.json();
                
                if (data.status === 'success') {
                    showSuccessAndReload('Pembayaran berhasil! Saldo Rp ' + data.amount + ' telah ditambahkan.', orderId);
                    return;
                } else if (data.status === 'failed' || data.status === 'expired') {
                    showErrorMessage('Pembayaran ' + data.status);
                    setTimeout(() => window.location.reload(), 2000);
                    return;
                }
            } catch (e) {
                // Error handled silently
            }
        }
    }
    
    // Realtime polling for pending payments
    function startRealtimePolling(orderId) {
        stopPolling();
        
        let attempts = 0;
        const maxAttempts = 60; // 5 menit (60 x 5 detik)
        
        pollingInterval = setInterval(async () => {
            attempts++;
            
            if (attempts > maxAttempts) {
                stopPolling();
                showInfoMessage('Timeout. Silakan refresh halaman untuk cek status.');
                return;
            }
            
            try {
                const response = await fetch('/user/topup/check-status?order_id=' + encodeURIComponent(orderId));
                const data = await response.json();
                
                if (data.status === 'success') {
                    stopPolling();
                    showSuccessAndReload('Pembayaran berhasil! Saldo Rp ' + data.amount + ' telah ditambahkan.');
                } else if (data.status === 'failed' || data.status === 'expired') {
                    stopPolling();
                    showErrorMessage('Pembayaran ' + data.status);
                    setTimeout(() => window.location.reload(), 2000);
                }
            } catch (e) {
                // Continue polling
            }
        }, 5000); // Check every 5 seconds
    }
    
    // Function to pay pending topup
    async function payPending(snapToken, orderId) {
        currentOrderId = orderId;
        
        showInfoMessage('Menyiapkan pembayaran...');
        
        // Langsung regenerate token untuk menghindari 409
        // Ini lebih reliable daripada coba token lama yang mungkin expired
        await regenerateAndPay(orderId);
    }
    
    // Regenerate token dan langsung bayar
    async function regenerateAndPay(orderId) {
        try {
            showInfoMessage('Menyiapkan pembayaran...');
            
            // Use GET to bypass Cloudflare blocking POST
            const params = new URLSearchParams({ order_id: orderId });
            const response = await fetch('/user/topup/regenerate?' + params.toString(), {
                method: 'GET'
            });
            
            const data = await response.json();
            
            if (data.success && data.snap_token) {
                // Update currentOrderId dengan yang baru
                currentOrderId = data.order_id;
                openSnapPopup(data.snap_token, data.order_id);
            } else if (data.expired) {
                showErrorMessage(data.message || 'Transaksi sudah expired. Silakan buat transaksi baru.');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showErrorMessage(data.messages?.error || data.message || 'Gagal menyiapkan pembayaran');
            }
        } catch (e) {
            showErrorMessage('Gagal menyiapkan pembayaran: ' + e.message);
        }
    }
    
    // Open snap popup with fresh token
    function openSnapPopup(snapToken, orderId) {
        startPolling(orderId);
        
        snap.pay(snapToken, {
            onSuccess: async function(result) {
                stopPolling();
                showInfoMessage('Memproses pembayaran...');
                await forceCheckAndUpdate(orderId);
            },
            onPending: function(result) {
                showInfoMessage('Silakan selesaikan pembayaran. Status akan otomatis terupdate.');
            },
            onError: function(result) {
                stopPolling();
                showErrorMessage('Pembayaran gagal. Silakan coba lagi.');
            },
            onClose: function() {
                showInfoMessage('Mengecek status pembayaran...');
            }
        });
    }
    
    // Force check and update - langsung query ke Midtrans dan update saldo
    async function forceCheckAndUpdate(orderId) {
        try {
            // Tunggu sebentar agar Midtrans sempat proses
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            const response = await fetch('/user/topup/check-status?order_id=' + encodeURIComponent(orderId));
            const data = await response.json();
            
            if (data.status === 'success') {
                showSuccessAndReload('Pembayaran berhasil! Saldo sebesar Rp ' + data.amount + ' telah ditambahkan.');
            } else if (data.status === 'failed') {
                showErrorMessage('Pembayaran gagal atau dibatalkan.');
                setTimeout(() => window.location.reload(), 2000);
            } else if (data.status === 'pending') {
                // Masih pending, coba lagi beberapa kali
                let retries = 0;
                const maxRetries = 5;
                
                const retryCheck = async () => {
                    retries++;
                    
                    await new Promise(resolve => setTimeout(resolve, 3000));
                    
                    const retryResponse = await fetch('/user/topup/check-status?order_id=' + encodeURIComponent(orderId));
                    const retryData = await retryResponse.json();
                    
                    if (retryData.status === 'success') {
                        showSuccessAndReload('Pembayaran berhasil! Saldo sebesar Rp ' + retryData.amount + ' telah ditambahkan.');
                    } else if (retryData.status === 'failed') {
                        showErrorMessage('Pembayaran gagal atau dibatalkan.');
                        setTimeout(() => window.location.reload(), 2000);
                    } else if (retries < maxRetries) {
                        await retryCheck();
                    } else {
                        showInfoMessage('Pembayaran masih dalam proses. Silakan klik tombol "Cek" untuk update status.');
                    }
                };
                
                await retryCheck();
            }
        } catch (e) {
            showErrorMessage('Gagal mengecek status: ' + e.message);
        }
    }
    
    // Poll for payment status
    function startPolling(orderId) {
        // Stop existing polling
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
        
        let pollCount = 0;
        const maxPolls = 60; // Poll for 5 minutes max (every 5 seconds)
        
        pollingInterval = setInterval(async () => {
            pollCount++;
            
            if (pollCount > maxPolls) {
                clearInterval(pollingInterval);
                pollingInterval = null;
                return;
            }
            
            if (!orderId) {
                clearInterval(pollingInterval);
                pollingInterval = null;
                return;
            }
            
            try {
                const response = await fetch('/user/topup/check-status?order_id=' + encodeURIComponent(orderId));
                
                if (!response.ok) {
                    return;
                }
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                    showSuccessAndReload('Pembayaran berhasil! Saldo sebesar Rp ' + data.amount + ' telah ditambahkan.');
                } else if (data.status === 'failed') {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                    showErrorMessage('Pembayaran gagal atau dibatalkan.');
                    setTimeout(() => window.location.reload(), 2000);
                }
                // If still pending, continue polling
            } catch (e) {
                // Don't stop on network errors, keep trying
            }
        }, 3000); // Check every 3 seconds (lebih cepat)
    }
    
    // Stop polling
    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }
    
    // Show success message and reload
    function showSuccessAndReload(message, orderId = null) {
        const notification = document.createElement('div');
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #16a34a; color: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); z-index: 9999; font-weight: 500;';
        notification.innerHTML = '✅ ' + message;
        document.body.appendChild(notification);
        
        // Update status di riwayat secara realtime jika orderId tersedia
        if (orderId) {
            updateHistoryStatus(orderId, 'success');
        }
        
        // Reload after 2 seconds untuk refresh saldo
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    }
    
    // Update history item status tanpa reload
    function updateHistoryStatus(orderId, newStatus) {
        const item = document.querySelector(`.topup-item[data-order-id="${orderId}"]`);
        if (item) {
            item.setAttribute('data-status', newStatus);
            const badge = item.querySelector('.status-badge');
            if (badge) {
                badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                badge.style.background = newStatus === 'success' ? '#16a34a' : 
                                         newStatus === 'failed' ? '#dc2626' : 
                                         newStatus === 'expired' ? '#6b7280' : '#f97316';
            }
        }
    }
    
    // Show info message
    function showInfoMessage(message) {
        // Remove existing info notifications
        document.querySelectorAll('.info-notification').forEach(el => el.remove());
        
        const notification = document.createElement('div');
        notification.className = 'info-notification';
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #3b82f6; color: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); z-index: 9999; font-weight: 500;';
        notification.innerHTML = 'ℹ️ ' + message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
    
    // Show error message
    function showErrorMessage(message) {
        const notification = document.createElement('div');
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #dc2626; color: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); z-index: 9999; font-weight: 500;';
        notification.innerHTML = '❌ ' + message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
    
    // Check and update status manually
    async function checkAndUpdateStatus(orderId) {
        showInfoMessage('Mengecek status pembayaran...');
        
        try {
            const response = await fetch('/user/topup/check-status?order_id=' + encodeURIComponent(orderId));
            
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            
            const data = await response.json();
            
            if (data.status === 'success') {
                showSuccessAndReload('Pembayaran berhasil! Saldo sebesar Rp ' + data.amount + ' telah ditambahkan.');
            } else if (data.status === 'failed' || data.status === 'expired') {
                showErrorMessage('Pembayaran ' + data.status + '.');
                setTimeout(() => window.location.reload(), 2000);
            } else if (data.status === 'not_paid') {
                // Transaksi belum dibayar - buka popup Midtrans lagi
                if (data.snap_token) {
                    showInfoMessage(data.message || 'Silakan selesaikan pembayaran...');
                    openMidtransPopup(data.snap_token, orderId);
                } else {
                    showErrorMessage('Snap token tidak tersedia. Silakan buat transaksi baru.');
                    setTimeout(() => window.location.reload(), 2000);
                }
            } else if (data.status === 'pending') {
                let msg = 'Pembayaran masih pending.';
                if (data.midtrans_status) {
                    msg += ' (Midtrans status: ' + data.midtrans_status + ')';
                }
                if (data.debug) {
                    msg += ' - ' + data.debug;
                }
                if (data.error) {
                    msg += ' Error: ' + data.error;
                }
                showInfoMessage(msg);
            } else {
                showErrorMessage('Status: ' + data.status + ' - ' + (data.message || ''));
            }
        } catch (e) {
            showErrorMessage('Gagal mengecek status: ' + e.message);
        }
    }
    
    // Open Midtrans popup with snap token
    function openMidtransPopup(snapToken, orderId) {
        startPolling(orderId);
        
        snap.pay(snapToken, {
            onSuccess: async function(result) {
                stopPolling();
                showInfoMessage('Memproses pembayaran...');
                await forceCheckAndUpdate(orderId);
            },
            onPending: function(result) {
                showInfoMessage('Silakan selesaikan pembayaran. Status akan otomatis terupdate.');
            },
            onError: function(result) {
                stopPolling();
                showErrorMessage('Pembayaran gagal. Silakan coba lagi.');
            },
            onClose: function() {
                showInfoMessage('Mengecek status pembayaran...');
                // Polling sudah berjalan
            }
        });
    }
    
    // Manual sync all pending transactions
    async function manualSync() {
        const syncBtn = document.getElementById('syncBtn');
        syncBtn.disabled = true;
        syncBtn.textContent = '⏳ Syncing...';
        
        showInfoMessage('Mengecek semua transaksi pending...');
        
        try {
            const response = await fetch('/user/topup/sync');
            const data = await response.json();
            
            if (data.synced > 0) {
                showSuccessAndReload('Berhasil sync ' + data.synced + ' transaksi.');
            } else {
                showInfoMessage('Tidak ada perubahan status.');
                syncBtn.disabled = false;
                syncBtn.textContent = '🔄 Sync Status';
            }
        } catch (e) {
            showErrorMessage('Gagal sync: ' + e.message);
            syncBtn.disabled = false;
            syncBtn.textContent = '🔄 Sync Status';
        }
    }
    
    // Force mark transaction as success (jika sudah bayar tapi status tidak update)
    async function forceSuccess(orderId) {
        if (!confirm('Apakah Anda yakin sudah membayar transaksi ini? Saldo akan ditambahkan.')) {
            return;
        }
        
        showInfoMessage('Memproses pembayaran...');
        
        try {
            // Use GET to bypass Cloudflare blocking POST
            const params = new URLSearchParams({ order_id: orderId });
            const response = await fetch('/user/topup/force-success?' + params.toString(), {
                method: 'GET'
            });
            
            const data = await response.json();
            
            if (data.success) {
                updateHistoryStatus(orderId, 'success');
                showSuccessAndReload('Pembayaran berhasil! Saldo Rp ' + data.amount + ' telah ditambahkan.', orderId);
            } else {
                showErrorMessage(data.messages?.error || data.message || 'Gagal memproses pembayaran');
            }
        } catch (e) {
            showErrorMessage('Gagal: ' + e.message);
        }
    }
</script>

<style>
    .amount-btn:hover {
        border-color: #10b981 !important;
        transform: translateY(-2px);
    }
    
    input:focus {
        outline: none;
        border-color: #10b981 !important;
    }
</style>
<?= $this->endSection() ?>
