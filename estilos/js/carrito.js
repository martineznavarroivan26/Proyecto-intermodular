function showNotification(message, type = 'success') {
    var notification = document.createElement('div');
    notification.className = 'alert alert-' + type;
    notification.setAttribute('role', 'alert');
    notification.style.position = 'fixed';
    notification.style.top = '1rem';
    notification.style.left = '50%';
    notification.style.transform = 'translateX(-50%)';
    notification.style.zIndex = '9999';
    notification.style.minWidth = 'min(90vw, 28rem)';
    notification.style.maxWidth = '90vw';
    notification.style.boxShadow = '0 12px 28px rgba(0, 0, 0, 0.3)';
    notification.style.animation = 'fadeIn 0.3s ease';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(function () {
        notification.style.transition = 'opacity 0.3s ease';
        notification.style.opacity = '0';
        setTimeout(function () {
            notification.remove();
        }, 300);
    }, 5000);
}

document.addEventListener('DOMContentLoaded', function () {
    function onlyDigits(value) {
        return (value || '').replace(/\D/g, '');
    }

    function isValidCardNumber(number) {
        var digits = onlyDigits(number);
        if (digits.length !== 16) {
            return false;
        }

        var sum = 0;
        var shouldDouble = false;
        // Validacion Luhn para detectar errores frecuentes en numeracion de tarjeta.
        for (var i = digits.length - 1; i >= 0; i--) {
            var digit = parseInt(digits.charAt(i), 10);
            if (shouldDouble) {
                digit *= 2;
                if (digit > 9) {
                    digit -= 9;
                }
            }
            sum += digit;
            shouldDouble = !shouldDouble;
        }

        return sum % 10 === 0;
    }

    function isValidExpiry(expiryValue) {
        var match = /^(0[1-9]|1[0-2])\/(\d{2})$/.exec((expiryValue || '').trim());
        if (!match) {
            return false;
        }

        var month = parseInt(match[1], 10);
        var year = 2000 + parseInt(match[2], 10);
        var now = new Date();
        var currentMonth = now.getMonth() + 1;
        var currentYear = now.getFullYear();

        if (year < currentYear) {
            return false;
        }

        if (year === currentYear && month < currentMonth) {
            return false;
        }

        return true;
    }

    function setFieldValidity(input, isValid) {
        if (!input) {
            return;
        }

        input.classList.toggle('is-invalid', !isValid);
        input.classList.toggle('is-valid', isValid);
    }

    var addToCartButtons = document.querySelectorAll('.add-to-cart-btn');

    addToCartButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();

            var eventoId = button.getAttribute('data-evento-id');
            var eventoTitulo = button.getAttribute('data-evento-titulo');
            var eventoPrecio = button.getAttribute('data-evento-precio');
            var eventoFecha = button.getAttribute('data-evento-fecha');

            if (!eventoId || !eventoTitulo || !eventoPrecio) {
                showNotification('Datos del evento incompletos', 'danger');
                return;
            }

            var formData = new FormData();
            formData.append('evento_id', eventoId);
            formData.append('titulo', eventoTitulo);
            formData.append('precio', eventoPrecio);
            formData.append('fecha_inicio', eventoFecha);

            fetch('?page=carrito-add', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (data) {
                        throw new Error(data.error || 'Error desconocido');
                    });
                }
                return response.json();
            })
            .then(function (data) {
                if (data.success) {
                    // Recarga corta para sincronizar mini-resumen y contador del carrito.
                    showNotification('Evento agregado al carrito', 'success');
                    setTimeout(function () {
                        location.reload();
                    }, 800);
                } else {
                    showNotification(data.error || 'Error al agregar al carrito', 'danger');
                }
            })
            .catch(function (error) {
                showNotification('Error: ' + error.message, 'danger');
            });
        });
    });

    var removeForms = document.querySelectorAll('.remove-form');
    removeForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var eventoId = form.querySelector('input[name="evento_id"]').value;

            if (!eventoId) {
                showNotification('ID de evento inválido', 'danger');
                return;
            }

            var formData = new FormData();
            formData.append('evento_id', eventoId);

            fetch('?page=carrito-remove', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (data) {
                        throw new Error(data.error || 'Error desconocido');
                    });
                }
                return response.json();
            })
            .then(function (data) {
                if (data.success) {
                    showNotification('Evento eliminado del carrito', 'success');
                    setTimeout(function () {
                        location.reload();
                    }, 800);
                } else {
                    showNotification(data.error || 'Error al eliminar del carrito', 'danger');
                }
            })
            .catch(function (error) {
                showNotification('Error: ' + error.message, 'danger');
            });
        });
    });

    var joinFreeEventButtons = document.querySelectorAll('.join-free-event-btn');
    joinFreeEventButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();

            var eventId = button.getAttribute('data-evento-id');
            if (!eventId) {
                showNotification('ID de evento invalido.', 'danger');
                return;
            }

            var formData = new FormData();
            formData.append('evento_id', eventId);

            fetch('?page=evento-join-free', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (data) {
                        throw new Error(data.error || 'Error desconocido');
                    });
                }
                return response.json();
            })
            .then(function (data) {
                if (data.success) {
                    showNotification(data.message || 'Inscripcion completada correctamente.', 'success');
                    setTimeout(function () {
                        location.reload();
                    }, 800);
                    return;
                }

                showNotification(data.error || 'No se pudo completar la inscripcion.', 'danger');
            })
            .catch(function (error) {
                showNotification('Error: ' + error.message, 'danger');
            });
        });
    });

    var leaveEventButtons = document.querySelectorAll('.leave-event-btn');
    leaveEventButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();

            var eventId = button.getAttribute('data-evento-id');
            if (!eventId) {
                showNotification('ID de evento invalido.', 'danger');
                return;
            }

            var formData = new FormData();
            formData.append('evento_id', eventId);

            fetch('?page=evento-leave', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (data) {
                        throw new Error(data.error || 'Error desconocido');
                    });
                }
                return response.json();
            })
            .then(function (data) {
                if (data.success) {
                    showNotification(data.message || 'Te has desinscrito correctamente del evento.', 'success');
                    setTimeout(function () {
                        location.reload();
                    }, 800);
                    return;
                }

                showNotification(data.error || 'No se pudo completar la desinscripcion.', 'danger');
            })
            .catch(function (error) {
                showNotification('Error: ' + error.message, 'danger');
            });
        });
    });

    var checkoutForm = document.getElementById('checkoutGatewayForm');
    if (checkoutForm) {
        var cardNameInput = document.getElementById('checkoutCardName');
        var cardNumberInput = document.getElementById('checkoutCardNumber');
        var cardExpiryInput = document.getElementById('checkoutCardExpiry');
        var cardCvcInput = document.getElementById('checkoutCardCvc');
        var gatewayNumberPreview = document.querySelector('.gateway-card-number');
        var payButton = checkoutForm.querySelector('.gateway-pay-btn') || document.querySelector('.gateway-pay-btn');
        var originalPayButtonText = payButton ? payButton.textContent : 'Pagar';

        cardNumberInput.addEventListener('input', function () {
            var digits = onlyDigits(cardNumberInput.value).slice(0, 16);
            cardNumberInput.value = digits.replace(/(.{4})/g, '$1 ').trim();

            if (gatewayNumberPreview) {
                if (digits.length === 16) {
                    gatewayNumberPreview.textContent = '**** **** **** ' + digits.slice(-4);
                } else {
                    gatewayNumberPreview.textContent = '**** **** **** ****';
                }
            }
        });

        cardExpiryInput.addEventListener('input', function () {
            var digits = onlyDigits(cardExpiryInput.value).slice(0, 4);
            if (digits.length >= 3) {
                cardExpiryInput.value = digits.slice(0, 2) + '/' + digits.slice(2);
            } else {
                cardExpiryInput.value = digits;
            }
        });

        cardCvcInput.addEventListener('input', function () {
            cardCvcInput.value = onlyDigits(cardCvcInput.value).slice(0, 4);
        });

        checkoutForm.addEventListener('submit', function (event) {
            event.preventDefault();

            var cardName = (cardNameInput.value || '').trim();
            var cardNumber = cardNumberInput.value;
            var cardExpiry = cardExpiryInput.value;
            var cardCvc = cardCvcInput.value;

            var isNameValid = cardName.length >= 3;
            var isNumberValid = isValidCardNumber(cardNumber);
            var isExpiryValid = isValidExpiry(cardExpiry);
            var isCvcValid = /^\d{3,4}$/.test(onlyDigits(cardCvc));

            setFieldValidity(cardNameInput, isNameValid);
            setFieldValidity(cardNumberInput, isNumberValid);
            setFieldValidity(cardExpiryInput, isExpiryValid);
            setFieldValidity(cardCvcInput, isCvcValid);

            if (!isNameValid || !isNumberValid || !isExpiryValid || !isCvcValid) {
                showNotification('Revisa los datos de pago antes de continuar.', 'danger');
                return;
            }

            if (payButton) {
                payButton.disabled = true;
                payButton.textContent = 'Procesando...';
            }

            fetch('?page=carrito-checkout', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (data) {
                        throw new Error(data.error || 'No se pudo completar el pago.');
                    });
                }
                return response.json();
            })
            .then(function (data) {
                showNotification(data.message || 'Pago realizado correctamente.', 'success');

                var checkoutModalElement = document.getElementById('checkoutGatewayModal');
                if (checkoutModalElement && typeof bootstrap !== 'undefined') {
                    var checkoutModal = bootstrap.Modal.getOrCreateInstance(checkoutModalElement);
                    checkoutModal.hide();
                }

                checkoutForm.reset();
                setFieldValidity(cardNameInput, true);
                setFieldValidity(cardNumberInput, true);
                setFieldValidity(cardExpiryInput, true);
                setFieldValidity(cardCvcInput, true);
                if (gatewayNumberPreview) {
                    gatewayNumberPreview.textContent = '**** **** **** 4242';
                }

                setTimeout(function () {
                    location.reload();
                }, 900);
            })
            .catch(function (error) {
                showNotification('Error: ' + error.message, 'danger');
            })
            .finally(function () {
                if (payButton) {
                    payButton.disabled = false;
                    payButton.textContent = originalPayButtonText;
                }
            });
        });
    }
});
