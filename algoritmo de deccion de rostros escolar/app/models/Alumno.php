<?php
/**
 * app/models/Alumno.php
 * Acceso a datos de la tabla 'alumnos'.
 */
require_once __DIR__ . '/../../config/database.php';

class Alumno
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConexion();
    }

    public function registrar(string $nombre, ?string $grupo): int
    {
        $stmt = $this->db->prepare('INSERT INTO alumnos (nombre, grupo) VALUES (:nombre, :grupo)');
        $stmt->execute(['nombre' => $nombre, 'grupo' => $grupo]);
        return (int) $this->db->lastInsertId();
    }

    public function listarTodos(): array
    {
        $stmt = $this->db->query('SELECT * FROM alumnos ORDER BY nombre');
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM alumnos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
