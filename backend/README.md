# Kolosal Chat API Backend (Flask)

## Development

1. Install dependencies:
   ```bash
   pip install -r requirements.txt
   ```

2. Run the server:
   ```bash
   python -m app
   ```

- The API will be available at http://localhost:5000/api/chat (POST)
- Example request:

```
curl http://localhost:5000/api/chat \
  -H "Content-Type: application/json" \
  -d '{"message": "Hello, how are you?"}'
```

## Project Structure

- `app/` - Flask app factory
- `controllers/` - API endpoints (Blueprints)
- `models/` - (Optional) Database models

## API Key
- The Kolosal API key is hardcoded in `controllers/chat_controller.py` for demo purposes.
