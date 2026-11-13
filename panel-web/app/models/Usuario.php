<?php
/**
 * app/models/Usuario.php
 * Acceso a datos de la tabla 'usuarios' (quienes pueden entrar al panel).
 */
require_once __DIR__ . '/../../config/database.php';

class Usuario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConexion();
    }

    public function buscarPorCorreo(string $correo): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE correo = :correo AND activo = 1');
        $stmt->execute(['correo' => $correo]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function crear(string $nombre, string $correo, string $password, string $rol = 'personal'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nombre, correo, password_hash, rol)
             VALUES (:nombre, :correo, :hash, :rol)'
        );
        $stmt->execute([
            'nombre' => $nombre,
            'correo' => $correo,
            'hash'   => password_hash($password, PASSWORD_DEFAULT),
            'rol'    => $rol,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizarPassword(int $id, string $password): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
    }
}