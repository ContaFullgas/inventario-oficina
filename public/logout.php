<?php

//Archivo logout.php

require_once __DIR__.'/../config/auth.php';
auth_logout();
// flash_set('ok', 'Sesión cerrada.');
// RUTA RELATIVA
header('Location: ../login.php', true, 303);
exit;
