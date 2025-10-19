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
        .cart-item {
            transition: all 0.3s ease;
        }
        .cart-item.removing {
            opacity: 0;
            height: 0;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        .toast-success {
            background: #28a745;
            color: white;
            border: none;
        }
        .toast-danger {
            background: #dc3545;
            color: white;
            border: none;
        }
        .empty-cart-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .quantity-input {
            width: 60px;
            text-align: center;
        }
        .product-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .btn-quantity {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cart-items-container {
            min-height: 200px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-gem me-2"></i>BLOOM
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

    <!-- Container para notificaciones Toast -->
    <div class="toast-container" id="toastContainer"></div>

    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4"><i class="fas fa-shopping-cart me-2"></i>Tu Carrito de Compras</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Productos en el Carrito</h5>
                        <button class="btn btn-outline-danger btn-sm" id="clear-cart-btn" style="display: none;">
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
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <h4 class="text-muted">Tu carrito está vacío</h4>
                                <p class="text-muted mb-4">Agrega algunos productos para continuar</p>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-bag me-2"></i>Ir a Comprar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card sticky-top" style="top: 100px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Resumen del Pedido</h5>
                    </div>
                    <div class="card-body">
                        <div id="order-summary">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span id="subtotal">Gs. 0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Envío:</span>
                                <span class="text-success">Gratis</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong id="total" class="text-success">Gs. 0</strong>
                            </div>
                            <button class="btn btn-success w-100 mb-3" id="checkout-btn" disabled>
                                <i class="fab fa-whatsapp me-2"></i>Completar Pedido por WhatsApp
                            </button>
                            <a href="catalogo.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-arrow-left me-2"></i>Seguir Comprando
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Información de contacto -->
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <h6><i class="fas fa-headset me-2"></i>¿Necesitas ayuda?</h6>
                        <p class="small mb-2">
                            <i class="fas fa-phone me-1"></i>+595 972 366-265<br>
                            <i class="fas fa-clock me-1"></i>Lun-Vie: 8:00-18:00
                        </p>
                        <a href="https://wa.me/595972366265" target="_blank" class="btn btn-success btn-sm w-100">
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
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>Un último paso
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="modal-confirm-btn">
                        <i class="fab fa-whatsapp me-2"></i>Enviar Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        let cart = JSON.parse(localStorage.getItem('marccos_cart')) || [];
        
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
                    <div class="cart-item row align-items-center mb-3 pb-3 border-bottom" id="cart-item-${index}">
                        <div class="col-2">
                            ${item.image ? 
                                `<img src="../uploads/products/${item.image}" class="product-thumb" alt="${item.name}">` :
                                `<div class="product-thumb bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image text-muted"></i>
                                </div>`
                            }
                        </div>
                        <div class="col-4">
                            <h6 class="mb-1">${item.name}</h6>
                            <p class="text-muted mb-0">Gs. ${item.price.toLocaleString()}</p>
                        </div>
                        <div class="col-4">
                            <div class="input-group input-group-sm" style="width: 140px;">
                                <button class="btn btn-outline-secondary btn-quantity minus-btn" type="button" data-index="${index}">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="form-control quantity-input" 
                                       value="${item.quantity}" min="1" data-index="${index}">
                                <button class="btn btn-outline-secondary btn-quantity plus-btn" type="button" data-index="${index}">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-2 text-end">
                            <strong class="d-block">Gs. ${itemTotal.toLocaleString()}</strong>
                            <button class="btn btn-sm btn-outline-danger mt-1 remove-item" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            cartItems.innerHTML = html;
            document.getElementById('subtotal').textContent = 'Gs. ' + subtotal.toLocaleString();
            document.getElementById('total').textContent = 'Gs. ' + subtotal.toLocaleString();
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
            localStorage.setItem('marccos_cart', JSON.stringify(cart));
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
                    const whatsappUrl = `https://wa.me/595972366265?text=${message}`;
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