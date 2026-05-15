<?php
// assets/controllers/UserController.php
require_once __DIR__ . '/../models/UserModel.php';

class UserController {
    private $model;
    public function __construct() {
        $this->model = new UserModel();
    }

    public function listAll() {
        return $this->model->getAllWithTaskCount();
    }

    public function get($id) {
        return $this->model->findById($id);
    }

    public function update($id, $data) {
        return $this->model->update($id, $data);
    }

    public function delete($id) {
        return $this->model->delete($id);
    }
}
