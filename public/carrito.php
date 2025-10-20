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
    <title>Carrito de Compras - BLOOM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #e74c3c;
            --accent-color: #3498db;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --bg-light: #f8f9fa;
            --border-color: #e0e0e0;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: white;
            color: var(--text-dark);
        }
        
        /* Navbar estilo BLOOM */
        .navbar-bloom {
            background: white;
            border-bottom: 2px solid var(--border-color);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .navbar-brand-bloom {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-color) !important;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .nav-link-bloom {
            color: var(--text-dark) !important;
            font-weight: 600;
            font-size: 0.95rem;
            margin: 0 15px;
            padding: 8px 0 !important;
            position: relative;
        }
        
        .nav-link-bloom:hover,
        .nav-link-bloom.active {
            color: var(--primary-color) !important;
        }
        
        .cart-icon-bloom {
            color: var(--primary-color);
            font-size: 1.4rem;
            position: relative;
        }
        
        /* Cart Badge BLOOM */
        .cart-badge-bloom {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--secondary-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
            text-align: center;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        /* Cart Cards */
        .cart-card {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            background: white;
            transition: all 0.3s ease;
        }
        
        .cart-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .cart-card .card-header {
            background: var(--bg-light);
            border-bottom: 1px solid var(--border-color);
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .summary-card {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            background: white;
        }
        
        .summary-card .card-header {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        /* Cart Items */
        .cart-item {
            transition: all 0.3s ease;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item.removing {
            opacity: 0;
            height: 0;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        
        .product-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        .product-title {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        
        .product-price {
            color: var(--secondary-color);
            font-weight: 600;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 5px;
        }
        
        .btn-quantity {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-color);
            background: white;
        }
        
        .btn-quantity:hover {
            background: var(--bg-light);
        }
        
        /* Buttons */
        .btn-primary-bloom {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary-bloom:hover {
            background: #1a252f;
        }
        
        .btn-success-bloom {
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-success-bloom:hover {
            background: #c0392b;
        }
        
        .btn-outline-primary-bloom {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
            border-radius: 6px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-outline-primary-bloom:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-danger-bloom {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 15px;
            font-weight: 600;
        }
        
        /* Empty Cart */
        .empty-cart-icon {
            font-size: 4rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }
        
        .cart-items-container {
            min-height: 200px;
        }
        
        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .toast-success {
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 8px;
        }
        
        .toast-danger {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
        }
        
        /* Contact Card */
        .contact-card {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: white;
        }
        
        .contact-card .card-body {
            padding: 20px;
            text-align: center;
        }
        
        /* Modal */
        .modal-header {
            background: var(--primary-color);
            color: white;
            border: none;
        }
        
        .modal-header .btn-close {
            filter: invert(1);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar-brand-bloom {
                font-size: 1.6rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .product-thumb {
                width: 60px;
                height: 60px;
            }
            
            .cart-item {
                padding: 10px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-bloom sticky-top">
        <div class="container">
            <a class="navbar-brand navbar-brand-bloom" href="index.php">
                BLOOM
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link nav-link-bloom" href="index.php">
                    <i class="fas fa-home me-1"></i>INICIO
                </a>
                <a class="nav-link nav-link-bloom position-relative" href="carrito.php">
                    <i class="fas fa-shopping-bag cart-icon-bloom"></i>
                    <span class="cart-badge-bloom" id="cart-count">0</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Container para notificaciones Toast -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><i class="fas fa-shopping-bag me-2"></i>Tu Carrito de Compras</h1>
            <p class="page-subtitle">Revisa y gestiona tus productos seleccionados</p>
        </div>
    </section>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-8">
                <div class="cart-card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Productos en el Carrito</h5>
                        <button class="btn btn-danger-bloom btn-sm" id="clear-cart-btn" style="display: none;">
                            <i class="fas fa-trash me-1"></i>Vaciar Carrito
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="cart-items-container" id="cart-items-container">
                            <div id="cart-items">
                                <!-- Los productos del carrito se cargan aquí con JavaScript -->
                            </div>
                            
                            <div class="text-center py-5" id="empty-cart-message">
                                <div class="empty-cart-icon">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <h4 class="text-muted mb-3">Tu carrito está vacío</h4>
                                <p class="text-muted mb-4">Agrega algunos productos para continuar</p>
                                <a href="index.php" class="btn btn-primary-bloom">
                                    <i class="fas fa-store me-2"></i>Ir a Comprar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="summary-card sticky-top" style="top: 100px;">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Resumen del Pedido</h5>
                    </div>
                    <div class="card-body">
                        <div id="order-summary">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span id="subtotal">GS. 0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Envío:</span>
                                <span class="text-success">Gratis</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong id="total" class="text-success">GS. 0</strong>
                            </div>
                            <button class="btn btn-success-bloom w-100 mb-3" id="checkout-btn" disabled>
                                <i class="fab fa-whatsapp me-2"></i>Completar Pedido por WhatsApp
                            </button>
                            <a href="catalogo.php" class="btn btn-outline-primary-bloom w-100">
                                <i class="fas fa-arrow-left me-2"></i>Seguir Comprando
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Información de contacto -->
                <div class="contact-card mt-4">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fas fa-headset me-2"></i>¿Necesitas ayuda?</h6>
                        <p class="small mb-3">
                            <i class="fas fa-phone me-1"></i>+595 976 588694<br>
                            <i class="fas fa-clock me-1"></i>Lun-Vie: 8:00-18:00
                        </p>
                        <a href="https://wa.me/595976588694" target="_blank" class="btn btn-success-bloom btn-sm w-100">
                            <i class="fab fa-whatsapp me-1"></i>Contactar por WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para datos del cliente -->
    <div class="modal fade" id="clienteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>Un último paso
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Para contactarte sobre tu pedido:</p>
                    
                    <div class="mb-3">
                        <label for="cliente_nombre" class="form-label">¿Cómo te llamas? *</label>
                        <input type="text" class="form-control" id="cliente_nombre" 
                               placeholder="Tu nombre completo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cliente_telefono" class="form-label">Tu número de WhatsApp *</label>
                        <input type="tel" class="form-control" id="cliente_telefono" 
                               placeholder="0972 366-265" required>
                        <small class="text-muted">Ej: 0972366265, 0985123456</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Te contactaremos por WhatsApp para confirmar disponibilidad y coordinar el pago.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary-bloom" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success-bloom" id="modal-confirm-btn">
                        <i class="fab fa-whatsapp me-2"></i>Enviar Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        let cart = JSON.parse(localStorage.getItem('bloom_cart')) || [];
        
        // Función para mostrar notificaciones bonitas
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            
            const toastHTML = `
                <div id="${toastId}" class="toast toast-${type} align-items-center" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHTML);
            const toastElement = new bootstrap.Toast(document.getElementById(toastId));
            toastElement.show();
            
            // Remover el toast del DOM después de que se oculte
            document.getElementById(toastId).addEventListener('hidden.bs.toast', function () {
                this.remove();
            });
        }
        
        // Función para actualizar el carrito en tiempo real
        function updateCartInRealTime() {
            updateCartDisplay();
            updateCartCount();
            updateClearCartButton();
        }
        
        function updateCartDisplay() {
            const cartItems = document.getElementById('cart-items');
            const emptyCart = document.getElementById('empty-cart-message');
            const checkoutBtn = document.getElementById('checkout-btn');
            
            if (cart.length === 0) {
                cartItems.innerHTML = '';
                emptyCart.style.display = 'block';
                checkoutBtn.disabled = true;
                return;
            }
            
            emptyCart.style.display = 'none';
            checkoutBtn.disabled = false;
            
            let html = '';
            let subtotal = 0;
            
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                
                html += `
                    <div class="cart-item row align-items-center" id="cart-item-${index}">
                        <div class="col-3 col-md-2">
                            ${item.image ? 
                                `<img src="../uploads/products/${item.image}" class="product-thumb" alt="${item.name}">` :
                                `<div class="product-thumb bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-gem text-muted"></i>
                                </div>`
                            }
                        </div>
                        <div class="col-5 col-md-4">
                            <h6 class="product-title mb-1">${item.name}</h6>
                            <p class="product-price mb-0">GS. ${item.price.toLocaleString()}</p>
                        </div>
                        <div class="col-4 col-md-4">
                            <div class="input-group input-group-sm">
                                <button class="btn btn-quantity minus-btn" type="button" data-index="${index}">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="form-control quantity-input" 
                                       value="${item.quantity}" min="1" data-index="${index}">
                                <button class="btn btn-quantity plus-btn" type="button" data-index="${index}">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-2 text-start text-md-end mt-2 mt-md-0">
                            <strong class="product-price d-block">GS. ${itemTotal.toLocaleString()}</strong>
                            <button class="btn btn-sm btn-danger-bloom mt-1 remove-item" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            cartItems.innerHTML = html;
            document.getElementById('subtotal').textContent = 'GS. ' + subtotal.toLocaleString();
            document.getElementById('total').textContent = 'GS. ' + subtotal.toLocaleString();
        }
        
        function updateCartCount() {
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cart-count').textContent = totalItems;
        }
        
        function updateClearCartButton() {
            const clearCartBtn = document.getElementById('clear-cart-btn');
            if (cart.length === 0) {
                clearCartBtn.style.display = 'none';
            } else {
                clearCartBtn.style.display = 'block';
            }
        }
        
        function saveCart() {
            localStorage.setItem('bloom_cart', JSON.stringify(cart));
        }
        
        function removeItemWithAnimation(index) {
            const itemElement = document.getElementById(`cart-item-${index}`);
            if (itemElement) {
                itemElement.classList.add('removing');
                
                setTimeout(() => {
                    cart.splice(index, 1);
                    saveCart();
                    updateCartInRealTime();
                    showToast('Producto eliminado del carrito', 'danger');
                }, 300);
            }
        }
        
        // Función para actualizar cantidad
        function updateQuantity(index, newQuantity) {
            if (newQuantity > 0) {
                cart[index].quantity = newQuantity;
                saveCart();
                updateCartInRealTime();
                showToast('Cantidad actualizada', 'success');
            }
        }

        // Función para limpiar y validar teléfono
        function formatearTelefono(telefono) {
            return telefono.replace(/\D/g, '');
        }

        // Función para validar teléfono paraguayo
        function validarTelefono(telefono) {
            const telefonoLimpio = telefono.replace(/\D/g, '');
            
            const patrones = [
                /^09[1-9]\d{6}$/,      // 0972366265
                /^9[1-9]\d{6}$/,       // 972366265
                /^5959[1-9]\d{6}$/,    // 595972366265
                /^09[1-9]\d{7}$/       // 09851234567
            ];
            
            return patrones.some(patron => patron.test(telefonoLimpio));
        }

        // Función para guardar pedido en BD
        async function guardarPedidoEnBD(nombre, telefono) {
            const checkoutBtn = document.getElementById('checkout-btn');
            const originalText = checkoutBtn.innerHTML;
            checkoutBtn.disabled = true;
            checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
            
            try {
                // Convertir nombre a mayúsculas y limpiar teléfono
                const nombreMayusculas = nombre.toUpperCase();
                const telefonoLimpio = formatearTelefono(telefono);
                
                // Preparar productos
                const productosParaBD = cart.map(item => ({
                    id: item.id,
                    name: item.name,
                    price: item.price,
                    quantity: item.quantity,
                    image: item.image || ''
                }));
                
                const response = await fetch('../includes/save_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        cliente_nombre: nombreMayusculas,
                        cliente_telefono: telefonoLimpio,
                        productos: productosParaBD,
                        total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
                    })
                });
                
                const result = await response.json();
                
                if (result.success && result.pedido_id) {
                    // Mensaje ultra compacto para WhatsApp
                    let message = `PEDIDO num ${result.pedido_id}%0A${nombreMayusculas}%0A%0A`;
                    
                    cart.forEach(item => {
                        message += `${item.quantity}x ${item.name}%0A`;
                    });
                    
                    message += `%0ACONFIRMAR DISPONIBILIDAD`;
                    
                    // Abrir WhatsApp
                    const whatsappUrl = `https://wa.me/595976588694?text=${message}`;
                    window.open(whatsappUrl, '_blank');
                    
                    // Limpiar carrito después de enviar
                    setTimeout(() => {
                        cart = [];
                        saveCart();
                        updateCartInRealTime();
                        showToast('¡Pedido #' + result.pedido_id + ' enviado correctamente!', 'success');
                        
                        // Restaurar botón
                        checkoutBtn.disabled = false;
                        checkoutBtn.innerHTML = originalText;
                    }, 2000);
                    
                } else {
                    const errorMsg = result.error || 'No se pudo obtener el ID del pedido';
                    showToast('Error: ' + errorMsg, 'danger');
                    checkoutBtn.disabled = false;
                    checkoutBtn.innerHTML = originalText;
                }
                
            } catch (error) {
                showToast('Error de conexión. Intenta nuevamente.', 'danger');
                checkoutBtn.disabled = false;
                checkoutBtn.innerHTML = originalText;
            }
        }

        // Función para mostrar modal y capturar datos
        function mostrarModalCliente() {
            const modal = new bootstrap.Modal(document.getElementById('clienteModal'));
            modal.show();
            
            // Limpiar formulario
            document.getElementById('cliente_nombre').value = '';
            document.getElementById('cliente_telefono').value = '';
        }
        
        // Event Listeners
        document.addEventListener('click', function(e) {
            // Botón menos
            if (e.target.closest('.minus-btn')) {
                const btn = e.target.closest('.minus-btn');
                const index = parseInt(btn.dataset.index);
                if (cart[index].quantity > 1) {
                    const newQuantity = cart[index].quantity - 1;
                    updateQuantity(index, newQuantity);
                }
            }
            
            // Botón más
            if (e.target.closest('.plus-btn')) {
                const btn = e.target.closest('.plus-btn');
                const index = parseInt(btn.dataset.index);
                const newQuantity = cart[index].quantity + 1;
                updateQuantity(index, newQuantity);
            }
            
            // Eliminar producto
            if (e.target.closest('.remove-item')) {
                const btn = e.target.closest('.remove-item');
                const index = parseInt(btn.dataset.index);
                removeItemWithAnimation(index);
            }
            
            // Vaciar carrito
            if (e.target.closest('#clear-cart-btn')) {
                if (cart.length === 0) return;
                
                if (confirm('¿Estás seguro de que quieres vaciar todo el carrito?')) {
                    const cartItems = document.querySelectorAll('.cart-item');
                    cartItems.forEach((item, index) => {
                        setTimeout(() => {
                            item.classList.add('removing');
                        }, index * 100);
                    });
                    
                    setTimeout(() => {
                        cart = [];
                        saveCart();
                        updateCartInRealTime();
                        showToast('Carrito vaciado correctamente', 'danger');
                    }, cartItems.length * 100 + 300);
                }
            }
            
            // Completar pedido
            if (e.target.closest('#checkout-btn')) {
                if (cart.length === 0) return;
                mostrarModalCliente();
            }
            
            // Confirmar pedido desde el modal
            if (e.target.closest('#modal-confirm-btn')) {
                const nombre = document.getElementById('cliente_nombre').value.trim();
                const telefono = document.getElementById('cliente_telefono').value.trim();
                
                if (!nombre) {
                    showToast('Por favor ingresa tu nombre', 'danger');
                    return;
                }
                
                if (!telefono) {
                    showToast('Por favor ingresa tu número de WhatsApp', 'danger');
                    return;
                }
                
                if (!validarTelefono(telefono)) {
                    showToast('Por favor ingresa un número de WhatsApp válido', 'danger');
                    return;
                }
                
                const modal = bootstrap.Modal.getInstance(document.getElementById('clienteModal'));
                modal.hide();
                
                guardarPedidoEnBD(nombre, telefono);
            }
        });
        
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('quantity-input')) {
                const index = parseInt(e.target.dataset.index);
                const quantity = parseInt(e.target.value);
                
                if (!isNaN(quantity) && quantity > 0) {
                    updateQuantity(index, quantity);
                }
            }
        });
        
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('quantity-input')) {
                const index = parseInt(e.target.dataset.index);
                const quantity = parseInt(e.target.value);
                
                if (isNaN(quantity) || quantity < 1) {
                    e.target.value = 1;
                    updateQuantity(index, 1);
                }
            }
        });
        
        // Inicializar
        updateCartInRealTime();
    </script>
</body>
</html>