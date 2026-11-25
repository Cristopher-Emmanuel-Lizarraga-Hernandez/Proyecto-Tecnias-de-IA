<?php
require_once __DIR__ . '/../../config/database.php';
class Aula
{
    private PDO $db;
    public function __construct() { $this->db = Database::getConexion(); }
    public function listarTodos(): array
    {
        return $this->db->query('SELECT a.*, p.nombre AS profesor, p.activo AS profesor_activo, (SELECT COUNT(*) FROM alumnos al WHERE al.aula_id = a.id) AS total_alumnos FROM aulas a JOIN profesores p ON p.id = a.profesor_id ORDER BY a.nombre')->fetchAll();
    }
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT a.*, p.nombre AS profesor, p.activo AS profesor_activo FROM aulas a JOIN profesores p ON p.id = a.profesor_id WHERE a.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    public function profesoresActivos(): array
    {
        return $this->db->query('SELECT id, nombre FROM profesores WHERE activo = 1 ORDER BY nombre')->fetchAll();
    }
    public function guardar(?int $id, string $nombre, int $profesor): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT id FROM profesores WHERE id = ? AND activo = 1 FOR UPDATE');
            $stmt->execute([$profesor]);
            if (!$stmt->fetch()) { $this->db->rollBack(); return false; }
            if ($id === null) {
                $stmt = $this->db->prepare('INSERT INTO aulas (nombre, profesor_id) VALUES (?, ?)');
                $stmt->execute([$nombre, $profesor]);
            } else {
                $stmt = $this->db->prepare('UPDATE aulas SET nombre = ?, profesor_id = ? WHERE id = ?');
                $stmt->execute([$nombre, $profesor, $id]);
            }
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
}
