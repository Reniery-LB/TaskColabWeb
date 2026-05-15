<?php
// assets/controllers/AuthController.php
require_once __DIR__ . '/../models/UserModel.php';

class AuthController {
    private $userModel;
    public function __construct() {
        $this->userModel = new UserModel();
    }

    public function register($name, $email, $password, $confirm, $is_admin) {
        $errors = [];

        if (empty($name)) $errors[] = "El nombre es obligatorio.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Correo inválido.";
        if (strlen($password) < 6) $errors[] = "La contraseña debe tener al menos 6 caracteres.";
        if ($password !== $confirm) $errors[] = "Las contraseñas no coinciden.";

        if (!empty($errors)) return ['ok' => false, 'errors' => $errors];

        if ($this->userModel->findByEmail($email)) {
            return ['ok' => false, 'errors' => ['El correo ya está registrado.']];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $insertedId = $this->userModel->create($name, $email, $hash, $is_admin ? 1 : 0);
        if ($insertedId) return ['ok' => true, 'user_id' => $insertedId];

        return ['ok' => false, 'errors' => ['Error al crear usuario.']];
    }

    public function login($email, $password) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Credenciales inválidas.'];
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user || !$user['is_active']) {
            return ['ok' => false, 'message' => 'Usuario no encontrado o inactivo.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'message' => 'Credenciales inválidas.'];
        }

        unset($user['password_hash']);
        return ['ok' => true, 'user' => $user];
    }
}
