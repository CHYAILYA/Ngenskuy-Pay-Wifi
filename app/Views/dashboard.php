<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body {
            background: #181818;
            color: #fff;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 500px;
            margin: 40px auto;
            background: #222;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.3);
            padding: 32px 24px;
        }
        h2 {
            font-size: 2rem;
            margin-bottom: 8px;
        }
        .subtitle {
            color: #aaa;
            margin-bottom: 24px;
        }
        .btn-logout {
            background: #fff;
            color: #222;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 1rem;
            margin-top: 24px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Dashboard</h2>
        <div class="subtitle">Welcome, <?= esc($user['name']) ?> (<?= esc($user['email']) ?>)</div>
        <form method="post" action="<?= site_url('logout') ?>">
            <button type="submit" class="btn-logout">Logout</button>
        </form>
    </div>
</body>
</html>
