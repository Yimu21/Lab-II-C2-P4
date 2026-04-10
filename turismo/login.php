<?php
// login.php - Página de inicio de sesión
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Por favor completa todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del correo electrónico no es válido.';
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, nombre, password FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();
            if (password_verify($password, $usuario['password'])) {
                $_SESSION['usuario_id']   = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Correo o contraseña incorrectos.';
            }
        } else {
            $error = 'Correo o contraseña incorrectos.';
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
    <title>Turismo San Miguel – Iniciar Sesión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --gold:    #C8922A;
            --gold-lt: #E8B84B;
            --dark:    #0D1B2A;
            --dark2:   #162031;
            --white:   #F5F0E8;
            --red-sm:  #8B1A1A;
        }

        body {
            font-family: 'Lato', sans-serif;
            background: var(--dark);
            color: var(--white);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Fondo decorativo */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 80%, rgba(200,146,42,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 20%, rgba(139,26,26,0.10) 0%, transparent 60%);
            pointer-events: none;
        }

        .hero-text {
            position: fixed;
            top: 50%;
            left: -5vw;
            transform: translateY(-50%);
            font-family: 'Playfair Display', serif;
            font-size: clamp(6rem, 15vw, 18rem);
            font-weight: 900;
            color: rgba(200,146,42,0.04);
            white-space: nowrap;
            pointer-events: none;
            user-select: none;
        }

        .card {
            background: rgba(22,32,49,0.92);
            border: 1px solid rgba(200,146,42,0.25);
            border-radius: 4px;
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 420px;
            position: relative;
            backdrop-filter: blur(12px);
            box-shadow: 0 30px 80px rgba(0,0,0,0.5), 0 0 0 1px rgba(200,146,42,0.1);
            animation: fadeUp 0.6s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 10%; right: 10%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }

        .brand {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-icon {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .brand h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            color: var(--gold-lt);
            letter-spacing: 0.02em;
        }

        .brand p {
            font-size: 0.78rem;
            color: rgba(245,240,232,0.5);
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-top: 0.25rem;
        }

        .error-box {
            background: rgba(139,26,26,0.2);
            border: 1px solid rgba(139,26,26,0.5);
            border-radius: 3px;
            padding: 0.7rem 1rem;
            font-size: 0.85rem;
            color: #ff9999;
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-size: 0.72rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(245,240,232,0.55);
            margin-bottom: 0.4rem;
        }

        input {
            width: 100%;
            background: rgba(13,27,42,0.8);
            border: 1px solid rgba(200,146,42,0.2);
            border-radius: 3px;
            padding: 0.75rem 1rem;
            color: var(--white);
            font-family: 'Lato', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            margin-bottom: 1.25rem;
        }

        input:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(200,146,42,0.12);
        }

        button[type="submit"] {
            width: 100%;
            background: linear-gradient(135deg, var(--gold), var(--red-sm));
            border: none;
            border-radius: 3px;
            padding: 0.85rem;
            color: #fff;
            font-family: 'Lato', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: opacity 0.2s, transform 0.15s;
        }

        button[type="submit"]:hover { opacity: 0.88; transform: translateY(-1px); }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.82rem;
            color: rgba(245,240,232,0.45);
        }

        .register-link a {
            color: var(--gold-lt);
            text-decoration: none;
        }

        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="hero-text">SM</div>
    <div class="card">
        <div class="brand">
            <span class="brand-icon">🏔️</span>
            <h1>Turismo San Miguel</h1>
            <p>Descubre el oriente de El Salvador</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="correo@ejemplo.com" required>

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••" required>

            <button type="submit">Ingresar</button>
        </form>

        <p class="register-link">
            ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
        </p>
    </div>
</body>
</html>
