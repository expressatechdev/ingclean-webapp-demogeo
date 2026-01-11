<?php
/**
 * INGClean - Página de Pago (Stripe)
 */
require_once '../includes/init.php';

auth()->requireLogin(['client']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

$orderId = get('order');

if (!$orderId) {
    redirect('/client/');
}

// Obtener orden pendiente de pago
$order = $db->fetchOne(
    "SELECT o.*, s.name as service_name, s.price, s.description as service_description,
            p.name as partner_name, p.phone as partner_phone, p.photo as partner_photo
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     LEFT JOIN partners p ON o.partner_id = p.id
     WHERE o.id = :order_id AND o.client_id = :client_id AND o.status = 'accepted'",
    ['order_id' => $orderId, 'client_id' => $user['id']]
);

if (!$order) {
    setFlash('error', 'Orden no encontrada o ya fue pagada');
    redirect('/client/');
}

// Calcular montos
$totalAmount = $order['price'];
$platformFee = calculatePlatformFee($totalAmount);
$partnerAmount = calculatePartnerAmount($totalAmount);

$stripePublicKey = getStripePublicKey();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagar Servicio - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f9ff;
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            background: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .back-btn {
            background: #f1f5f9;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #64748b;
            text-decoration: none;
        }
        
        .header h1 {
            font-size: 1.1rem;
            color: #1e3a5f;
        }
        
        /* Main */
        .main-content {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section-title {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Order Summary */
        .order-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 15px;
        }
        
        .service-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .order-details h3 {
            font-size: 1rem;
            color: #1e3a5f;
            margin-bottom: 3px;
        }
        
        .order-details p {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        /* Partner Info */
        .partner-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .partner-photo {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            overflow: hidden;
        }
        
        .partner-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .partner-info h4 {
            font-size: 0.95rem;
            color: #1e3a5f;
        }
        
        .partner-info p {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        /* Price Breakdown */
        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 0.95rem;
        }
        
        .price-row.total {
            border-top: 2px solid #e2e8f0;
            margin-top: 10px;
            padding-top: 15px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .price-row .label {
            color: #64748b;
        }
        
        .price-row .value {
            color: #1e3a5f;
        }
        
        .price-row.total .value {
            color: #0077b6;
            font-size: 1.3rem;
        }
        
        /* Stripe Card Element */
        .card-element-container {
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: #fafafa;
            transition: all 0.3s;
        }
        
        .card-element-container.focused {
            border-color: #00b4d8;
            background: white;
        }
        
        .card-element-container.error {
            border-color: #ef4444;
        }
        
        #card-element {
            padding: 5px 0;
        }
        
        #card-errors {
            color: #ef4444;
            font-size: 0.85rem;
            margin-top: 10px;
            display: none;
        }
        
        #card-errors.visible {
            display: block;
        }
        
        /* Security Note */
        .security-note {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
            padding: 12px;
            background: #f0fdf4;
            border-radius: 10px;
        }
        
        .security-note span {
            font-size: 0.8rem;
            color: #16a34a;
        }
        
        /* Submit Button */
        .btn-pay {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 119, 182, 0.3);
        }
        
        .btn-pay:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-pay .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: none;
        }
        
        .btn-pay.loading .spinner {
            display: block;
        }
        
        .btn-pay.loading .btn-text {
            display: none;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Stripe Badge */
        .stripe-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
            color: #64748b;
            font-size: 0.8rem;
        }
        
        .stripe-badge img {
            height: 20px;
        }
        
        /* Test Mode Banner */
        .test-mode {
            background: #fef3c7;
            color: #92400e;
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .test-card-info {
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .test-card-info code {
            background: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
        
        /* Success Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }
        
        .modal-overlay.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 24px;
            padding: 40px 30px;
            text-align: center;
            max-width: 400px;
            width: 100%;
            animation: modalIn 0.3s ease;
        }
        
        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .modal-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 20px;
        }
        
        .modal-content h2 {
            font-size: 1.5rem;
            color: #1e3a5f;
            margin-bottom: 10px;
        }
        
        .modal-content p {
            color: #64748b;
            margin-bottom: 25px;
        }
        
        .btn-continue {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <a href="index.php" class="back-btn">←</a>
        <h1>Pagar Servicio</h1>
    </header>
    
    <!-- Main -->
    <main class="main-content">
        <?php if (STRIPE_MODE === 'test'): ?>
            <div class="test-mode">
                ⚠️ <strong>Modo de Prueba</strong> - No se realizarán cargos reales
            </div>
        <?php endif; ?>
        
        <!-- Order Summary -->
        <div class="section">
            <div class="section-title">Resumen del Pedido</div>
            
            <div class="order-header">
                <div class="service-icon">🧹</div>
                <div class="order-details">
                    <h3><?= e($order['service_name']) ?></h3>
                    <p><?= e($order['order_code']) ?></p>
                </div>
            </div>
            
            <?php if ($order['partner_name']): ?>
                <div class="partner-row">
                    <div class="partner-photo">
                        <?php if ($order['partner_photo']): ?>
                            <img src="../<?= e($order['partner_photo']) ?>" alt="">
                        <?php else: ?>
                            👤
                        <?php endif; ?>
                    </div>
                    <div class="partner-info">
                        <h4><?= e($order['partner_name']) ?></h4>
                        <p>Tu profesional asignado</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Price Breakdown -->
        <div class="section">
            <div class="section-title">Detalle de Pago</div>
            
            <div class="price-row">
                <span class="label"><?= e($order['service_name']) ?></span>
                <span class="value">$<?= number_format($order['price'], 2) ?></span>
            </div>
            <div class="price-row">
                <span class="label">Comisión de servicio</span>
                <span class="value">Incluida</span>
            </div>
            <div class="price-row total">
                <span class="label">Total</span>
                <span class="value">$<?= number_format($totalAmount, 2) ?></span>
            </div>
        </div>
        
        <!-- Payment Form -->
        <div class="section">
            <div class="section-title">Método de Pago</div>
            
            <form id="payment-form">
                <div class="card-element-container" id="card-container">
                    <div id="card-element"></div>
                </div>
                <div id="card-errors" role="alert"></div>
                
                <?php if (STRIPE_MODE === 'test'): ?>
                    <div class="test-card-info">
                        💳 Tarjeta de prueba: <code>4242 4242 4242 4242</code><br>
                        Fecha: <code>12/34</code> | CVC: <code>123</code>
                    </div>
                <?php endif; ?>
                
                <div class="security-note">
                    🔒 <span>Tu información de pago está protegida con encriptación SSL</span>
                </div>
                
                <button type="submit" class="btn-pay" id="submit-btn">
                    <div class="spinner"></div>
                    <span class="btn-text">💳 Pagar $<?= number_format($totalAmount, 2) ?></span>
                </button>
            </form>
            
            <div class="stripe-badge">
                Procesado de forma segura por
                <svg width="50" height="20" viewBox="0 0 60 25">
                    <path fill="#635BFF" d="M59.64 14.28c0-4.7-2.28-8.4-6.64-8.4-4.38 0-7.02 3.7-7.02 8.36 0 5.52 3.12 8.3 7.6 8.3 2.18 0 3.84-.5 5.08-1.18v-3.66c-1.24.62-2.66 1.02-4.46 1.02-1.76 0-3.32-.62-3.52-2.78h8.88c0-.24.08-1.18.08-1.66zm-8.98-1.74c0-2.06 1.26-2.92 2.42-2.92 1.12 0 2.32.86 2.32 2.92h-4.74zM41.24 5.88c-1.76 0-2.9.82-3.52 1.4l-.24-1.12h-4.02V22h4.56v-9.88c1.08-1.4 2.9-1.14 3.46-.94v-4.2c-.58-.22-2.7-.62-4.24 1.1V5.88zM32.86 1.94l-4.44.94v12.48c0 2.3 1.74 4 4.04 4 1.28 0 2.22-.24 2.74-.52v-3.5c-.5.2-2.98.9-2.98-1.36V9.9h2.98V6.16h-2.98V1.94zM23.04 9.12c0-.66.54-1.16 1.44-1.16.66 0 1.18.16 1.7.44V4.88c-.58-.26-1.56-.58-2.78-.58-2.86 0-5.04 1.68-5.04 4.46 0 3.06 3.2 4.1 4.66 4.46 1.36.34 1.64.66 1.64 1.16 0 .68-.6 1.22-1.6 1.22-1.08 0-1.74-.32-2.38-.74v3.66c.76.36 1.82.66 3.08.66 2.98 0 5.18-1.64 5.18-4.46 0-3.28-3.24-4.2-4.72-4.6-1.18-.32-1.58-.56-1.58-1zM12.5 9.12c0-.66.54-1.16 1.44-1.16.66 0 1.18.16 1.7.44V4.88c-.58-.26-1.56-.58-2.78-.58-2.86 0-5.04 1.68-5.04 4.46 0 3.06 3.2 4.1 4.66 4.46 1.36.34 1.64.66 1.64 1.16 0 .68-.6 1.22-1.6 1.22-1.08 0-1.74-.32-2.38-.74v3.66c.76.36 1.82.66 3.08.66 2.98 0 5.18-1.64 5.18-4.46 0-3.28-3.24-4.2-4.72-4.6-1.18-.32-1.58-.56-1.58-1z"/>
                </svg>
            </div>
        </div>
    </main>
    
    <!-- Success Modal -->
    <div class="modal-overlay" id="success-modal">
        <div class="modal-content">
            <div class="modal-icon">✓</div>
            <h2>¡Pago Exitoso!</h2>
            <p>Tu partner ha sido notificado y está en camino a tu ubicación.</p>
            <a href="tracking.php?order=<?= $order['id'] ?>" class="btn-continue">
                📍 Seguir en el Mapa
            </a>
        </div>
    </div>
    
    <script>
        // Inicializar Stripe
        const stripe = Stripe('<?= $stripePublicKey ?>');
        const elements = stripe.elements();
        
        // Estilos del card element
        const cardStyle = {
            base: {
                color: '#1e3a5f',
                fontFamily: 'Poppins, sans-serif',
                fontSize: '16px',
                '::placeholder': {
                    color: '#94a3b8'
                }
            },
            invalid: {
                color: '#ef4444',
                iconColor: '#ef4444'
            }
        };
        
        // Crear card element
        const cardElement = elements.create('card', { style: cardStyle });
        cardElement.mount('#card-element');
        
        // Manejar errores del card
        const cardErrors = document.getElementById('card-errors');
        const cardContainer = document.getElementById('card-container');
        
        cardElement.on('change', function(event) {
            if (event.error) {
                cardErrors.textContent = event.error.message;
                cardErrors.classList.add('visible');
                cardContainer.classList.add('error');
            } else {
                cardErrors.classList.remove('visible');
                cardContainer.classList.remove('error');
            }
        });
        
        cardElement.on('focus', function() {
            cardContainer.classList.add('focused');
        });
        
        cardElement.on('blur', function() {
            cardContainer.classList.remove('focused');
        });
        
        // Manejar submit del formulario
        const form = document.getElementById('payment-form');
        const submitBtn = document.getElementById('submit-btn');
        
        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            
            try {
                // 1. Crear Payment Intent en el servidor
                const response = await fetch('../api/payments/create-intent.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: <?= $order['id'] ?>
                    })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Error al procesar el pago');
                }
                
                // 2. Confirmar el pago con Stripe
                const { error, paymentIntent } = await stripe.confirmCardPayment(
                    data.data.client_secret,
                    {
                        payment_method: {
                            card: cardElement,
                            billing_details: {
                                name: '<?= e($user['name']) ?>',
                                email: '<?= e($user['email']) ?>'
                            }
                        }
                    }
                );
                
                if (error) {
                    throw new Error(error.message);
                }
                
                if (paymentIntent.status === 'succeeded') {
                    // 3. Confirmar en el servidor
                    const confirmResponse = await fetch('../api/payments/confirm.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            order_id: <?= $order['id'] ?>,
                            payment_intent_id: paymentIntent.id
                        })
                    });
                    
                    const confirmData = await confirmResponse.json();
                    
                    if (confirmData.success) {
                        // Mostrar modal de éxito
                        document.getElementById('success-modal').classList.add('show');
                    } else {
                        throw new Error(confirmData.message || 'Error al confirmar el pago');
                    }
                }
                
            } catch (error) {
                cardErrors.textContent = error.message;
                cardErrors.classList.add('visible');
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
            }
        });
    </script>
</body>
</html>
