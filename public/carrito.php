<?php
include_once('../includes/database.php');
include_once('../includes/functions.php');

$database = new Database();
$db = $database->getConnection();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - Marco Cos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar (igual que index.php) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-gem me-2"></i>Marco Cos
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>Inicio
                </a>
                <a class="nav-link position-relative" href="carrito.php">
                    <i class="fas fa-shopping-cart me-1"></i>Carrito
                    <span class="badge bg-danger rounded-pill" id="cart-count">0</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4"><i class="fas fa-shopping-cart me-2"></i>Tu Carrito de Compras</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div id="cart-items">
                            <!-- Los productos del carrito se cargan aquí con JavaScript -->
                            <div class="text-center py-4" id="empty-cart-message">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <h5>Tu carrito está vacío</h5>
                                <p class="text-muted">Agrega algunos productos para continuar</p>
                                <a href="index.php" class="btn btn-primary">Ir a Comprar</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Resumen del Pedido</h5>
                        <div id="order-summary">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span id="subtotal">Gs. 0</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong id="total">Gs. 0</strong>
                            </div>
                            <button class="btn btn-primary w-100 mb-3" id="checkout-btn" disabled>
                                <i class="fas fa-whatsapp me-2"></i>Completar Pedido por WhatsApp
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-arrow-left me-2"></i>Seguir Comprando
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Información de contacto -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h6 class="card-title">¿Necesitas ayuda?</h6>
                        <p class="card-text small">
                            <i class="fas fa-phone me-2"></i>+595 972 366-265<br>
                            <i class="fas fa-clock me-2"></i>Lun-Vie: 8:00-18:00
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        let cart = JSON.parse(localStorage.getItem('marccos_cart')) || [];
        
        function updateCartDisplay() {
            const cartItems = $('#cart-items');
            const emptyCart = $('#empty-cart-message');
            const checkoutBtn = $('#checkout-btn');
            
            if (cart.length === 0) {
                emptyCart.show();
                checkoutBtn.prop('disabled', true);
                return;
            }
            
            emptyCart.hide();
            checkoutBtn.prop('disabled', false);
            
            let html = '';
            let subtotal = 0;
            
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                
                html += `
                    <div class="cart-item row align-items-center mb-3 pb-3 border-bottom">
                        <div class="col-3">
                        ${item.image ? 
    `<img src="../uploads/products/${item.image}" class="img-thumbnail" alt="${item.name}" style="width: 250px; height: 200px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;">` :
    `<div class="img-thumbnail bg-light d-flex align-items-center justify-content-center" style="width: 250px; height: 200px; border-radius: 8px; border: 1px solid #ddd;">
        <i class="fas fa-image text-muted"></i>
    </div>`
}
                        </div>
                        <div class="col-5">
                            <h6 class="mb-1">${item.name}</h6>
                            <p class="text-muted mb-0">Gs. ${item.price.toLocaleString()}</p>
                        </div>
                        <div class="col-2">
                            <input type="number" class="form-control form-control-sm quantity-input" 
                                   value="${item.quantity}" min="1" data-index="${index}">
                        </div>
                        <div class="col-2 text-end">
                            <strong>Gs. ${itemTotal.toLocaleString()}</strong>
                            <br>
                            <button class="btn btn-sm btn-outline-danger mt-1 remove-item" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            cartItems.html(html);
            $('#subtotal').text('Gs. ' + subtotal.toLocaleString());
            $('#total').text('Gs. ' + subtotal.toLocaleString());
            updateCartCount();
        }
        
        function updateCartCount() {
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            $('#cart-count').text(totalItems);
        }
        
        function saveCart() {
            localStorage.setItem('marccos_cart', JSON.stringify(cart));
        }
        
        // Eventos
        $(document).on('change', '.quantity-input', function() {
            const index = $(this).data('index');
            const quantity = parseInt($(this).val());
            
            if (quantity > 0) {
                cart[index].quantity = quantity;
                saveCart();
                updateCartDisplay();
            }
        });
        
        $(document).on('click', '.remove-item', function() {
            const index = $(this).data('index');
            cart.splice(index, 1);
            saveCart();
            updateCartDisplay();
        });
        
        $('#checkout-btn').click(function() {
            if (cart.length === 0) return;
            
            // Crear mensaje para WhatsApp
            let message = "¡Hola! Me interesan los siguientes productos:%0A%0A";
            let total = 0;
            
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                message += `• ${item.name} - ${item.quantity} x Gs. ${item.price.toLocaleString()}%0A`;
            });
            
            message += `%0ATotal: Gs. ${total.toLocaleString()}%0A%0A`;
            message += "Por favor, contactame para coordinar la compra. ¡Gracias!";
            
            // Abrir WhatsApp
            const phone = "595972366265"; // Tu número sin el +
            window.open(`https://wa.me/${phone}?text=${message}`, '_blank');
        });
        
        // Inicializar
        updateCartDisplay();
    </script>
</body>
</html>