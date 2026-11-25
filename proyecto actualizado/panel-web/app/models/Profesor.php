<?php
require_once __DIR__ . '/../../config/database.php';

class Profesor
{
    private PDO $db;
    public function __construct() { $this->db = Database::getConexion(); }
    public function listarTodos(): array
    {
        return $this->db->query('SELECT * FROM profesores ORDER BY nombre')->fetchAll();
    }
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM profesores WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    public function registrar(string $nombre, string $correo): void
    {
        $stmt = $this->db->prepare('INSERT INTO profesores (nombre, correo) VALUES (?, ?)');
        $stmt->execute([$nombre, $correo]);
    }
    public function actualizar(int $id, string $nombre, string $correo): void
    {
        $stmt = $this->db->prepare('UPDATE profesores SET nombre = ?, correo = ? WHERE id = ?');
        $stmt->execute([$nombre, $correo, $id]);
    }
    public function cambiarEstado(int $id, int $activo): void
    {
        $stmt = $this->db->prepare('UPDATE profesores SET activo = ? WHERE id = ?');
        $stmt->execute([$activo, $id]);
    }
}
