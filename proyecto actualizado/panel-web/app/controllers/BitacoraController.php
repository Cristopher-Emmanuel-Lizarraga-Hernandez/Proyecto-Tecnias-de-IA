<?php
/**
 * app/models/BitacoraRecogida.php
 * Acceso a datos de la tabla 'bitacora_recogida' — el registro auditable
 * que escribe main.py (Python) cada vez que hay un intento de recogida.
 * Desde PHP normalmente solo LEEMOS esta tabla (panel de reportes).
 */
require_once __DIR__ . '/../../config/database.php';

class BitacoraRecogida
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConexion();
    }

    public function historial(int $limite = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.fecha_hora, t.nombre AS tutor, a.nombre AS alumno,
                    b.confianza, b.autorizado
             FROM bitacora_recogida b
             LEFT JOIN tutores t ON b.tutor_id = t.id
             LEFT JOIN alumnos a ON b.alumno_id = a.id
             ORDER BY b.fecha_hora DESC
             LIMIT :limite"
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contarIntentosNoAutorizados(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) AS total FROM bitacora_recogida WHERE autorizado = 0');
        return (int) $stmt->fetch()['total'];
    }

    public function contarRecogidasHoy(): int
    {
        $stmt = $this->db->query(
            'SELECT COUNT(*) AS total FROM bitacora_recogida
             WHERE autorizado = 1 AND DATE(fecha_hora) = CURDATE()'
        );
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Historial con los nombres de columna que espera la vista del dashboard.
     * Los alias adaptan nuestra tabla bitacora_recogida a esa vista.
     * Nota: 'confianza_biometrica' es la DISTANCIA de LBPH (menor = mejor
     * coincidencia), no un porcentaje.
     */
    public function historialDetallado(int $limite = 200): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.id,
                    b.fecha_hora AS fecha_hora_verificacion,
                    COALESCE(a.nombre, '—') AS alumno_nombre,
                    COALESCE(a.grupo, '—') AS grado_grupo,
                    COALESCE(t.nombre, 'Persona no reconocida') AS tutor_nombre,
                    'RECONOCIMIENTO_FACIAL' AS metodo_validacion,
                    b.confianza AS confianza_biometrica,
                    IF(b.autorizado = 1, 'ENTREGADO', 'NO_AUTORIZADO') AS estado_entrega
             FROM bitacora_recogida b
             LEFT JOIN tutores t ON b.tutor_id = t.id
             LEFT JOIN alumnos a ON b.alumno_id = a.id
             ORDER BY b.fecha_hora DESC
             LIMIT :limite"
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contarNoAutorizadosHoy(): int
    {
        $stmt = $this->db->query(
            'SELECT COUNT(*) AS total FROM bitacora_recogida
             WHERE autorizado = 0 AND DATE(fecha_hora) = CURDATE()'
        );
        return (int) $stmt->fetch()['total'];
    }
}