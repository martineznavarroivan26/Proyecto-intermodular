<?php
declare(strict_types=1);

use App\Controllers\PageController;
use App\Controllers\LoginController;
use App\Controllers\PerfilController;
use App\Controllers\ForoController;
use App\Controllers\CarritoController;
use App\Controllers\JuegoController;
use App\Controllers\ModeratorController;
use App\Controllers\AdminController;

return [
    'home' => [PageController::class, 'home'],
    'juegos' => [JuegoController::class, 'juegos'],
    'foro' => [ForoController::class, 'foro'],
    'moderacion' => [ModeratorController::class, 'moderacion'],
    'admin' => [AdminController::class, 'admin'],
    'eventos' => [PageController::class, 'eventos'],
    'carrito' => [CarritoController::class, 'viewCart'],
    'carrito-add' => [CarritoController::class, 'addToCart'],
    'carrito-remove' => [CarritoController::class, 'removeFromCart'],
    'carrito-checkout' => [CarritoController::class, 'checkout'],
    'evento-join-free' => [CarritoController::class, 'joinFreeEvent'],
    'evento-leave' => [CarritoController::class, 'leaveEvent'],
    'login' => [LoginController::class, 'login'],
    'perfil' => [PerfilController::class, 'perfil'],
    'terminos' => [PageController::class, 'terminos'],
    'privacidad' => [PageController::class, 'privacidad'],
    'aviso-legal' => [PageController::class, 'avisoLegal'],
    '404' => [PageController::class, 'notFound']
];