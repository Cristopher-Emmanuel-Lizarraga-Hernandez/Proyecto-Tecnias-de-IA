<?php
namespace app\models;

require_once __DIR__ . '/../../config/database.php';
use Database;
use PDO;

class BitacoraRecogida
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConexion();
    }

    public function registrarEntrega(array $datos): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO bitacora_entregas (alumno_id, tutor_id, metodo_validacion, confianza_biometrica, estado_entrega) 
            VALUES (:alumno_id, :tutor_id, :metodo, :confianza, :estado)
        ');
        $stmt->execute([
            'alumno_id' => $datos['alumno_id'],
            'tutor_id' => $datos['tutor_id'],
            'metodo' => $datos['metodo'], // 'RECONOCIMIENTO_FACIAL' o 'PIN_RESPALDO'
            'confianza' => $datos['confianza_biometrica'],
            'estado' => $datos['estado'] ?? 'PENDIENTE_AULA'
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function historial(int $limite = 50): array
    {
        // Actualizado con los JOINs correctos hacia la nueva estructura
        $stmt = $this->db->prepare("
            SELECT b.id, b.fecha_hora_verificacion, b.metodo_validacion, b.confianza_biometrica, b.estado_entrega,
                   CONCAT(t.nombre, ' ', t.apellido_paterno) AS tutor_nombre, 
                   CONCAT(a.nombre, ' ', a.apellido_paterno) AS alumno_nombre,
                   CONCAT(a.grado, '° ', a.grupo) AS grado_grupo
            FROM bitacora_entregas b
            LEFT JOIN tutores t ON b.tutor_id = t.id
            LEFT JOIN alumnos a ON b.alumno_id = a.id
            ORDER BY b.fecha_hora_verificacion DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contarRecogidasHoy(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total 
            FROM bitacora_entregas 
            WHERE DATE(fecha_hora_verificacion) = CURDATE()
        ");
        return (int) $stmt->fetch()['total'];
    }

    public function obtenerPendientesPorAula(string $aulaId): array
    {
        $stmt = $this->db->prepare("
            SELECT b.id, a.nombre AS alumno_nombre, t.nombre AS tutor_nombre, b.fecha_hora_verificacion
            FROM bitacora_entregas b
            INNER JOIN alumnos a ON b.alumno_id = a.id
            INNER JOIN tutores t ON b.tutor_id = t.id
            WHERE b.estado_entrega = 'PENDIENTE_AULA' AND a.aula_id = :aula_id
            ORDER BY b.fecha_hora_verificacion DESC
        ");
        $stmt->execute(['aula_id' => $aulaId]);
        return $stmt->fetchAll();
    }
}