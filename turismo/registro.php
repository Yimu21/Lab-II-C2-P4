<?php
// registro.php - Página de registro de nuevos usuarios
session_start();

if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'config/database.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre']   ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    // Validaciones
    if (empty($nombre) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (strlen($nombre) < 3) {
        $error = 'El nombre debe tener al menos 3 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del correo no es válido.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener mínimo 8 caracteres.';
    } elseif ($password !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $conn = getConnection();
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Este correo electrónico ya está registrado.';
        } else {
            $stmt->close();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nombre, $email, $hash);

            if ($stmt->execute()) {
                $success = 'Cuenta creada exitosamente. <a href="login.php">Inicia sesión</a>.';
            } else {
                $error = 'Error al crear la cuenta. Intenta de nuevo.';
            }
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turismo San Miguel – Registro</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --gold:#C8922A; --gold-lt:#E8B84B; --dark:#0D1B2A; --dark2:#162031; --white:#F5F0E8; --red-sm:#8B1A1A; }
        body { font-family:'Lato',sans-serif; background:var(--dark); color:var(--white); min-height:100vh; display:flex; align-items:center; justify-content:center; }
        body::before { content:''; position:fixed; inset:0; background:radial-gradient(ellipse 80% 60% at 20% 80%,rgba(200,146,42,.12) 0%,transparent 60%),radial-gradient(ellipse 60% 80% at 80% 20%,rgba(139,26,26,.10) 0%,transparent 60%); pointer-events:none; }
        .card { background:rgba(22,32,49,.92); border:1px solid rgba(200,146,42,.25); border-radius:4px; padding:2.5rem; width:100%; max-width:440px; backdrop-filter:blur(12px); box-shadow:0 30px 80px rgba(0,0,0,.5); animation:fadeUp .6s ease both; position:relative; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
        .card::before { content:''; position:absolute; top:0; left:10%; right:10%; height:2px; background:linear-gradient(90deg,transparent,var(--gold),transparent); }
        .brand { text-align:center; margin-bottom:1.8rem; }
        .brand h1 { font-family:'Playfair Display',serif; font-size:1.5rem; color:var(--gold-lt); }
        .brand p { font-size:.75rem; color:rgba(245,240,232,.5); letter-spacing:.12em; text-transform:uppercase; margin-top:.25rem; }
        .msg { border-radius:3px; padding:.7rem 1rem; font-size:.85rem; margin-bottom:1.2rem; }
        .error-box { background:rgba(139,26,26,.2); border:1px solid rgba(139,26,26,.5); color:#ff9999; }
        .success-box { background:rgba(26,100,50,.2); border:1px solid rgba(26,100,50,.5); color:#88ffaa; }
        .success-box a { color:var(--gold-lt); }
        label { display:block; font-size:.72rem; letter-spacing:.12em; text-transform:uppercase; color:rgba(245,240,232,.55); margin-bottom:.35rem; }
        input { width:100%; background:rgba(13,27,42,.8); border:1px solid rgba(200,146,42,.2); border-radius:3px; padding:.7rem 1rem; color:var(--white); font-family:'Lato',sans-serif; font-size:.93rem; transition:border-color .2s,box-shadow .2s; margin-bottom:1.1rem; }
        input:focus { outline:none; border-color:var(--gold); box-shadow:0 0 0 3px rgba(200,146,42,.12); }
        button { width:100%; background:linear-gradient(135deg,var(--gold),var(--red-sm)); border:none; border-radius:3px; padding:.82rem; color:#fff; font-family:'Lato',sans-serif; font-weight:700; font-size:.83rem; letter-spacing:.15em; text-transform:uppercase; cursor:pointer; margin-top:.3rem; transition:opacity .2s,transform .15s; }
        button:hover { opacity:.88; transform:translateY(-1px); }
        .login-link { text-align:center; margin-top:1.3rem; font-size:.82rem; color:rgba(245,240,232,.45); }
        .login-link a { color:var(--gold-lt); text-decoration:none; }
    </style>
</head>
<body>
<div class="card">
    <div class="brand">
        <h1>🏔️ Crear Cuenta</h1>
        <p>Turismo San Miguel</p>
    </div>

    <?php if ($error): ?>
        <div class="msg error-box">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="msg success-box">✅ <?= $success ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <label>Nombre completo</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" placeholder="Tu nombre" required>

        <label>Correo electrónico</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="correo@ejemplo.com" required>

        <label>Contraseña <span style="color:rgba(245,240,232,.4);font-size:.7rem">(mínimo 8 caracteres)</span></label>
        <input type="password" name="password" placeholder="••••••••" required>

        <label>Confirmar contraseña</label>
        <input type="password" name="confirm" placeholder="••••••••" required>

        <button type="submit">Crear Cuenta</button>
    </form>
    <p class="login-link">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
</div>
</body>
</html>
