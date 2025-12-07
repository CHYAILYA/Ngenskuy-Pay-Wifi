<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('sidebar') ?>
<div class="nav-section">
    <div class="nav-section-title">Main Menu</div>
    <a href="/user/dashboard" class="nav-item"><span class="nav-icon">🏠</span> Dashboard</a>
    <a href="/user/topup" class="nav-item"><span class="nav-icon">💳</span> Top Up</a>
    <a href="/user/transfer" class="nav-item active"><span class="nav-icon">💸</span> Transfer</a>
    <a href="/user/bills" class="nav-item"><span class="nav-icon">📄</span> Bills</a>
    <a href="/user/payments" class="nav-item"><span class="nav-icon">📋</span> History</a>
</div>
<div class="nav-section">
    <div class="nav-section-title">Account</div>
    <a href="/user/profile" class="nav-item"><span class="nav-icon">👤</span> Profile</a>
    <a href="/logout" class="nav-item"><span class="nav-icon">🚪</span> Logout</a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <div>
        <h1 style="font-size: 28px; margin-bottom: 8px;">💸 Transfer</h1>
        <p style="color: #64748b;">Send money to users or pay merchants</p>
    </div>
</div>

<!-- Balance Card -->
<div style="background: linear-gradient(135deg, #3b82f6, #6366f1); border-radius: 16px; padding: 24px; margin-bottom: 32px;">
    <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">💰 Available Balance</div>
    <div style="font-size: 32px; font-weight: 700;">Rp <?= number_format($balance, 0, ',', '.') ?></div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
    <!-- Transfer Form -->
    <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 32px;">
        <h3 style="font-size: 18px; margin-bottom: 24px;">📤 Send Money</h3>

        <!-- Alert -->
        <div id="alertBox" style="display: none; padding: 16px; border-radius: 12px; margin-bottom: 20px;"></div>

        <!-- Success View -->
        <div id="successView" style="display: none; text-align: center; padding: 24px;">
            <div style="width: 80px; height: 80px; background: #10b98130; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px;">✓</div>
            <h2 style="margin-bottom: 8px;">Transfer Successful!</h2>
            <p style="color: #64748b; margin-bottom: 24px;" id="successDetail"></p>
            <button onclick="resetForm()" style="padding: 12px 32px; background: #3b82f6; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer;">
                New Transfer
            </button>
        </div>

        <!-- Form -->
        <form id="transferForm" onsubmit="return processTransfer(event)">
            <div style="margin-bottom: 20px;">
                <label style="display: block; color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Card Number / Merchant ID *</label>
                <div style="position: relative;">
                    <input type="text" name="destination" id="destination" required 
                           placeholder="e.g. 4123456789012345 or MCH-ABC123"
                           oninput="lookupRecipient()"
                           style="width: 100%; padding: 14px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; font-size: 16px; font-family: monospace;">
                    <div id="recipientInfo" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #0f172a; border: 1px solid #10b981; border-radius: 8px; padding: 12px; margin-top: 8px; z-index: 10;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span id="recipientIcon" style="font-size: 24px;">👤</span>
                            <div>
                                <div id="recipientName" style="font-weight: 600;"></div>
                                <div id="recipientType" style="color: #10b981; font-size: 12px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Amount (Rp) *</label>
                <input type="number" name="amount" id="amount" min="1000" required placeholder="Minimum Rp 1,000"
                       style="width: 100%; padding: 14px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; font-size: 16px;">
                <div style="display: flex; gap: 8px; margin-top: 12px;">
                    <button type="button" onclick="setAmount(10000)" style="flex: 1; padding: 8px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #94a3b8; cursor: pointer; font-size: 12px;">10rb</button>
                    <button type="button" onclick="setAmount(25000)" style="flex: 1; padding: 8px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #94a3b8; cursor: pointer; font-size: 12px;">25rb</button>
                    <button type="button" onclick="setAmount(50000)" style="flex: 1; padding: 8px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #94a3b8; cursor: pointer; font-size: 12px;">50rb</button>
                    <button type="button" onclick="setAmount(100000)" style="flex: 1; padding: 8px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #94a3b8; cursor: pointer; font-size: 12px;">100rb</button>
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Note (Optional)</label>
                <input type="text" name="note" placeholder="e.g. Payment for order #123"
                       style="width: 100%; padding: 14px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; font-size: 16px;">
            </div>

            <button type="submit" id="submitBtn" style="width: 100%; padding: 16px; background: linear-gradient(135deg, #10b981, #059669); border: none; border-radius: 10px; color: white; font-weight: 600; font-size: 16px; cursor: pointer;">
                <span id="btnText">💸 Send Now</span>
                <span id="btnLoading" style="display: none;">Processing...</span>
            </button>
        </form>
    </div>

    <!-- Instructions -->
    <div>
        <div style="background: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 24px; margin-bottom: 24px;">
            <h3 style="font-size: 18px; margin-bottom: 20px;">📋 How to Transfer</h3>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; align-items: flex-start; gap: 16px;">
                    <div style="width: 32px; height: 32px; background: #3b82f620; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-weight: 700;">1</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 4px;">Enter destination</div>
                        <div style="color: #64748b; font-size: 14px;">User's card number (16 digits) or Merchant ID (MCH-XXXXXX)</div>
                    </div>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 16px;">
                    <div style="width: 32px; height: 32px; background: #3b82f620; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-weight: 700;">2</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 4px;">Verify recipient</div>
                        <div style="color: #64748b; font-size: 14px;">Check the recipient name before sending</div>
                    </div>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 16px;">
                    <div style="width: 32px; height: 32px; background: #3b82f620; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-weight: 700;">3</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 4px;">Send money</div>
                        <div style="color: #64748b; font-size: 14px;">Money is transferred instantly</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="background: linear-gradient(135deg, #f59e0b20, #f59e0b10); border: 1px solid #f59e0b; border-radius: 16px; padding: 24px;">
            <h4 style="margin-bottom: 12px; color: #f59e0b;">⚠️ Important</h4>
            <ul style="color: #94a3b8; font-size: 14px; line-height: 1.8; margin-left: 16px;">
                <li>Double check the recipient before sending</li>
                <li>Transfers cannot be reversed</li>
                <li>Minimum transfer: Rp 1,000</li>
                <li>No transfer fee for user-to-user</li>
            </ul>
        </div>
    </div>
</div>

<script>
let lookupTimeout;

function setAmount(amount) {
    document.getElementById('amount').value = amount;
}

function lookupRecipient() {
    clearTimeout(lookupTimeout);
    const destination = document.getElementById('destination').value;
    const infoBox = document.getElementById('recipientInfo');
    
    if (destination.length < 6) {
        infoBox.style.display = 'none';
        return;
    }
    
    lookupTimeout = setTimeout(() => {
        fetch('/user/transfer/lookup?q=' + encodeURIComponent(destination))
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('recipientName').textContent = data.name;
                    document.getElementById('recipientType').textContent = data.type === 'merchant' ? '🏪 Merchant' : '👤 User';
                    document.getElementById('recipientIcon').textContent = data.type === 'merchant' ? '🏪' : '👤';
                    infoBox.style.display = 'block';
                } else {
                    infoBox.style.display = 'none';
                }
            })
            .catch(() => {
                infoBox.style.display = 'none';
            });
    }, 500);
}

function processTransfer(e) {
    e.preventDefault();
    
    const form = document.getElementById('transferForm');
    const btn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnLoading = document.getElementById('btnLoading');
    const alertBox = document.getElementById('alertBox');
    
    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoading.style.display = 'inline';
    alertBox.style.display = 'none';
    
    const formData = new FormData(form);
    
    fetch('/user/transfer/process', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('transferForm').style.display = 'none';
            document.getElementById('successView').style.display = 'block';
            document.getElementById('successDetail').textContent = 
                `Rp ${parseInt(data.amount).toLocaleString('id-ID')} sent to ${data.recipient}`;
        } else {
            alertBox.style.display = 'block';
            alertBox.style.background = '#ef444420';
            alertBox.style.border = '1px solid #ef4444';
            alertBox.style.color = '#fca5a5';
            alertBox.textContent = data.message;
        }
    })
    .catch(err => {
        alertBox.style.display = 'block';
        alertBox.style.background = '#ef444420';
        alertBox.style.border = '1px solid #ef4444';
        alertBox.style.color = '#fca5a5';
        alertBox.textContent = 'Transfer failed. Please try again.';
    })
    .finally(() => {
        btn.disabled = false;
        btnText.style.display = 'inline';
        btnLoading.style.display = 'none';
    });
    
    return false;
}

function resetForm() {
    document.getElementById('transferForm').reset();
    document.getElementById('transferForm').style.display = 'block';
    document.getElementById('successView').style.display = 'none';
    document.getElementById('recipientInfo').style.display = 'none';
    document.getElementById('alertBox').style.display = 'none';
}
</script>
<?= $this->endSection() ?>
