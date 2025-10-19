<?php
// Verificar que está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-gem"></i> BLOOM Admin
        </a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3">
                Hola, <?php echo $_SESSION['user_name']; ?>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </div>
</nav>