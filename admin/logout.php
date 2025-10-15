<?php
include_once('../includes/functions.php');

// Destruir sesión
session_destroy();

// Redirigir al login
redirect('login.php');
?>