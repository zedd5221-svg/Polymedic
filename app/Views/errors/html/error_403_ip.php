<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Restricted IP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .error-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 20px;
            padding: 3rem;
            max-width: 520px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .icon-box {
            width: 80px;
            height: 80px;
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1.5rem;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .ip-badge {
            background: #0f172a;
            color: #38bdf8;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.95rem;
            display: inline-block;
            border: 1px solid #334155;
            margin: 1rem 0;
        }
        .btn-return {
            background: #2563eb;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }
        .btn-return:hover {
            background: #1d4ed8;
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon-box">
            <i class="bi bi-shield-slash-fill"></i>
        </div>
        <h2 class="fw-bold mb-2">Access Denied (403)</h2>
        <p class="text-secondary mb-3">
            System IP restriction is active. Your current IP address is not authorized to access staff or administrator panels.
        </p>
        <div class="ip-badge">
            <i class="bi bi-display me-2"></i><?= esc($clientIp ?? $_SERVER['REMOTE_ADDR']) ?>
        </div>
        <div class="mt-4">
            <a href="<?= base_url('logout') ?>" class="btn-return">
                <i class="bi bi-box-arrow-left"></i> Log Out &amp; Return Home
            </a>
        </div>
    </div>
</body>
</html>
