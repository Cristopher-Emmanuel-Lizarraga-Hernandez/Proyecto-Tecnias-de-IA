<?php
namespace app\models;

require_once __DIR__ . '/../../config/database.php';
use Database;
use PDO;

class Alumno
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConexion();
    }

    public function registrar(string $matricula, string $nombre, string $apellidoPaterno, string $apellidoMaterno, int $grado, string $grupo, string $aulaId): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO alumnos (matricula, nombre, apellido_paterno, apellido_materno, grado, grupo, aula_id) 
            VALUES (:matricula, :nombre, :apellido_paterno, :apellido_materno, :grado, :grupo, :aula_id)
        ');
        $stmt->execute([
            'matricula' => $matricula,
            'nombre' => $nombre,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,
            'grado' => $grado,
            'grupo' => $grupo,
            'aula_id' => $aulaId
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function listarTodos(): array
    {
        $stmt = $this->db->query('SELECT * FROM alumnos ORDER BY grado, grupo, nombre');
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