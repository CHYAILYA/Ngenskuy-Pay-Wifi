from flask import Flask

def create_app():
    app = Flask(__name__)

    # Register blueprints/controllers here
    from controllers.chat_controller import chat_bp
    app.register_blueprint(chat_bp, url_prefix='/api/chat')

    return app

if __name__ == '__main__':
    app = create_app()
    app.run(host='0.0.0.0', port=5000, debug=True)
