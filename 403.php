<?php
$page_title = '403 - Access Denied';
require_once __DIR__ . '/config/app.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0a1628; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .error-container { text-align: center; padding: 40px 20px; max-width: 600px; }
        .error-code { font-family: 'Playfair Display', serif; font-size: 8rem; font-weight: 700; background: linear-gradient(135deg, #ef4444, #f87171); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1; margin-bottom: 0; }
        .error-divider { width: 80px; height: 3px; background: linear-gradient(90deg, #ef4444, #f87171); margin: 24px auto; border-radius: 2px; }
        .error-title { font-family: 'Playfair Display', serif; font-size: 1.5rem; color: #e5e7eb; margin-bottom: 12px; }
        .error-message { color: #9ca3af; font-size: 1rem; line-height: 1.6; margin-bottom: 32px; }
        .btn-home { display: inline-flex; align-items: center; gap: 8px; padding: 12px 32px; background: linear-gradient(135deg, #d4af37, #f0d060); color: #0a1628; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.95rem; transition: all 0.3s ease; border: none; cursor: pointer; }
        .btn-home:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(212, 175, 55, 0.3); color: #0a1628; }
        .glow-orb { position: fixed; width: 400px; height: 400px; border-radius: 50%; background: radial-gradient(circle, rgba(239,68,68,0.08), transparent 70%); pointer-events: none; }
        .glow-orb:nth-child(1) { top: -200px; right: -200px; }
        .glow-orb:nth-child(2) { bottom: -200px; left: -200px; }
    </style>
</head>
<body>
    <div class="glow-orb"></div>
    <div class="glow-orb"></div>
    <div class="error-container">
        <div class="mb-3">
            <span style="font-family:'Playfair Display',serif; font-size:2rem; color:#d4af37; font-weight:700;">GP</span>
            <span style="font-family:'Playfair Display',serif; font-size:0.8rem; color:rgba(255,255,255,0.4); letter-spacing:3px; text-transform:uppercase; display:block;">FFB Hotel</span>
        </div>
        <h1 class="error-code">403</h1>
        <div class="error-divider"></div>
        <h2 class="error-title">Access Denied</h2>
        <p class="error-message">You do not have permission to access this resource. Please contact your administrator if you believe this is an error.</p>
        <a href="<?php echo defined('APP_URL') ? APP_URL : '/hotel'; ?>/index.php" class="btn-home"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg> Back to Home</a>
    </div>
</body>
</html>
