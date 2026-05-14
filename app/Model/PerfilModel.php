<?php
declare(strict_types=1);

namespace App\Model;

class PerfilModel
{
    private UserModel $userModel;

    public function __construct(?UserModel $userModel = null)
    {
        $this->userModel = $userModel ?? new UserModel();
    }

    public function findById(int $userId): ?array
    {
        return $this->userModel->findById($userId);
    }

    public function usernameExistsForOther(string $username, int $userId): bool
    {
        return $this->userModel->usernameExistsForOther($username, $userId);
    }

    public function updateProfile(int $userId, string $username, ?string $avatarPath, ?string $role = null): void
    {
        $this->userModel->updateProfile($userId, $username, $avatarPath, $role);
    }
}
