<?php
declare(strict_types=1);

namespace App\Model;

use PDO;

require_once __DIR__ . '/conexion.php';

class UserModel
{
	private PDO $connection;

	public function __construct(?PDO $connection = null)
	{
		$this->connection = $connection ?? \ConectarDB::conexion();
		$this->ensureRegistrationIpColumn();
	}

	private function ensureRegistrationIpColumn(): void
	{
		try {
			$statement = $this->connection->query("SHOW COLUMNS FROM usuario LIKE 'ip_registro'");
			$column = $statement !== false ? $statement->fetch(PDO::FETCH_ASSOC) : false;

			if ($column === false) {
				$this->connection->exec('ALTER TABLE usuario ADD COLUMN ip_registro VARCHAR(45) DEFAULT NULL');
			}
		} catch (\Throwable $exception) {
			// If schema migration fails, registration should still continue without blocking auth.
		}
	}

	public function usernameExists(string $username): bool
	{
		$statement = $this->connection->prepare('SELECT 1 FROM usuario WHERE nombre_usuario = :username LIMIT 1');
		$statement->execute(['username' => $username]);

		return (bool) $statement->fetchColumn();
	}

	public function emailExists(string $email): bool
	{
		$statement = $this->connection->prepare('SELECT 1 FROM usuario WHERE email = :email LIMIT 1');
		$statement->execute(['email' => $email]);

		return (bool) $statement->fetchColumn();
	}

	public function usernameExistsForOther(string $username, int $userId): bool
	{
		$statement = $this->connection->prepare(
			'SELECT 1 FROM usuario WHERE nombre_usuario = :username AND usuario_id <> :user_id LIMIT 1'
		);
		$statement->execute([
			'username' => $username,
			'user_id' => $userId,
		]);

		return (bool) $statement->fetchColumn();
	}

	public function createUser(string $username, string $email, string $password, ?string $registrationIp = null): int
	{
		if ($registrationIp !== null && filter_var($registrationIp, FILTER_VALIDATE_IP) === false) {
			$registrationIp = null;
		}

		$statement = $this->connection->prepare(
			'INSERT INTO usuario (nombre_usuario, email, contrasena, avatar, rol, ip_registro)
			 VALUES (:username, :email, :password, NULL, :role, :ip_registro)'
		);

		$statement->execute([
			'username' => $username,
			'email' => $email,
			'password' => password_hash($password, PASSWORD_DEFAULT),
			'role' => 'usuario',
			'ip_registro' => $registrationIp,
		]);

		return (int) $this->connection->lastInsertId();
	}

	public function authenticateByEmail(string $email, string $plainPassword): ?array
	{
		$statement = $this->connection->prepare(
			'SELECT usuario_id, nombre_usuario, email, contrasena, avatar, rol FROM usuario WHERE email = :email LIMIT 1'
		);
		$statement->execute(['email' => $email]);

		$user = $statement->fetch(PDO::FETCH_ASSOC);

		if (!is_array($user) || !isset($user['contrasena']) || !password_verify($plainPassword, (string) $user['contrasena'])) {
			return null;
		}

		return [
			'usuario_id' => (int) $user['usuario_id'],
			'nombre_usuario' => (string) $user['nombre_usuario'],
			'email' => (string) $user['email'],
			'avatar' => $user['avatar'] !== null ? (string) $user['avatar'] : null,
			'rol' => (string) ($user['rol'] ?? 'usuario'),
		];
	}

	public function findById(int $userId): ?array
	{
		$statement = $this->connection->prepare(
			'SELECT usuario_id, nombre_usuario, email, avatar, rol FROM usuario WHERE usuario_id = :user_id LIMIT 1'
		);
		$statement->execute(['user_id' => $userId]);

		$user = $statement->fetch(PDO::FETCH_ASSOC);

		if (!is_array($user)) {
			return null;
		}

		return [
			'usuario_id' => (int) $user['usuario_id'],
			'nombre_usuario' => (string) $user['nombre_usuario'],
			'email' => (string) $user['email'],
			'avatar' => $user['avatar'] !== null ? (string) $user['avatar'] : null,
			'rol' => (string) ($user['rol'] ?? 'usuario'),
		];
	}

	public function getActiveUserBlock(int $userId): ?array
	{
		try {
			$statement = $this->connection->prepare(
				'SELECT motivo, bloqueado_hasta
				 FROM bloqueo_usuario
				 WHERE usuario_id = :user_id
				   AND (bloqueado_hasta IS NULL OR bloqueado_hasta >= NOW())
				 LIMIT 1'
			);
			$statement->execute(['user_id' => $userId]);

			$row = $statement->fetch(PDO::FETCH_ASSOC);

			return is_array($row) ? $row : null;
		} catch (\Throwable $exception) {
			return null;
		}
	}

	public function getActiveIpBlock(string $ip): ?array
	{
		try {
			$statement = $this->connection->prepare(
				'SELECT motivo, bloqueado_hasta
				 FROM bloqueo_ip
				 WHERE ip = :ip
				   AND (bloqueado_hasta IS NULL OR bloqueado_hasta >= NOW())
				 LIMIT 1'
			);
			$statement->execute(['ip' => $ip]);

			$row = $statement->fetch(PDO::FETCH_ASSOC);

			return is_array($row) ? $row : null;
		} catch (\Throwable $exception) {
			return null;
		}
	}

	public function updateProfile(int $userId, string $username, ?string $avatarPath, ?string $role = null): void
	{
		if ($role === null) {
			$statement = $this->connection->prepare(
				'UPDATE usuario SET nombre_usuario = :username, avatar = :avatar WHERE usuario_id = :user_id'
			);
			$statement->execute([
				'username' => $username,
				'avatar' => $avatarPath,
				'user_id' => $userId,
			]);

			return;
		}

		$statement = $this->connection->prepare(
			'UPDATE usuario SET nombre_usuario = :username, avatar = :avatar, rol = :role WHERE usuario_id = :user_id'
		);
		$statement->execute([
			'username' => $username,
			'avatar' => $avatarPath,
			'role' => $role,
			'user_id' => $userId,
		]);
	}
}
