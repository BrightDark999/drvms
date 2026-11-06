<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Unavailable</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: #0f1419;
            color: #c9d1d9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .panel {
            max-width: 32rem;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 2rem;
            background: #161b22;
        }
        h1 { font-size: 1.25rem; font-weight: 600; margin-bottom: .75rem; color: #f0f6fc; }
        p { line-height: 1.6; color: #8b949e; font-size: .95rem; }
        code { font-size: .85rem; color: #79c0ff; }
    </style>
</head>
<body>
    <div class="panel">
        <h1>503 — Service temporarily unavailable</h1>
        <p>
            The application could not complete your request. Core services did not respond
            as expected. This is usually a hosting, database, or configuration issue.
        </p>
        <p style="margin-top: 1rem;">
            Reference: <code><?= esc(bin2hex(random_bytes(4)) . '-' . date('YmdHis')) ?></code>
        </p>
    </div>
</body>
</html>
