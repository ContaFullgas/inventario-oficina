<?php

//Archivo login.php

require_once __DIR__.'/config/auth.php';

$err = null;

// Detectar si hay logo
$logoWebPath = 'assets/logo_FG.png';                        // <- aquí va tu imagen
$logoFsPath  = __DIR__ . '/assets/logo_FG.png';
$hasLogo     = is_file($logoFsPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $usuario = trim($_POST['usuario'] ?? '');
  $clave   = (string)($_POST['clave'] ?? '');

  if ($usuario === '' || $clave === '') {
    $err = 'Usuario y contraseña son obligatorios.';
  } else {
    if (auth_login($usuario, $clave)) {
      // RUTA RELATIVA al home
      header('Location: index.php?tab=inv#inv', true, 303);
      exit;
    } else {
      $err = 'Credenciales inválidas.';
    }
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ingresar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body{
      min-height:100vh;
      background-image: url("assets/fondo2.jpg");
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
      display:flex; align-items:center; justify-content:center;
      padding: 16px;
    }
    .login-card{
      width:100%; max-width:420px;
      border-radius:16px;
      box-shadow: 0 .75rem 2rem rgba(0,0,0,.25);
      overflow:hidden;
      background:#cfd4da;
    }
    .brand {
      background:#cfd4da;
      text-align:center;
      padding: 16px 12px 8px;
    }
    .brand-img{
      max-width: 160px;        /* ajusta tamaño del logo */
      max-height: 90px;
      height: auto;
      width: auto;
      filter: drop-shadow(0 2px 6px rgba(0,0,0,.2));
    }
    .brand-title{
      color:#fff;
      font-weight:700;
      letter-spacing:.5px;
    }

    /* ====== Reset básico ====== */
* { box-sizing: border-box; }
html, body { height: 100%; }
body {
  margin: 0;
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", sans-serif;
  /*background: linear-gradient(135deg, #ff8a00, #ffa94d);*/
  /* Fondo naranja */
  background-image: url("assets/fondo5.jpg");
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  background-attachment: fixed;
  color: #212529;
}

/* Si cargas Oswald desde Google Fonts, úsala donde convenga */
:root {
  --red-700: #84202a;
  --red-600: #b12a38;
  --red-500: #dc3546;
  --red-400: #e45d6b;
  --red-300: #ea8690;
  --bg-box: #3a6168;
  --bg-card: #fff;
  --text-muted: #6c757d;
  --ring: rgba(255, 0, 0, .35);
}

/* ====== Layout general ====== */
.login-wrap {
  min-height: 100vh;
  padding: 24px;
  display: grid;
  place-items: center;
}

/* ====== Encabezado / Marca ====== */
.brand {
  text-align: center;
  /* margin-bottom: 16px; */
}
.brand img {
  max-width: 280px;
  max-height: 200px;
  height: auto;
  width: auto;
  filter: drop-shadow(0 4px 10px rgba(0,0,0,.25));
  user-select: none;
}

/* ====== Tarjeta de Login ====== */
.login-card {
  width: 100%;
  max-width: 440px;
  background-color: $red-600;
  border-radius: 18px;
  overflow: hidden;
  /* Borde y sombra con tinte naranja */
  border: 1px solid rgba(255, 0, 0, 1.0);
  box-shadow: 0px 0px 10px 15px rgba(255, 0, 0, 0.38);
  padding: 22px;
}

/* Títulos de sección (Usuario / Contraseña) */
.section-title {
  font-weight: 600;
  color: #333;
  margin-bottom: 6px;
  letter-spacing: .2px;
}

/* ====== Inputs estilizados con Bootstrap ====== */
.input-group .input-group-text {
  background: #fff7f0;                  /* naranja MUY claro */
  border: 1px solid rgba(255, 0, 0, .35);
  color: var(--red-700);
}

.form-control {
  border: 1px solid rgba(255, 0, 0, .35);
  background: #fff; /* mejor contraste que blanco roto */
}

.form-control:focus,
.input-group .form-control:focus {
  border-color: var(--red-600);
  box-shadow: 0 0 0 .2rem var(--ring);
}

/* Filas meta (recuerdame, etc.) */
.meta-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

/* ====== Botón de Login ====== */
.btn-login {
  background: var(--red-700);
  border: 1px solid var(--red-700);
  color: #fff;
  border-radius: 10px;
  font-weight: 600;
  letter-spacing: .2px;
  transition: transform .04s ease, box-shadow .2s ease, background .2s ease;
  box-shadow: 0 6px 18px rgba(255, 0, 0, .35);
}

.btn-login:hover {
  background: var(--red-600);
  border-color: var(--red-600);
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(255, 0, 0, .45);
}

.btn-login:active {
  transform: translateY(0);
  box-shadow: 0 4px 12px rgba(255, 0, 0, .3) inset;
}

.btn-login:focus {
  box-shadow: 0 0 0 .2rem var(--red-400), 0 6px 18px rgba(255, 0, 0, .35);
}

/* ====== Alertas ====== */
.alert-danger {
  border-left: 4px solid #dc3545;
  box-shadow: 0 4px 16px rgba(220, 53, 69, .15);
}

/* ====== Responsivo ====== */
@media (max-width: 480px) {
  .login-card { padding: 18px; }
  .brand img { max-width: 160px; }
}

  </style>
</head>
<body>
  <div class="login-card">
    <div class="brand">
      <?php if ($hasLogo): ?>
        <img src="<?=htmlspecialchars($logoWebPath, ENT_QUOTES,'UTF-8')?>"
             alt="Logo" class="brand-img">
      <?php else: ?>
        <div class="brand-title">Inventario de Oficina</div>
      <?php endif; ?>
    </div>

    <div class="p-4 p-md-4">
      <?php if ($err): ?>
        <div class="alert alert-danger py-2"><?=$err?></div>
      <?php endif; ?>

      <form method="post" autocomplete="off" novalidate>
        <div class="input-group mb-3">
          <span class="input-group-text" id="basic-addon1"><i class="bi bi-person-fill"></i></span>
          <input type="text" name="usuario" class="form-control" required autofocus placeholder="Usuario">
        </div>
        <div class="input-group mb-3">
          <label class="input-group-text" id="basic-addon1"><i class="bi bi-key-fill"></i></label>
          <input type="password" name="clave" class="form-control" required placeholder="Contraseña">
        </div>
        <button class="btn btn-dark w-100" type="submit"><i class="bi bi-box-arrow-in-right"></i> Ingresar</button>
      </form>

      <div class="text-center mt-3">
        <small class="text-muted">
          <!-- Demo: <code>admin/admin123</code> · <code>consulta/consulta123</code>-->
        </small>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
