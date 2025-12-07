<!-- AI Assistant Chat Widget -->
<style>
    /* AI Chat Widget Styles */
    .ai-chat-widget {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
    }
    
    .ai-chat-toggle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8b5cf6, #6366f1);
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .ai-chat-toggle:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 25px rgba(139, 92, 246, 0.5);
    }
    
    .ai-chat-toggle.active {
        background: #ef4444;
    }
    
    .ai-chat-box {
        position: absolute;
        bottom: 80px;
        right: 0;
        width: 380px;
        max-height: 500px;
        background: #1e293b;
        border-radius: 16px;
        border: 1px solid #334155;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        display: none;
        flex-direction: column;
        overflow: hidden;
    }
    
    .ai-chat-box.open {
        display: flex;
        animation: slideUp 0.3s ease;
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .ai-chat-header {
        background: linear-gradient(135deg, #8b5cf6, #6366f1);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .ai-chat-header-icon {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    
    .ai-chat-header-info h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }
    
    .ai-chat-header-info p {
        margin: 0;
        font-size: 12px;
        opacity: 0.8;
    }
    
    .ai-chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        min-height: 250px;
        max-height: 350px;
    }
    
    .ai-message {
        max-width: 85%;
        padding: 12px 16px;
        border-radius: 16px;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .ai-message.bot {
        background: #334155;
        align-self: flex-start;
        border-bottom-left-radius: 4px;
    }
    
    .ai-message.user {
        background: linear-gradient(135deg, #8b5cf6, #6366f1);
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }
    
    .ai-message.loading {
        display: flex;
        gap: 4px;
        padding: 16px 20px;
    }
    
    .ai-message.loading .dot {
        width: 8px;
        height: 8px;
        background: #64748b;
        border-radius: 50%;
        animation: bounce 1.4s infinite ease-in-out both;
    }
    
    .ai-message.loading .dot:nth-child(1) { animation-delay: -0.32s; }
    .ai-message.loading .dot:nth-child(2) { animation-delay: -0.16s; }
    
    @keyframes bounce {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }
    
    .ai-quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 12px 16px;
        border-top: 1px solid #334155;
    }
    
    .ai-quick-action {
        background: #334155;
        border: none;
        color: white;
        padding: 8px 12px;
        border-radius: 20px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .ai-quick-action:hover {
        background: #475569;
    }
    
    .ai-chat-input {
        display: flex;
        gap: 8px;
        padding: 12px 16px;
        border-top: 1px solid #334155;
        background: #0f172a;
    }
    
    .ai-chat-input input {
        flex: 1;
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 24px;
        padding: 12px 16px;
        color: white;
        font-size: 14px;
        outline: none;
    }
    
    .ai-chat-input input:focus {
        border-color: #8b5cf6;
    }
    
    .ai-chat-input input::placeholder {
        color: #64748b;
    }
    
    .ai-chat-input button {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, #8b5cf6, #6366f1);
        border: none;
        border-radius: 50%;
        color: white;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .ai-chat-input button:hover {
        transform: scale(1.05);
    }
    
    .ai-chat-input button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    /* Voice Button */
    .ai-voice-btn {
        width: 44px;
        height: 44px;
        background: #334155;
        border: none;
        border-radius: 50%;
        color: white;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .ai-voice-btn:hover {
        background: #475569;
    }
    
    .ai-voice-btn.recording {
        background: #ef4444;
        animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
</style>

<div class="ai-chat-widget">
    <!-- Chat Box -->
    <div class="ai-chat-box" id="aiChatBox">
        <div class="ai-chat-header">
            <div class="ai-chat-header-icon">🤖</div>
            <div class="ai-chat-header-info">
                <h3>UDARA AI Assistant</h3>
                <p>Siap membantu Anda</p>
            </div>
        </div>
        
        <div class="ai-chat-messages" id="aiChatMessages">
            <div class="ai-message bot">
                Halo! 👋 Saya UDARA AI Assistant. Ada yang bisa saya bantu?
                <br><br>
                Anda bisa bertanya tentang:
                <br>• 💰 Top up & pembayaran
                <br>• 🏠 Kontrol perangkat smart home
                <br>• 📊 Analisis keuangan
            </div>
        </div>
        
        <div class="ai-quick-actions" id="aiQuickActions">
            <button class="ai-quick-action" onclick="sendQuickAction('Berapa saldo saya?')">💰 Cek Saldo</button>
            <button class="ai-quick-action" onclick="sendQuickAction('Bagaimana cara top up?')">💳 Cara Top Up</button>
            <button class="ai-quick-action" onclick="sendQuickAction('Analisis pengeluaran saya')">📊 Analisis</button>
        </div>
        
        <div class="ai-chat-input">
            <button class="ai-voice-btn" id="aiVoiceBtn" onclick="toggleVoice()" title="Voice Command">
                🎤
            </button>
            <input type="text" id="aiChatInput" placeholder="Ketik pesan..." onkeypress="handleInputKeypress(event)">
            <button id="aiSendBtn" onclick="sendMessage()">
                ➤
            </button>
        </div>
    </div>
    
    <!-- Toggle Button -->
    <button class="ai-chat-toggle" id="aiChatToggle" onclick="toggleChat()">
        🤖
    </button>
</div>

<script>
    let aiChatOpen = false;
    let isRecording = false;
    let recognition = null;
    
    // Toggle chat box
    function toggleChat() {
        aiChatOpen = !aiChatOpen;
        const chatBox = document.getElementById('aiChatBox');
        const toggleBtn = document.getElementById('aiChatToggle');
        
        if (aiChatOpen) {
            chatBox.classList.add('open');
            toggleBtn.classList.add('active');
            toggleBtn.innerHTML = '✕';
            document.getElementById('aiChatInput').focus();
            // Load insights when opening
            loadInsights();
        } else {
            chatBox.classList.remove('open');
            toggleBtn.classList.remove('active');
            toggleBtn.innerHTML = '🤖';
        }
    }
    
    // Handle input keypress
    function handleInputKeypress(event) {
        if (event.key === 'Enter') {
            sendMessage();
        }
    }
    
    // Send message
    async function sendMessage() {
        const input = document.getElementById('aiChatInput');
        const message = input.value.trim();
        
        if (!message) return;
        
        // Add user message
        addMessage(message, 'user');
        input.value = '';
        
        // Show loading
        showLoading();
        
        try {
            // Call via CodeIgniter proxy (same origin, no CORS/Mixed Content issues)
            const response = await fetch('/agent/chat?message=' + encodeURIComponent(message), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            hideLoading();
            
            if (data.success) {
                // Handle response with actions
                const responseText = data.message || data.response || data.data?.response || 'OK';
                addMessage(responseText, 'bot');
                
                // Show action buttons if any
                const actions = data.actions || data.data?.actions || [];
                if (actions.length > 0) {
                    showActionButtons(actions);
                }
                
                // Update suggestions
                loadSuggestions();
            } else {
                addMessage(data.message || 'Maaf, terjadi kesalahan. Silakan coba lagi.', 'bot');
            }
        } catch (error) {
            hideLoading();
            addMessage('Maaf, tidak dapat terhubung ke AI. Silakan coba lagi.', 'bot');
        }
    }
    
    // Show action buttons in chat
    function showActionButtons(actions) {
        const messagesContainer = document.getElementById('aiChatMessages');
        const actionsEl = document.createElement('div');
        actionsEl.className = 'ai-action-buttons';
        actionsEl.style.cssText = 'display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; align-self: flex-start;';
        
        actions.forEach(action => {
            const btn = document.createElement('button');
            btn.className = 'ai-quick-action';
            btn.innerHTML = action.label;
            btn.onclick = () => handleAction(action);
            actionsEl.appendChild(btn);
        });
        
        messagesContainer.appendChild(actionsEl);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Handle action button click
    function handleAction(action) {
        const actionType = action.action || action.type;
        
        switch(actionType) {
            case 'topup':
                window.location.href = '/user/topup';
                break;
            case 'transfer':
                window.location.href = '/user/transfer';
                break;
            case 'check_balance':
                sendQuickAction('Berapa saldo saya?');
                break;
            case 'spending_analysis':
                sendQuickAction('Analisis pengeluaran saya');
                break;
            case 'device_list':
                sendQuickAction('Daftar perangkat smart home');
                break;
            case 'help':
                sendQuickAction('Bantuan');
                break;
            case 'merchant_transactions':
                window.location.href = '/merchant/transactions';
                break;
            case 'withdraw':
                window.location.href = '/merchant/withdraw';
                break;
            case 'merchant_qr':
                window.location.href = '/merchant/qrcode';
                break;
            case 'pay_bills':
                window.location.href = '/user/bills';
                break;
            default:
                if (action.label) {
                    sendQuickAction(action.label);
                }
        }
    }
    
    // Load AI insights
    async function loadInsights() {
        try {
            const response = await fetch('/agent/insights', {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            
            if (data.success && data.data?.insights?.length > 0) {
                showInsights(data.data.insights);
            }
        } catch (error) {
            // Silently fail
        }
    }
    
    // Show insights in chat
    function showInsights(insights) {
        const messagesContainer = document.getElementById('aiChatMessages');
        
        // Check if insights already shown
        if (document.querySelector('.ai-insights-container')) return;
        
        const insightsEl = document.createElement('div');
        insightsEl.className = 'ai-insights-container ai-message bot';
        insightsEl.style.cssText = 'background: linear-gradient(135deg, #1e3a5f, #1e293b); border: 1px solid #3b82f6;';
        
        let html = '<strong>💡 Insights untuk Anda:</strong><br><br>';
        insights.forEach(insight => {
            html += `<div style="margin-bottom: 8px;">
                ${insight.icon} <strong>${insight.title}</strong><br>
                <span style="opacity: 0.9; font-size: 13px;">${insight.message}</span>
            </div>`;
        });
        
        insightsEl.innerHTML = html;
        
        // Insert after welcome message
        const firstMessage = messagesContainer.querySelector('.ai-message');
        if (firstMessage) {
            firstMessage.after(insightsEl);
        } else {
            messagesContainer.appendChild(insightsEl);
        }
    }
    
    // Load suggestions
    async function loadSuggestions() {
        try {
            const response = await fetch('/agent/suggestions', {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            
            if (data.success && data.data?.suggestions) {
                updateQuickActions(data.data.suggestions);
            }
        } catch (error) {
            // Silently fail
        }
    }
    
    // Update quick action buttons
    function updateQuickActions(suggestions) {
        const quickActionsContainer = document.getElementById('aiQuickActions');
        quickActionsContainer.innerHTML = '';
        
        suggestions.slice(0, 4).forEach(suggestion => {
            const btn = document.createElement('button');
            btn.className = 'ai-quick-action';
            btn.innerHTML = suggestion;
            btn.onclick = () => sendQuickAction(suggestion);
            quickActionsContainer.appendChild(btn);
        });
    }
    
    // Send quick action
    function sendQuickAction(text) {
        document.getElementById('aiChatInput').value = text;
        sendMessage();
    }
    
    // Add message to chat
    function addMessage(text, type) {
        const messagesContainer = document.getElementById('aiChatMessages');
        const messageEl = document.createElement('div');
        messageEl.className = `ai-message ${type}`;
        
        // Format markdown-style text
        let formattedText = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
        
        messageEl.innerHTML = formattedText;
        messagesContainer.appendChild(messageEl);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Show loading indicator
    function showLoading() {
        const messagesContainer = document.getElementById('aiChatMessages');
        const loadingEl = document.createElement('div');
        loadingEl.className = 'ai-message bot loading';
        loadingEl.id = 'aiLoading';
        loadingEl.innerHTML = '<div class="dot"></div><div class="dot"></div><div class="dot"></div>';
        messagesContainer.appendChild(loadingEl);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        document.getElementById('aiSendBtn').disabled = true;
    }
    
    // Hide loading indicator
    function hideLoading() {
        const loadingEl = document.getElementById('aiLoading');
        if (loadingEl) {
            loadingEl.remove();
        }
        document.getElementById('aiSendBtn').disabled = false;
    }
    
    // Voice recognition
    function toggleVoice() {
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            alert('Browser Anda tidak mendukung voice recognition');
            return;
        }
        
        const voiceBtn = document.getElementById('aiVoiceBtn');
        
        if (isRecording) {
            // Stop recording
            if (recognition) {
                recognition.stop();
            }
            isRecording = false;
            voiceBtn.classList.remove('recording');
            voiceBtn.innerHTML = '🎤';
        } else {
            // Start recording
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            recognition.lang = 'id-ID';
            recognition.continuous = false;
            recognition.interimResults = false;
            
            recognition.onstart = function() {
                isRecording = true;
                voiceBtn.classList.add('recording');
                voiceBtn.innerHTML = '⏹️';
            };
            
            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript;
                document.getElementById('aiChatInput').value = transcript;
                sendVoiceCommand(transcript);
            };
            
            recognition.onerror = function(event) {
                isRecording = false;
                voiceBtn.classList.remove('recording');
                voiceBtn.innerHTML = '🎤';
            };
            
            recognition.onend = function() {
                isRecording = false;
                voiceBtn.classList.remove('recording');
                voiceBtn.innerHTML = '🎤';
            };
            
            recognition.start();
        }
    }
    
    // Send voice command
    async function sendVoiceCommand(text) {
        addMessage(text, 'user');
        showLoading();
        
        try {
            // Check if it's a device command
            const deviceKeywords = ['nyalakan', 'matikan', 'hidupkan', 'set', 'atur', 'lampu', 'ac', 'kunci', 'pintu'];
            const isDeviceCommand = deviceKeywords.some(keyword => text.toLowerCase().includes(keyword));
            
            let response;
            if (isDeviceCommand) {
                // Call via CodeIgniter proxy
                response = await fetch('/agent/voice?text=' + encodeURIComponent(text), {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
            } else {
                // Call via CodeIgniter proxy
                response = await fetch('/agent/chat?message=' + encodeURIComponent(text), {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
            }
            
            const data = await response.json();
            hideLoading();
            
            if (data.success) {
                const reply = data.data?.spoken_response || data.response || data.data?.response || data.data?.action?.message || 'Perintah diproses';
                addMessage(reply, 'bot');
                
                // Text-to-speech response
                if ('speechSynthesis' in window) {
                    const utterance = new SpeechSynthesisUtterance(reply.replace(/<[^>]*>/g, '').replace(/\*\*/g, ''));
                    utterance.lang = 'id-ID';
                    speechSynthesis.speak(utterance);
                }
            } else {
                addMessage(data.message || 'Maaf, terjadi kesalahan.', 'bot');
            }
        } catch (error) {
            hideLoading();
            addMessage('Maaf, tidak dapat memproses perintah.', 'bot');
        }
    }
    
    // Clear conversation
    async function clearConversation() {
        const messagesContainer = document.getElementById('aiChatMessages');
        messagesContainer.innerHTML = `
            <div class="ai-message bot">
                Halo! 👋 Saya UDARA AI Assistant. Ada yang bisa saya bantu?
                <br><br>
                Anda bisa bertanya tentang:
                <br>• 💰 Top up & pembayaran
                <br>• 🏠 Kontrol perangkat smart home
                <br>• 📊 Analisis keuangan
            </div>
        `;
        
        try {
            await fetch('/agent/clear-history', { method: 'GET' });
        } catch (error) {
            // Silently fail
        }
    }
</script>
