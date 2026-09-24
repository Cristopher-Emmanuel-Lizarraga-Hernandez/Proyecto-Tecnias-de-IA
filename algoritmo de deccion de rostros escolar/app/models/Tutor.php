<?php
/**
 * app/models/Tutor.php
 * Acceso a datos de la tabla 'tutores'.
 *
 * Nota: 'embedding_label' es el identificador que usa el sistema Python de
 * reconocimiento facial (models/labels.json) para relacionar el modelo
 * entrenado con este tutor. Aquí solo lo guardamos como referencia.
 */
require_once __DIR__ . '/../../config/database.php';

class Tutor
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConexion();
    }

    public function registrar(string $nombre, int $alumnoId, int $embeddingLabel, string $parentesco = 'tutor'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tutores (nombre, alumno_id, embedding_label, parentesco)
             VALUES (:nombre, :alumno_id, :embedding_label, :parentesco)'
        );
        $stmt->execute([
            'nombre' => $nombre,
            'alumno_id' => $alumnoId,
            'embedding_label' => $embeddingLabel,
            'parentesco' => $parentesco,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function listarTodos(): array
    {
        $stmt = $this->db->query(
            'SELECT t.*, a.nombre AS alumno_nombre
             FROM tutores t JOIN alumnos a ON t.alumno_id = a.id
             ORDER BY t.nombre'
        );
        return $stmt->fetchAll();
    }

    public function siguienteEmbeddingLabel(): int
    {
        $stmt = $this->db->query('SELECT COALESCE(MAX(embedding_label), -1) + 1 AS siguiente FROM tutores');
        return (int) $stmt->fetch()['siguiente'];
    }

    public function eliminar(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM tutores WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
