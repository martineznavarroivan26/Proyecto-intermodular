<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Model\ForoModel;
use App\Model\UserModel;

class ForoController extends Controller
{
    private const MAX_TITLE_LENGTH = 100;
    private const MAX_COMMENT_LENGTH = 1000;
    private const ALLOWED_CATEGORIES = ['General', 'Juegos', 'Debate', 'Ayuda', 'Eventos'];
    private const FAVORITES_VIEW = 'favorites';

    private function foroStyles(): array
    {
        return [
            'estilos/css/css_pag/style.css',
            'estilos/css/css_pag/foro.css',
            'estilos/css/css_pag/responsive.css',
        ];
    }

    private function validatedSessionUserId(): ?int
    {
        $userId = $this->currentSessionUserId();

        if ($userId === null) {
            return null;
        }

        try {
            $userModel = new UserModel();
            if ($userModel->findById($userId) === null) {
                unset($_SESSION['auth_user']);
                return null;
            }
        } catch (\Throwable $exception) {
            return null;
        }

        return $userId;
    }

    private function currentForumView(): string
    {
        $view = trim((string) ($_GET['view'] ?? ''));

        return $view === self::FAVORITES_VIEW ? self::FAVORITES_VIEW : '';
    }

    private function foroRouteWithView(string $forumView): string
    {
        if ($forumView === self::FAVORITES_VIEW) {
            return route('foro') . '&view=' . self::FAVORITES_VIEW;
        }

        return route('foro');
    }

    private function createPostIfNeeded(ForoModel $foroModel, string $forumView): array
    {
        // Orquesta TODAS las acciones POST del foro (post, comentario, favorito)
        // y devuelve estado para que el render principal sepa si redirigir o mostrar errores.
        $errors = [];
        $forumErrors = [];
        $openCreatePostPanel = false;
        $formData = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'category' => trim((string) ($_POST['category'] ?? 'General')),
            'content' => trim((string) ($_POST['content'] ?? '')),
        ];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [$errors, $formData, $forumErrors, false, $openCreatePostPanel];
        }

        $redirectUrl = $this->foroRouteWithView($forumView);

        $action = (string) ($_POST['foro_action'] ?? '');
        $userId = $this->validatedSessionUserId();
        $postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;

        if ($action === 'toggle_favorite') {
            if ($userId === null) {
                $forumErrors[] = 'Debes iniciar sesion para agregar a favoritos.';
                return [$errors, $formData, $forumErrors, false, $openCreatePostPanel];
            }

            if ($postId <= 0 || !$foroModel->postExists($postId)) {
                $forumErrors[] = 'El POST seleccionado no existe.';
                return [$errors, $formData, $forumErrors, false, $openCreatePostPanel];
            }
        }

        if ($action === 'create_comment') {
            if ($userId === null) {
                $forumErrors[] = 'Debes iniciar sesion para comentar en el foro.';
                return [$errors, $formData, $forumErrors, false, $openCreatePostPanel];
            }

            if ($postId <= 0 || !$foroModel->postExists($postId)) {
                $forumErrors[] = 'El POST seleccionado no existe.';
                return [$errors, $formData, $forumErrors, false, $openCreatePostPanel];
            }
        }

        if ($action === 'toggle_favorite') {
            try {
                $favorited = $foroModel->toggleFavorite((int) $userId, $postId);
            } catch (\Throwable $exception) {
                $forumErrors[] = 'No se pudo actualizar el favorito. Intentalo de nuevo.';
                return [$errors, $formData, $forumErrors, false, $openCreatePostPanel];
            }

            $_SESSION['flash_success'] = $favorited ? 'POST anadido a favoritos.' : 'POST eliminado de favoritos.';
            header('Location: ' . $redirectUrl);

            return [$errors, $formData, $forumErrors, true, $openCreatePostPanel];
        }

        if ($action === 'create_comment') {
            $commentContent = trim((string) ($_POST['comment_content'] ?? ''));

            if ($commentContent === '') {
                $forumErrors[] = 'El comentario no puede estar vacio.';
                return [$errors, $formData, $forumErrors, false, $openCreatePostPanel];
            }

            if (mb_strlen($commentContent) > self::MAX_COMMENT_LENGTH) {
                $forumErrors[] = 'El comentario no puede superar los 1000 caracteres.';
                return [$errors, $formData, $forumErrors, false, $openCreatePostPanel];
            }

            $foroModel->createComment((int) $userId, $postId, $commentContent);
            $_SESSION['flash_success'] = 'Comentario publicado correctamente.';
            header('Location: ' . $redirectUrl);

            return [$errors, $formData, $forumErrors, true, $openCreatePostPanel];
        }

        if ($action !== 'create_post') {
            $forumErrors[] = 'Accion de foro no valida.';
            return [$errors, $formData, $forumErrors, false, $openCreatePostPanel];
        }

        if ($userId === null) {
            $errors[] = 'Debes iniciar sesion para crear un POST.';
            $openCreatePostPanel = true;
            return [$errors, $formData, $forumErrors, false, $openCreatePostPanel];
        }

        if ($formData['title'] === '') {
            $errors[] = 'El titulo es obligatorio.';
        } elseif (mb_strlen($formData['title']) > self::MAX_TITLE_LENGTH) {
            $errors[] = 'El titulo no puede superar los 100 caracteres.';
        }

        if ($formData['category'] === '') {
            $errors[] = 'La categoria es obligatoria.';
        } elseif (!in_array($formData['category'], self::ALLOWED_CATEGORIES, true)) {
            $errors[] = 'La categoria seleccionada no es valida.';
        }

        if ($formData['content'] === '') {
            $errors[] = 'El contenido del post es obligatorio.';
        }

        if ($errors !== []) {
            $openCreatePostPanel = true;
            return [$errors, $formData, $forumErrors, false, $openCreatePostPanel];
        }

        try {
            $foroModel->createPost($userId, $formData['title'], $formData['content'], $formData['category']);
        } catch (\Throwable $exception) {
            $errors[] = 'No se pudo publicar el POST. Inicia sesion de nuevo e intentalo otra vez.';
            $openCreatePostPanel = true;
            return [$errors, $formData, $forumErrors, false, $openCreatePostPanel];
        }
        $_SESSION['flash_success'] = 'POST creado correctamente.';

        // New posts should always be visible after creation, even if user was browsing favorites.
        header('Location: ' . route('foro'));

        return [$errors, $formData, $forumErrors, true, $openCreatePostPanel];
    }

    public function foro(): void
    {
        $foroModel = new ForoModel();
        $forumView = $this->currentForumView();

        [$postErrors, $postFormData, $forumErrors, $redirected, $openCreatePostPanel] = $this->createPostIfNeeded($foroModel, $forumView);

        if ($redirected) {
            return;
        }

        $search = trim((string) ($_GET['q'] ?? ''));
        $category = trim((string) ($_GET['cat'] ?? ''));
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $perPageOptions = [5, 15, 25, 50];
        $perPage = (int) ($_GET['per_page'] ?? 5);
        
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 5;
        }
        
        $currentUserId = $this->validatedSessionUserId();

        if ($forumView === self::FAVORITES_VIEW && $currentUserId === null) {
            $forumErrors[] = 'Debes iniciar sesion para ver tus favoritos.';
        }

        if ($category !== '' && !in_array($category, self::ALLOWED_CATEGORIES, true)) {
            $category = '';
        }

        try {
            $totalPosts = $foroModel->countPosts($search, $category, $currentUserId, $forumView === self::FAVORITES_VIEW);
            // Ajusta la paginacion al total real para evitar paginas vacias en filtros exigentes.
            $effectivePerPage = $totalPosts > 0 ? min($perPage, $totalPosts) : $perPage;
            $totalPages = max(1, (int) ceil($totalPosts / $effectivePerPage));
            $page = min($page, $totalPages);
            
            $posts = $foroModel->listPosts($search, $category, $currentUserId, $forumView === self::FAVORITES_VIEW, $page, $effectivePerPage);
            $commentsByPost = $foroModel->listCommentsByPostIds(array_map(static fn (array $post): int => (int) ($post['post_id'] ?? 0), $posts));
            $popularPosts = $foroModel->listPopularPosts(5);
        } catch (\Throwable $exception) {
            $posts = [];
            $commentsByPost = [];
            $popularPosts = [];
            $totalPages = 1;
            $page = 1;
            $totalPosts = 0;
            if ($forumErrors === []) {
                $forumErrors[] = 'No se pudo cargar el foro desde la base de datos.';
            }
        }

        $this->render('pages/foro', [
            'pageTitle' => 'Inamania - Foro',
            'styles' => $this->foroStyles(),
            'currentPage' => 'foro',
            'flashSuccess' => $this->consumeFlashSuccess(),
            'forumPosts' => $posts,
            'forumCategories' => self::ALLOWED_CATEGORIES,
            'forumPopularPosts' => $popularPosts,
            'forumCommentsByPost' => $commentsByPost,
            'forumSearch' => $search,
            'forumCategory' => $category,
            'forumView' => $forumView,
            'forumBaseRoute' => $this->foroRouteWithView($forumView),
            'forumErrors' => $forumErrors,
            'postErrors' => $postErrors,
            'postFormData' => $postFormData,
            'isAuthenticated' => $currentUserId !== null,
            'openCreatePostPanel' => $openCreatePostPanel,
            'allowedCategories' => self::ALLOWED_CATEGORIES,
            'currentForumPage' => $page,
            'totalForumPages' => $totalPages,
            'perPageOptions' => $perPageOptions,
            'currentPerPage' => $perPage,
            'totalForumPosts' => $totalPosts,
        ]);
    }
}
