<?php
declare(strict_types=1);

namespace App\Model;

use PDO;

require_once __DIR__ . '/conexion.php';

class ForoModel
{
    private PDO $connection;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? \ConectarDB::conexion();
    }

    public function listPosts(string $search = '', string $category = '', ?int $currentUserId = null, bool $onlyFavorited = false, int $page = 1, int $perPage = 10): array
    {
        $search = trim($search);
        $category = trim($category);
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $conditions = [];
        $params = [];

        if ($onlyFavorited) {
            if ($currentUserId === null) {
                return [];
            }

            $conditions[] = 'EXISTS(SELECT 1 FROM favorito f_filter WHERE f_filter.post_id = p.post_id AND f_filter.usuario_id = :favorite_user_id)';
            $params[':favorite_user_id'] = $currentUserId;
        }

        if ($search !== '') {
            $conditions[] = '(p.titulo LIKE :search_title OR p.contenido LIKE :search_content OR u.nombre_usuario LIKE :search_user)';
            $searchLike = '%' . $search . '%';
            $params[':search_title'] = $searchLike;
            $params[':search_content'] = $searchLike;
            $params[':search_user'] = $searchLike;
        }

        if ($category !== '') {
            $conditions[] = 'p.categoria = :category';
            $params[':category'] = $category;
        }

        // Construye WHERE dinamico para reutilizar el mismo metodo con distintos filtros.
        $whereClause = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);

        // Si no hay usuario autenticado, el flag de favorito siempre es 0.
        $favoritedSql = '0 AS user_favorited';

        if ($currentUserId !== null) {
            $favoritedSql = 'EXISTS(SELECT 1 FROM favorito f2 WHERE f2.post_id = p.post_id AND f2.usuario_id = :current_user_id) AS user_favorited';
            $params[':current_user_id'] = $currentUserId;
        }

        $sql = "
            SELECT
                p.post_id,
                p.titulo,
                p.contenido,
                p.categoria,
                p.creado_en,
                u.usuario_id,
                u.nombre_usuario,
                u.avatar,
                COUNT(c.comentario_id) AS total_comentarios,
                (SELECT COUNT(*) FROM favorito f WHERE f.post_id = p.post_id) AS total_favoritos,
                {$favoritedSql}
            FROM post p
            INNER JOIN usuario u ON u.usuario_id = p.usuario_id
            LEFT JOIN comentario c ON c.post_id = p.post_id
            {$whereClause}
            GROUP BY p.post_id, p.titulo, p.contenido, p.categoria, p.creado_en, u.usuario_id, u.nombre_usuario, u.avatar
            ORDER BY p.creado_en DESC
            LIMIT :limit OFFSET :offset
        ";

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countPosts(string $search = '', string $category = '', ?int $currentUserId = null, bool $onlyFavorited = false): int
    {
        $search = trim($search);
        $category = trim($category);

        $conditions = [];
        $params = [];

        if ($onlyFavorited) {
            if ($currentUserId === null) {
                return 0;
            }

            $conditions[] = 'EXISTS(SELECT 1 FROM favorito f_filter WHERE f_filter.post_id = p.post_id AND f_filter.usuario_id = :favorite_user_id)';
            $params[':favorite_user_id'] = $currentUserId;
        }

        if ($search !== '') {
            $conditions[] = '(p.titulo LIKE :search_title OR p.contenido LIKE :search_content OR u.nombre_usuario LIKE :search_user)';
            $searchLike = '%' . $search . '%';
            $params[':search_title'] = $searchLike;
            $params[':search_content'] = $searchLike;
            $params[':search_user'] = $searchLike;
        }

        if ($category !== '') {
            $conditions[] = 'p.categoria = :category';
            $params[':category'] = $category;
        }

        $whereClause = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $sql = "
            SELECT COUNT(DISTINCT p.post_id) as total
            FROM post p
            INNER JOIN usuario u ON u.usuario_id = p.usuario_id
            {$whereClause}
        ";

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return (int) ($result['total'] ?? 0);
    }

    public function listCommentsByPostIds(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        $cleanIds = array_values(array_filter(array_map(static fn ($id): int => (int) $id, $postIds), static fn (int $id): bool => $id > 0));

        if ($cleanIds === []) {
            return [];
        }

        // Genera placeholders (?, ?, ?) para un IN seguro con PDO.
        $placeholders = implode(', ', array_fill(0, count($cleanIds), '?'));

        $sql = "
            SELECT
                c.comentario_id,
                c.post_id,
                c.contenido,
                c.creado_en,
                u.nombre_usuario
            FROM comentario c
            INNER JOIN usuario u ON u.usuario_id = c.usuario_id
            WHERE c.post_id IN ({$placeholders})
            ORDER BY c.post_id ASC, c.creado_en DESC
        ";

        $statement = $this->connection->prepare($sql);
        $statement->execute($cleanIds);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $commentsByPost = [];

        foreach ($rows as $row) {
            $postId = (int) ($row['post_id'] ?? 0);
            if ($postId <= 0) {
                continue;
            }

            // Agrupa por post_id para que la vista pueda pintar comentarios por tarjeta.
            if (!isset($commentsByPost[$postId])) {
                $commentsByPost[$postId] = [];
            }

            $commentsByPost[$postId][] = $row;
        }

        return $commentsByPost;
    }

    public function listLatestPosts(int $limit = 4): array
    {
        $limit = max(1, min(20, $limit));

        $sql = "
            SELECT
                p.post_id,
                p.titulo,
                p.contenido,
                p.categoria,
                p.creado_en,
                u.nombre_usuario
            FROM post p
            INNER JOIN usuario u ON u.usuario_id = p.usuario_id
            ORDER BY p.creado_en DESC
            LIMIT {$limit}
        ";

        $statement = $this->connection->query($sql);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listCategories(): array
    {
        $statement = $this->connection->query(
            'SELECT DISTINCT categoria FROM post WHERE categoria IS NOT NULL AND categoria <> "" ORDER BY categoria ASC'
        );

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_values(array_map(static fn (array $row): string => (string) $row['categoria'], $rows));
    }

    public function listPopularPosts(int $limit = 5): array
    {
        $limit = max(1, min(10, $limit));

        $sql = "
            SELECT
                p.post_id,
                p.titulo,
                p.contenido,
                COUNT(f.usuario_id) AS total_favoritos
            FROM post p
            LEFT JOIN favorito f ON f.post_id = p.post_id
            GROUP BY p.post_id, p.titulo, p.contenido, p.creado_en
            ORDER BY total_favoritos DESC, p.creado_en DESC
            LIMIT {$limit}
        ";

        $statement = $this->connection->query($sql);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createPost(int $userId, string $title, string $content, string $category): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO post (usuario_id, titulo, contenido, categoria) VALUES (:user_id, :title, :content, :category)'
        );

        $statement->execute([
            'user_id' => $userId,
            'title' => $title,
            'content' => $content,
            'category' => $category,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function postExists(int $postId): bool
    {
        $statement = $this->connection->prepare('SELECT 1 FROM post WHERE post_id = :post_id LIMIT 1');
        $statement->execute(['post_id' => $postId]);

        return (bool) $statement->fetchColumn();
    }

    public function createComment(int $userId, int $postId, string $content): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO comentario (post_id, usuario_id, contenido) VALUES (:post_id, :user_id, :content)'
        );

        $statement->execute([
            'post_id' => $postId,
            'user_id' => $userId,
            'content' => $content,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function toggleFavorite(int $userId, int $postId): bool
    {
        $check = $this->connection->prepare(
            'SELECT 1 FROM favorito WHERE usuario_id = :user_id AND post_id = :post_id LIMIT 1'
        );
        $check->execute([
            'user_id' => $userId,
            'post_id' => $postId,
        ]);

        if ((bool) $check->fetchColumn()) {
            $remove = $this->connection->prepare('DELETE FROM favorito WHERE usuario_id = :user_id AND post_id = :post_id');
            $remove->execute([
                'user_id' => $userId,
                'post_id' => $postId,
            ]);

            return false;
        }

        $add = $this->connection->prepare('INSERT INTO favorito (usuario_id, post_id) VALUES (:user_id, :post_id)');
        $add->execute([
            'user_id' => $userId,
            'post_id' => $postId,
        ]);

        return true;
    }
}
