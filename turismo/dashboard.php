<?php
// dashboard.php - Panel principal (sin login, usuario hardcodeado)
session_start();

require_once 'config/database.php';

// ── Usuario fijo (debe existir en la tabla usuarios) ──────────────────
// Ajusta estos valores si cambias el usuario en la BD
$uid             = 1;
$usuario_nombre  = 'Admin San Miguel';

$conn    = getConnection();
$errores = [];
$success = '';

// ── Insertar nuevo lugar ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $nombre      = trim($_POST['nombre']      ?? '');
    $categoria   = trim($_POST['categoria']   ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $direccion   = trim($_POST['direccion']   ?? '');
    $municipio   = trim($_POST['municipio']   ?? '');
    $horario     = trim($_POST['horario']     ?? '');
    $entrada     = $_POST['entrada']     ?? '';
    $calificacion= $_POST['calificacion']?? '';

    $cats_validas = ['natural','cultural','historico','religioso','recreativo'];

    if (empty($nombre))                          $errores[] = 'El nombre del lugar es obligatorio.';
    elseif (strlen($nombre) < 3)                 $errores[] = 'El nombre debe tener al menos 3 caracteres.';
    if (!in_array($categoria, $cats_validas))    $errores[] = 'Selecciona una categoría válida.';
    if (empty($descripcion))                     $errores[] = 'La descripción es obligatoria.';
    elseif (strlen($descripcion) < 10)           $errores[] = 'La descripción debe tener al menos 10 caracteres.';
    if (empty($direccion))                       $errores[] = 'La dirección es obligatoria.';
    if (empty($municipio))                       $errores[] = 'El municipio es obligatorio.';
    if ($entrada !== '' && !is_numeric($entrada)) $errores[] = 'El precio de entrada debe ser un número.';
    if ($entrada !== '' && $entrada < 0)         $errores[] = 'El precio no puede ser negativo.';
    if (!in_array($calificacion, ['1','2','3','4','5'])) $errores[] = 'Selecciona una calificación del 1 al 5.';

    if (empty($errores)) {
        $entrada_val = ($entrada === '') ? 0.00 : (float)$entrada;
        $stmt = $conn->prepare("INSERT INTO lugares (nombre, categoria, descripcion, direccion, municipio, horario, entrada, calificacion, usuario_id) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssdii", $nombre, $categoria, $descripcion, $direccion, $municipio, $horario, $entrada_val, $calificacion, $uid);
        if ($stmt->execute()) {
            $success = "¡Lugar turístico agregado exitosamente!";
            // Limpiar POST para no repoblar el formulario
            $_POST = [];
        } else {
            $errores[] = "Error al guardar. Intenta de nuevo.";
        }
        $stmt->close();
    }
}

// ── Eliminar lugar ────────────────────────────────────────────────────
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id_del = (int)$_GET['eliminar'];
    $stmt = $conn->prepare("DELETE FROM lugares WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id_del, $uid);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php?deleted=1");
    exit();
}

// ── Buscar / listar lugares ───────────────────────────────────────────
$buscar     = trim($_GET['buscar']      ?? '');
$filtro_cat = trim($_GET['categoria_f'] ?? '');

$sql = "SELECT l.*, u.nombre AS autor FROM lugares l 
        JOIN usuarios u ON l.usuario_id = u.id 
        WHERE 1=1";
$params = []; $types = '';

if ($buscar) {
    $sql .= " AND (l.nombre LIKE ? OR l.municipio LIKE ?)";
    $like = "%$buscar%";
    $params[] = $like; $params[] = $like;
    $types .= 'ss';
}
if ($filtro_cat) {
    $sql .= " AND l.categoria = ?";
    $params[] = $filtro_cat;
    $types .= 's';
}
$sql .= " ORDER BY l.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$lugares = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$estrellas  = fn($n) => str_repeat('★', $n) . str_repeat('☆', 5 - $n);
$cat_labels = [
    'natural'   => '🌿 Natural',
    'cultural'  => '🎭 Cultural',
    'historico' => '🏛️ Histórico',
    'religioso' => '⛪ Religioso',
    'recreativo'=> '🎡 Recreativo',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turismo San Miguel – Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --gold:#C8922A; --gold-lt:#E8B84B; --dark:#0D1B2A; --dark2:#162031;
            --surface:#1A2840; --white:#F5F0E8; --red-sm:#8B1A1A;
            --green:#1A6432; --border:rgba(200,146,42,.18);
        }
        body { font-family:'Lato',sans-serif; background:var(--dark); color:var(--white); min-height:100vh; }

        /* ── NAV ── */
        nav {
            background:rgba(22,32,49,.97);
            border-bottom:1px solid var(--border);
            padding:0 2rem;
            display:flex; align-items:center; justify-content:space-between;
            height:60px;
            position:sticky; top:0; z-index:100;
            backdrop-filter:blur(8px);
        }
        .nav-brand { font-family:'Playfair Display',serif; font-size:1.15rem; color:var(--gold-lt); display:flex; align-items:center; gap:.5rem; }
        .nav-right  { display:flex; align-items:center; gap:1.5rem; font-size:.85rem; }
        .nav-right span { color:rgba(245,240,232,.6); }

        /* ── LAYOUT ── */
        .container { max-width:1200px; margin:0 auto; padding:2rem 1.5rem; }

        /* ── ALERTS ── */
        .alert { border-radius:3px; padding:.75rem 1.1rem; font-size:.88rem; margin-bottom:1.5rem; }
        .alert-error   { background:rgba(139,26,26,.2);  border:1px solid rgba(139,26,26,.4);  color:#ff9999; }
        .alert-success { background:rgba(26,100,50,.2);  border:1px solid rgba(26,100,50,.4);  color:#88ffaa; }

        /* ── SECTION TITLES ── */
        .section-title {
            font-family:'Playfair Display',serif;
            font-size:1.4rem; color:var(--gold-lt);
            margin-bottom:1.2rem;
            padding-bottom:.6rem;
            border-bottom:1px solid var(--border);
        }

        /* ── FORM ── */
        .form-card {
            background:var(--surface);
            border:1px solid var(--border);
            border-radius:4px; padding:1.8rem;
            margin-bottom:2.5rem;
        }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .form-full  { grid-column:1/-1; }
        label { display:block; font-size:.72rem; letter-spacing:.1em; text-transform:uppercase; color:rgba(245,240,232,.5); margin-bottom:.35rem; }
        input, select, textarea {
            width:100%; background:rgba(13,27,42,.8);
            border:1px solid rgba(200,146,42,.18);
            border-radius:3px; padding:.65rem .9rem;
            color:var(--white); font-family:'Lato',sans-serif; font-size:.9rem;
            transition:border-color .2s, box-shadow .2s;
        }
        input:focus, select:focus, textarea:focus { outline:none; border-color:var(--gold); box-shadow:0 0 0 3px rgba(200,146,42,.1); }
        textarea { resize:vertical; min-height:90px; }
        select option { background:#1A2840; }
        .btn-submit {
            background:linear-gradient(135deg,var(--gold),var(--red-sm));
            border:none; border-radius:3px; padding:.75rem 2rem;
            color:#fff; font-family:'Lato',sans-serif; font-weight:700;
            font-size:.82rem; letter-spacing:.12em; text-transform:uppercase;
            cursor:pointer; transition:opacity .2s, transform .15s;
            margin-top:1rem;
        }
        .btn-submit:hover { opacity:.88; transform:translateY(-1px); }

        /* ── FILTERS ── */
        .filter-bar { display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; align-items:center; }
        .filter-bar input, .filter-bar select { max-width:220px; }
        .btn-filter { background:rgba(200,146,42,.15); border:1px solid var(--gold); border-radius:3px; padding:.55rem 1.2rem; color:var(--gold-lt); font-size:.82rem; cursor:pointer; letter-spacing:.06em; }
        .btn-filter:hover { background:rgba(200,146,42,.28); }

        /* ── TABLE ── */
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:.87rem; }
        thead th {
            background:rgba(200,146,42,.1);
            color:var(--gold-lt); font-size:.72rem;
            letter-spacing:.12em; text-transform:uppercase;
            padding:.75rem 1rem; text-align:left;
            border-bottom:1px solid var(--border);
            white-space:nowrap;
        }
        tbody tr { border-bottom:1px solid rgba(200,146,42,.07); transition:background .15s; }
        tbody tr:hover { background:rgba(200,146,42,.05); }
        td { padding:.7rem 1rem; vertical-align:top; }
        .cat-badge {
            display:inline-block; padding:.2rem .6rem;
            border-radius:50px; font-size:.72rem; font-weight:700;
            letter-spacing:.06em;
        }
        .cat-natural    { background:rgba(26,150,50,.2);  color:#88ffaa; border:1px solid rgba(26,150,50,.4); }
        .cat-cultural   { background:rgba(150,100,26,.2); color:#ffcc88; border:1px solid rgba(150,100,26,.4); }
        .cat-historico  { background:rgba(100,26,150,.2); color:#cc99ff; border:1px solid rgba(100,26,150,.4); }
        .cat-religioso  { background:rgba(26,100,150,.2); color:#88ccff; border:1px solid rgba(26,100,150,.4); }
        .cat-recreativo { background:rgba(26,150,150,.2); color:#88ffee; border:1px solid rgba(26,150,150,.4); }
        .stars   { color:var(--gold); font-size:1rem; }
        .btn-del { background:transparent; border:1px solid rgba(139,26,26,.5); color:#ff9999; padding:.25rem .65rem; border-radius:3px; font-size:.78rem; cursor:pointer; transition:background .2s; }
        .btn-del:hover { background:rgba(139,26,26,.3); }
        .empty-row td { text-align:center; color:rgba(245,240,232,.35); padding:2.5rem; font-style:italic; }
    </style>
</head>
<body>
<nav>
    <div class="nav-brand">🏔️ Turismo San Miguel</div>
    <div class="nav-right">
        <span>👤 <?= htmlspecialchars($usuario_nombre) ?></span>
    </div>
</nav>

<div class="container">

    <!-- ── FORMULARIO ── -->
    <div class="form-card">
        <h2 class="section-title">➕ Agregar Lugar Turístico</h2>

        <?php if ($errores): ?>
            <div class="alert alert-error">
                <?php foreach($errores as $e): ?>⚠️ <?= htmlspecialchars($e) ?><br><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">🗑️ Lugar eliminado correctamente.</div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-grid">
                <div>
                    <label>Nombre del lugar *</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" placeholder="Ej: Laguna El Jocotal" required>
                </div>
                <div>
                    <label>Categoría *</label>
                    <select name="categoria" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach($cat_labels as $k => $v): ?>
                            <option value="<?= $k ?>" <?= (($_POST['categoria'] ?? '') === $k) ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Municipio *</label>
                    <input type="text" name="municipio" value="<?= htmlspecialchars($_POST['municipio'] ?? '') ?>" placeholder="Ej: San Miguel" required>
                </div>
                <div>
                    <label>Dirección *</label>
                    <input type="text" name="direccion" value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>" placeholder="Dirección o referencia" required>
                </div>
                <div>
                    <label>Horario de visita</label>
                    <input type="text" name="horario" value="<?= htmlspecialchars($_POST['horario'] ?? '') ?>" placeholder="Ej: Lun-Dom 8:00-17:00">
                </div>
                <div>
                    <label>Precio de entrada ($) <span style="color:rgba(245,240,232,.35);font-size:.7rem">0 = gratis</span></label>
                    <input type="number" name="entrada" min="0" step="0.25" value="<?= htmlspecialchars($_POST['entrada'] ?? '0') ?>" placeholder="0.00">
                </div>
                <div>
                    <label>Calificación (1–5) *</label>
                    <select name="calificacion" required>
                        <option value="">-- Seleccionar --</option>
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= (($_POST['calificacion'] ?? '') == $i) ? 'selected' : '' ?>><?= str_repeat('★', $i) ?> (<?= $i ?>)</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-full">
                    <label>Descripción *</label>
                    <textarea name="descripcion" placeholder="Describe el lugar turístico..."><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                </div>
            </div>
            <button type="submit" name="agregar" class="btn-submit">Agregar Lugar</button>
        </form>
    </div>

    <!-- ── TABLA ── -->
    <h2 class="section-title">🗺️ Lugares Turísticos Registrados</h2>

    <form method="GET" class="filter-bar">
        <input type="text" name="buscar" value="<?= htmlspecialchars($buscar) ?>" placeholder="🔍 Buscar nombre o municipio...">
        <select name="categoria_f">
            <option value="">Todas las categorías</option>
            <?php foreach($cat_labels as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($filtro_cat === $k) ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-filter">Filtrar</button>
        <a href="dashboard.php"><button type="button" class="btn-filter" style="background:transparent">✕ Limpiar</button></a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Municipio</th>
                    <th>Dirección</th>
                    <th>Horario</th>
                    <th>Entrada</th>
                    <th>Calificación</th>
                    <th>Agregado por</th>
                    <th>Fecha</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lugares)): ?>
                    <tr class="empty-row"><td colspan="11">No hay lugares registrados todavía.</td></tr>
                <?php else: ?>
                    <?php foreach($lugares as $i => $l): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($l['nombre']) ?></strong><br>
                            <small style="color:rgba(245,240,232,.4)"><?= htmlspecialchars(substr($l['descripcion'], 0, 50)) ?>…</small>
                        </td>
                        <td><span class="cat-badge cat-<?= $l['categoria'] ?>"><?= $cat_labels[$l['categoria']] ?></span></td>
                        <td><?= htmlspecialchars($l['municipio']) ?></td>
                        <td><?= htmlspecialchars($l['direccion']) ?></td>
                        <td><?= htmlspecialchars($l['horario'] ?: '—') ?></td>
                        <td>
                            <?= $l['entrada'] > 0
                                ? '$' . number_format($l['entrada'], 2)
                                : '<span style="color:rgba(245,240,232,.4)">Gratis</span>' ?>
                        </td>
                        <td><span class="stars"><?= $estrellas($l['calificacion']) ?></span></td>
                        <td><?= htmlspecialchars($l['autor']) ?></td>
                        <td style="white-space:nowrap"><?= date('d/m/Y', strtotime($l['created_at'])) ?></td>
                        <td>
                            <?php if ($l['usuario_id'] == $uid): ?>
                                <a href="dashboard.php?eliminar=<?= $l['id'] ?>" onclick="return confirm('¿Eliminar este lugar?')">
                                    <button class="btn-del">🗑️ Eliminar</button>
                                </a>
                            <?php else: ?>
                                <span style="color:rgba(245,240,232,.25);font-size:.75rem">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
