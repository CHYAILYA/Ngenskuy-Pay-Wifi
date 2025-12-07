import os
from flask import Blueprint, request, jsonify
from dotenv import load_dotenv
import requests

chat_bp = Blueprint('chat', __name__)

# Load .env file
load_dotenv(os.path.join(os.path.dirname(__file__), '..', '.env'))

API_URL = os.getenv('KOLOSAL_API_URL', 'https://api.kolosal.ai/v1/chat/completions')
API_KEY = os.getenv('KOLOSAL_API_KEY')

@chat_bp.route('/', methods=['POST'])
def chat():
    data = request.get_json()
    user_message = data.get('message')
    user_context = data.get('context')
    print("[DEBUG] Received context:", user_context)
    if not user_message:
        return jsonify({'error': 'Message is required'}), 400

    # Always build prompt by merging payment context into content string
    merchant = ''
    merchant_id = ''
    amount = ''
    desc = ''
    page = ''
    wallet_str = ''
    wallet_stats_str = ''
    wallet_tx_str = ''
    merchant_str = ''
    merchant_stats_str = ''
    merchant_tx_str = ''
    # Extract context fields
    if user_context and isinstance(user_context, dict):
        merchant = user_context.get('merchant_name', '')
        merchant_id = user_context.get('merchant_id', '')
        amount = user_context.get('amount', '')
        desc = user_context.get('description', '')
        page = user_context.get('page', '')
        # Wallet info
        wallet = user_context.get('wallet')
        if wallet:
            wallet_str = f"\n💰 SALDO WALLET: {wallet.get('balance_formatted', wallet.get('balance', ''))}"
        # Wallet stats
        wallet_stats = user_context.get('wallet_stats')
        if wallet_stats:
            wallet_stats_str = (
                f"\n📊 RINGKASAN KEUANGAN:\n"
                f"- Total Pemasukan: Rp {wallet_stats.get('total_income', 0):,}\n"
                f"- Total Pengeluaran: Rp {wallet_stats.get('total_expense', 0):,}\n"
                f"- Jumlah Transaksi: {wallet_stats.get('transaction_count', 0)}"
            )
        # Wallet transactions
        wallet_tx = user_context.get('wallet_transactions')
        if wallet_tx:
            wallet_tx_str = "\n📋 RIWAYAT TRANSAKSI WALLET:\n"
            for tx in wallet_tx[:5]:
                tx_type = '➕' if tx.get('type') == 'credit' else '➖'
                wallet_tx_str += f"- {tx.get('date')}: {tx_type} Rp {tx.get('amount', 0):,} - {tx.get('description', '')}\n"
        # Merchant info
        merchant_info = user_context.get('merchant')
        if merchant_info:
            merchant_str = (
                f"\n🏪 INFO MERCHANT:\n"
                f"- Nama Usaha: {merchant_info.get('name', '')}\n"
                f"- Jenis Usaha: {merchant_info.get('business_type', '')}\n"
                f"- Merchant ID: {merchant_info.get('merchant_id', '')}\n"
                f"- Saldo Merchant: Rp {merchant_info.get('balance', 0):,}"
            )
        # Merchant stats
        merchant_stats = user_context.get('merchant_stats')
        if merchant_stats:
            merchant_stats_str = (
                f"\n📈 STATISTIK MERCHANT:\n"
                f"- Total Pendapatan: Rp {merchant_stats.get('total_income', 0):,}\n"
                f"- Pendapatan Hari Ini: Rp {merchant_stats.get('today_income', 0):,}\n"
                f"- Jumlah Transaksi: {merchant_stats.get('transaction_count', 0)}"
            )
        # Merchant transactions
        merchant_tx = user_context.get('merchant_transactions')
        if merchant_tx:
            merchant_tx_str = "\n📋 TRANSAKSI MERCHANT TERAKHIR:\n"
            for tx in merchant_tx[:5]:
                merchant_tx_str += f"- {tx.get('date')}: Rp {tx.get('amount', 0):,} ({tx.get('status', '')}) - {tx.get('description', '')}\n"
    let_payment = (
        "\n=== DETAIL PEMBAYARAN ===\n"
        f"- Nama Merchant: {merchant}\n"
        f"- Merchant ID: {merchant_id}\n"
        f"- Jumlah Pembayaran: Rp {amount}\n"
        f"- Deskripsi: {desc}\n"
        f"- Link Pembayaran: {page}\n"
        "\nMohon gunakan detail di atas untuk menjawab pertanyaan user secara spesifik.\n"
    )
    # Build full prompt
    prompt = (
        f"{let_payment}"
        f"{wallet_str}"
        f"{wallet_stats_str}"
        f"{wallet_tx_str}"
        f"{merchant_str}"
        f"{merchant_stats_str}"
        f"{merchant_tx_str}"
        f"\n[PESAN USER]:\n{user_message}"
    )
    print("[DEBUG] Built prompt:", prompt)

    payload = {
        "model": "Claude Sonnet 4.5",
        "messages": [
            {"role": "user", "content": prompt}
        ]
    }
    headers = {
        "Content-Type": "application/json",
        "Authorization": f"Bearer {API_KEY}"
    }
    try:
        response = requests.post(API_URL, json=payload, headers=headers, timeout=30)
        response.raise_for_status()
        return jsonify(response.json())
    except requests.RequestException as e:
        return jsonify({'error': str(e)}), 500
