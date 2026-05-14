<?php
declare(strict_types=1);

$cartItems = is_array($cartItems ?? null) ? $cartItems : [];
$totalPrice = 0;

foreach ($cartItems as $item) {
    $totalPrice += (float) ($item['precio'] ?? 0);
}
?>
<div class="caja">
    <h1 class="titulo">Carrito de eventos</h1>
    <div class="linea"></div>

    <?php if ($cartItems === []) : ?>
        <div class="carrito-empty">
            <p>Tu carrito está vacío</p>
            <p><a href="<?= route('eventos') ?>">Ir a eventos</a></p>
        </div>
    <?php else : ?>
        <div class="carrito-container">
            <div class="carrito-items">
                <?php foreach ($cartItems as $item) : ?>
                    <div class="carrito-item">
                        <div class="item-header">
                            <h3><?= htmlspecialchars((string) ($item['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                            <form method="post" action="<?= route('carrito-remove') ?>" class="remove-form">
                                <input type="hidden" name="evento_id" value="<?= htmlspecialchars((string) ($item['evento_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="remove-btn" aria-label="Eliminar">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                        <div class="item-details">
                            <p>Precio: <strong><?= htmlspecialchars(number_format((float) ($item['precio'] ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?> EUR</strong></p>
                            <?php if (!empty($item['fecha_inicio'])) : ?>
                                <p>Fecha: <strong><?= htmlspecialchars((string) ($item['fecha_inicio'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="carrito-summary">
                <h2>Resumen</h2>
                <div class="summary-row">
                    <span>Eventos:</span>
                    <strong><?= htmlspecialchars((string) count($cartItems), ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <strong><?= htmlspecialchars(number_format($totalPrice, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?> EUR</strong>
                </div>
                <button type="button" class="checkout-btn" data-bs-toggle="modal" data-bs-target="#checkoutGatewayModal">Proceder al pago</button>
                <a href="<?= route('eventos') ?>" class="continue-shopping">Continuar comprando</a>
            </div>
        </div>

        <div class="modal fade checkout-gateway-modal" id="checkoutGatewayModal" tabindex="-1" aria-labelledby="checkoutGatewayModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content checkout-gateway-content">
                    <div class="modal-header checkout-gateway-header">
                        <h5 class="modal-title" id="checkoutGatewayModalLabel">Pasarela de pago</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body checkout-gateway-body">
                        <p class="gateway-intro">Completa los datos para finalizar el pago del carrito.</p>
                        <div class="gateway-card-preview" aria-hidden="true">
                            <span class="gateway-chip"></span>
                            <strong>INAMANIA PAY</strong>
                            <span class="gateway-card-number">**** **** **** 4242</span>
                        </div>
                        <form id="checkoutGatewayForm" novalidate>
                            <div class="mb-3">
                                <label class="form-label" for="checkoutCardName">Titular</label>
                                <input type="text" class="form-control" id="checkoutCardName" autocomplete="cc-name" placeholder="Nombre y apellidos" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="checkoutCardNumber">Numero de tarjeta</label>
                                <input type="text" class="form-control" id="checkoutCardNumber" inputmode="numeric" autocomplete="cc-number" placeholder="**** **** **** 4242" maxlength="19" required>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label" for="checkoutCardExpiry">Caducidad</label>
                                    <input type="text" class="form-control" id="checkoutCardExpiry" inputmode="numeric" autocomplete="cc-exp" placeholder="MM/AA" maxlength="5" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="checkoutCardCvc">CVC</label>
                                    <input type="text" class="form-control" id="checkoutCardCvc" inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="4" required>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer checkout-gateway-footer">
                        <button type="button" class="btn btn-outline-secondary gateway-cancel-btn" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" form="checkoutGatewayForm" class="btn btn-primary gateway-pay-btn">Pagar <?= htmlspecialchars(number_format($totalPrice, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?> EUR</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="linea"></div>
</div>
