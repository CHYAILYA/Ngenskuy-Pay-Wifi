
<!-- Toggle Button for AI Chat Widget -->
<button id="ai-chat-toggle-btn" style="position:fixed;bottom:32px;right:32px;z-index:9999;background:#10b981;color:#fff;border:none;border-radius:50%;width:56px;height:56px;box-shadow:0 4px 16px rgba(16,185,129,0.18);font-size:28px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background 0.2s;">
  💬
</button>

<!-- Modern AI Chat Widget for Payment Page -->
<div id="ai-chat-widget" style="position:fixed;bottom:100px;right:32px;z-index:9999;width:370px;max-width:100vw;background:#1e293b;border-radius:18px;box-shadow:0 8px 32px rgba(16,185,129,0.12);border:1px solid #334155;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,sans-serif;display:none;">
  <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #334155;background:linear-gradient(135deg,#10b98120,#05966920);font-weight:600;font-size:17px;color:#10b981;letter-spacing:0.5px;">
    <span>💬 Tanya AI Agent</span>
    <button id="ai-chat-close-btn" style="background:none;border:none;color:#64748b;font-size:22px;cursor:pointer;">&times;</button>
  </div>
  <div id="ai-chat-messages" style="height:240px;overflow-y:auto;padding:16px 18px;font-size:15px;background:#0f172a;"></div>
  <form id="ai-chat-form" style="display:flex;border-top:1px solid #334155;padding:12px 14px;background:#1e293b;">
    <input type="text" id="ai-chat-input" placeholder="Tanya tentang pembayaran..." style="flex:1;padding:12px 14px;border-radius:10px;border:1px solid #334155;background:#0f172a;color:#fff;font-size:15px;" autocomplete="off" />
    <button type="submit" style="margin-left:10px;padding:12px 20px;border-radius:10px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;font-weight:600;font-size:15px;box-shadow:0 2px 8px rgba(16,185,129,0.10);cursor:pointer;">Kirim</button>
  </form>
</div>

<script>

const chatWidget = document.getElementById('ai-chat-widget');
const chatMessages = document.getElementById('ai-chat-messages');
const chatForm = document.getElementById('ai-chat-form');
const chatInput = document.getElementById('ai-chat-input');
const chatToggleBtn = document.getElementById('ai-chat-toggle-btn');
const chatCloseBtn = document.getElementById('ai-chat-close-btn');

// Ambil context pembayaran dari PHP (lebih akurat)
const paymentContext = window.AI_PAYMENT_CONTEXT || {};

// Toggle open/close widget
chatToggleBtn.addEventListener('click', function() {
  chatWidget.style.display = 'block';
  chatToggleBtn.style.display = 'none';
});

chatCloseBtn.addEventListener('click', function() {
  chatWidget.style.display = 'none';
  chatToggleBtn.style.display = 'flex';
});

function appendMessage(text, sender) {
  const msg = document.createElement('div');
  msg.style.marginBottom = '12px';
  msg.style.textAlign = sender === 'user' ? 'right' : 'left';
  msg.innerHTML = `<span style="background:${sender==='user'?'#10b98130':'#334155'};color:${sender==='user'?'#10b981':'#fff'};padding:10px 16px;border-radius:12px;display:inline-block;max-width:80%;box-shadow:0 2px 8px rgba(16,185,129,0.08);font-size:15px;">${text}</span>`;
  chatMessages.appendChild(msg);
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

chatForm.addEventListener('submit', async function(e) {
  e.preventDefault();
  const message = chatInput.value.trim();
  if (!message) return;
  appendMessage(message, 'user');
  chatInput.value = '';
  appendMessage('...', 'ai');
  try {
    const res = await fetch('/agent/chat', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message, context: paymentContext })
    });
    const data = await res.json();
    // Remove loading
    chatMessages.lastChild.remove();
    if (data.success && data.message) {
      appendMessage(data.message, 'ai');
    } else {
      appendMessage('Maaf, AI tidak bisa menjawab saat ini.', 'ai');
    }
  } catch (err) {
    chatMessages.lastChild.remove();
    appendMessage('Gagal menghubungi AI.', 'ai');
  }
});
</script>
