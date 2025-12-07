<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #0a0a0a;
            color: #fff;
            min-height: 100vh;
        }
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #fff;
            text-decoration: none;
            font-size: 0.9rem;
            z-index: 10;
        }
        .main-container {
            width: 100%;
            max-width: 400px;
            padding: 32px 24px;
            background: #111;
            border-radius: 16px;
            border: 1px solid #222;
        }
        h2 {
            font-size: 1.75rem;
            margin-bottom: 8px;
            font-weight: 700;
            text-align: center;
        }
        .subtitle {
            color: #888;
            margin-bottom: 24px;
            font-size: 0.95rem;
            text-align: center;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 18px;
            text-align: center;
            font-size: 0.9rem;
        }
        .alert-danger {
            background: #c00;
            color: #fff;
        }
        .alert-success {
            background: #28a745;
            color: #fff;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 0.9rem;
        }
        input[type="email"],
        input[type="password"],
        input[type="text"] {
            display: block;
            width: 100%;
            height: 48px;
            padding: 0 16px;
            margin-bottom: 16px;
            border-radius: 8px;
            border: 1px solid #333;
            background: #181818;
            color: #fff;
            font-size: 1rem;
        }
        input:focus {
            outline: none;
            border-color: #555;
        }
        button[type="submit"] {
            width: 100%;
            background: #fff;
            color: #222;
            border: none;
            border-radius: 8px;
            padding: 14px 0;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button[type="submit"]:hover {
            background: #eee;
        }
        .links {
            margin-top: 24px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            text-align: center;
        }
        .links a {
            color: #888;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .links a:hover {
            color: #fff;
            text-decoration: underline;
        }
        @media (max-width: 480px) {
            .main-container {
                padding: 24px 16px;
                border-radius: 12px;
            }
            h2 {
                font-size: 1.5rem;
            }
            .back-link {
                top: 16px;
                left: 16px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <a href="/" class="back-link">&larr; Back to website</a>
        <div class="main-container">
            <h2>Sign In</h2>
            <p class="subtitle">Enter your credentials to continue</p>
            
            <?php if (!empty($error)) : ?>
                <div class="alert alert-danger"><?= esc($error) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($info)) : ?>
                <div class="alert alert-success"><?= esc($info) ?></div>
            <?php endif; ?>

            <?php if (!empty($ui_forgot)) : ?>
                <!-- Forgot Password Form -->
                <form method="post" action="<?= site_url('forgot') ?>">
                    <label for="forgot_email">Email</label>
                    <input type="email" id="forgot_email" name="forgot_email" placeholder="name@example.com" required>
                    <button type="submit">Send Reset Link</button>
                </form>
                
            <?php elseif (!empty($ui_magic)) : ?>
                <!-- Magic Link Form -->
                <form method="post" action="<?= site_url('magic') ?>">
                    <label for="magic_email">Email</label>
                    <input type="email" id="magic_email" name="magic_email" placeholder="name@example.com" required>
                    <button type="submit">Send Magic Link</button>
                </form>
                
            <?php elseif (!empty($ui_register)) : ?>
                <!-- Registration Form -->
                <form method="post" action="<?= site_url('register') ?>">
                    <label for="reg_email">Email</label>
                    <input type="email" id="reg_email" name="reg_email" placeholder="name@example.com" required>
                    <label for="reg_name">Name</label>
                    <input type="text" id="reg_name" name="reg_name" placeholder="Your Name" required>
                    <label for="reg_password">Password</label>
                    <input type="password" id="reg_password" name="reg_password" placeholder="Min 6 characters" required minlength="6">
                    <button type="submit">Sign Up</button>
                </form>
                
            <?php else: ?>
                <!-- Login Form -->
                <form method="post" action="<?= site_url('login') ?>">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="name@example.com" required>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <button type="submit">Sign In</button>
                </form>
            <?php endif; ?>
            
            <div class="links">
                <a href="<?= site_url('forgot') ?>">Forgot your password?</a>
                <a href="<?= site_url('magic') ?>">Sign in via magic link</a>
                <a href="<?= site_url('register') ?>">Don't have an account? Sign up</a>
            </div>
        </div>
    </div>

</body>
</html>
