<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Model\EventoModel;
use App\Model\ForoModel;
use App\Model\ModeratorModel;

class PageController extends Controller
{
    private function baseStyles(): array
    {
        return ['estilos/css/css_pag/style.css'];
    }

    private function homeStyles(): array
    {
        return ['estilos/css/css_pag/style.css', 'estilos/css/css_pag/responsive.css'];
    }

    private function renderInfoPage(string $pageTitle, string $headline, string $message, string $currentPage = 'home'): void
    {
        $this->render('pages/info', [
            'pageTitle' => $pageTitle,
            'styles' => ['estilos/css/css_pag/style.css', 'estilos/css/css_pag/foro.css', 'estilos/css/css_pag/responsive.css'],
            'currentPage' => $currentPage,
            'headline' => $headline,
            'message' => $message
        ]);
    }

    public function home(): void
    {
        $homeForumPosts = [];
        $homeUpcomingEvents = [];
        $homeCarousel = [];
        $homeNews = [];

        try {
            $homeForumPosts = (new ForoModel())->listPopularPosts(5);
        } catch (\Throwable $exception) {
            $homeForumPosts = [];
        }

        try {
            $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
            $homeUpcomingEvents = (new EventoModel())->listUpcomingOpenRegistrationEvents($today, 4);
        } catch (\Throwable $exception) {
            $homeUpcomingEvents = [];
        }

        try {
            $moderatorModel = new ModeratorModel();
            $homeCarousel = $moderatorModel->listActiveCarouselItems();
            $homeNews = $moderatorModel->listActiveNewsItems();
        } catch (\Throwable $exception) {
            $homeCarousel = [];
            $homeNews = [];
        }

        $this->render('pages/home', [
            'pageTitle' => 'Inamania',
            'styles' => $this->homeStyles(),
            'currentPage' => 'home',
            'flashSuccess' => $this->consumeFlashSuccess(),
            'homeForumPosts' => $homeForumPosts,
            'homeUpcomingEvents' => $homeUpcomingEvents,
            'homeCarousel' => $homeCarousel,
            'homeNews' => $homeNews,
        ]);
    }

    public function eventos(): void
    {
        $eventoModel = new EventoModel();
        $currentUserId = $this->currentSessionUserId();
        $enrolledEvents = [];
        $enrolledEventIds = [];
        $eventLoadError = '';

        $rangeStart = (new \DateTimeImmutable('first day of this month'))->format('Y-m-d');
        $rangeEnd = (new \DateTimeImmutable('first day of this month'))
            ->modify('+11 months')
            ->modify('last day of this month')
            ->format('Y-m-d');
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');

        try {
            $calendarEvents = $eventoModel->listEventsBetween($rangeStart, $rangeEnd);

            if ($currentUserId !== null) {
                $enrolledEvents = $eventoModel->listEnrolledEventsByUser($currentUserId, 20);
                $enrolledEventIds = array_map(
                    static fn (array $event): int => (int) ($event['evento_id'] ?? 0),
                    $enrolledEvents
                );
            }

            // Recommended events should not include events where the user is already enrolled.
            $recommendedEvents = array_values(array_filter(
                $eventoModel->listRecommendedEventsByRegistrations(10, $today),
                static fn (array $event): bool => !in_array((int) ($event['evento_id'] ?? 0), $enrolledEventIds, true)
            ));

            $recommendedEvents = array_slice($recommendedEvents, 0, 3);
        } catch (\Throwable $exception) {
            $calendarEvents = [];
            $recommendedEvents = [];
            $enrolledEvents = [];
            $enrolledEventIds = [];
            $eventLoadError = 'No se pudieron cargar los eventos desde la base de datos.';
        }

        $this->render('pages/eventos', [
            'pageTitle' => 'Inamania - Eventos',
            'styles' => ['estilos/css/css_pag/style.css', 'estilos/css/css_pag/eventos.css', 'estilos/css/css_pag/responsive.css'],
            'scripts' => ['estilos/js/eventos-calendar.js', 'estilos/js/carrito.js'],
            'currentPage' => 'eventos'
            ,
            'flashSuccess' => $this->consumeFlashSuccess(),
            'flashError' => $eventLoadError !== '' ? $eventLoadError : $this->consumeFlashError(),
            'calendarEvents' => $calendarEvents,
            'recommendedEvents' => $recommendedEvents,
            'enrolledEvents' => $enrolledEvents,
            'enrolledEventIds' => $enrolledEventIds,
            'isAuthenticated' => $this->currentSessionUserId() !== null,
        ]);
    }

    public function terminos(): void
    {
        $this->renderInfoPage('Inamania - Términos y Condiciones', 'Términos y Condiciones', 'Aquí van las condiciones de uso de la web y la comunidad.');
    }

    public function privacidad(): void
    {
        $this->renderInfoPage('Inamania - Política de Privacidad', 'Política de Privacidad', 'Aquí va una explicación sobre qué datos se guardan, con qué finalidad y cómo puede ejercer sus derechos el usuario.');
    }

    public function avisoLegal(): void
    {
        $this->renderInfoPage('Inamania - Aviso Legal', 'Aviso Legal', 'Aquí va la información legal del proyecto, titularidad y datos de contacto.');
    }

    public function notFound(): void
    {
        http_response_code(404);
        $this->render('pages/not-found', [
            'pageTitle' => 'Inamania - 404',
            'styles' => $this->baseStyles(),
            'currentPage' => 'home'
        ]);
    }
}