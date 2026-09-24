<?php
/**
 * config/database.php
 * Conexión PDO a la base de datos 'pase_inteligente_escolar' (misma que
 * usa el sistema Python de reconocimiento facial — comparten schema.sql).
 */

class Database
{
    private static string $host = '192.168.10.215';
    private static string $dbname = 'pase_inteligente_escolar';
    private static string $user = 'debian';
    private static string $password = 'debian'; 
    private static ?PDO $conexion = null;

    public static function getConexion(): PDO
    {
        if (self::$conexion === null) {
            try {
                $dsn = 'mysql:host=' . self::$host . ';dbname=' . self::$dbname . ';charset=utf8mb4';
                self::$conexion = new PDO($dsn, self::$user, self::$password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                die('Error de conexión a la base de datos: ' . $e->getMessage());
            }
        }
        return self::$conexion;
    }
}
