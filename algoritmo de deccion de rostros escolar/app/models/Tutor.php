<?php
namespace app\models;

require_once __DIR__ . '/../../config/database.php';
use Database;
use PDO;

class Tutor
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConexion();
    }

    public function registrar(string $nombre, string $apellidoPaterno, string $telefono, string $pinRespaldo): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO tutores (nombre, apellido_paterno, telefono, pin_respaldo) 
            VALUES (:nombre, :apellido_paterno, :telefono, :pin_respaldo)
        ');
        $stmt->execute([
            'nombre' => $nombre,
            'apellido_paterno' => $apellidoPaterno,
            'telefono' => $telefono,
            'pin_respaldo' => password_hash($pinRespaldo, PASSWORD_DEFAULT) // Seguridad para el PIN
        ]);
        return (int) $this->db->lastInsertId();
    }

    // NUEVO: Esto maneja la relación de "Muchos a Muchos"
    public function vincularAlumno(int $tutorId, int $alumnoId, string $parentesco): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO alumnos_tutores (tutor_id, alumno_id, parentesco) 
            VALUES (:tutor_id, :alumno_id, :parentesco)
        ');
        $stmt->execute([
            'tutor_id' => $tutorId,
            'alumno_id' => $alumnoId,
            'parentesco' => $parentesco
        ]);
    }

    public function listarTodos(): array
    {
        $stmt = $this->db->query('SELECT * FROM tutores ORDER BY nombre');
        return $stmt->fetchAll();
    }

    // Busca un tutor y trae la lista de los alumnos que tiene autorizados recoger
    public function obtenerConAlumnos(int $tutorId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM tutores WHERE id = :id AND estado = "activo"');
        $stmt->execute(['id' => $tutorId]);
        $tutor = $stmt->fetch();

        if ($tutor) {
            $stmtAlumnos = $this->db->prepare('
                SELECT a.id, CONCAT(a.nombre, " ", a.apellido_paterno) AS nombre_completo, a.aula_id AS aula 
                FROM alumnos a
                INNER JOIN alumnos_tutores act ON a.id = act.alumno_id
                WHERE act.tutor_id = :tutor_id
            ');
            $stmtAlumnos->execute(['tutor_id' => $tutorId]);
            $tutor['alumnos'] = $stmtAlumnos->fetchAll();
            return $tutor;
        }
        return null;
    }

    public function buscarPorPin(string $pin): ?array
    {
        // Trae todos los tutores y verifica el hash del PIN
        $stmt = $this->db->query('SELECT id, pin_respaldo FROM tutores WHERE estado = "activo"');
        $tutores = $stmt->fetchAll();
        
        foreach ($tutores as $t) {
            if (password_verify($pin, $t['pin_respaldo'])) {
                return $this->obtenerConAlumnos($t['id']);
            }
        }
        return null;
    }
}
