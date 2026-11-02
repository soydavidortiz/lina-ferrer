<?php
declare(strict_types=1);

/* =========================================================
   LIRA FERRER — Modelo Cliente
   ========================================================= */
final class Cliente
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::conexion();
    }

    /** Crea el cliente o actualiza sus datos si el correo ya existe. Devuelve el id. */
    public function guardar(array $d): int
    {
        $st = $this->db->prepare('SELECT id FROM clientes WHERE email = ? LIMIT 1');
        $st->execute([$d['email']]);
        $id = $st->fetchColumn();

        $valores = [
            ':nombre'       => $d['nombre'],
            ':email'        => $d['email'],
            ':telefono'     => $d['telefono'],
            ':documento'    => $d['documento'],
            ':departamento' => $d['departamento'],
            ':ciudad'       => $d['ciudad'],
            ':direccion'    => $d['direccion'],
        ];

        if ($id) {
            $up = $this->db->prepare(
                'UPDATE clientes
                    SET nombre = :nombre, telefono = :telefono, documento = :documento,
                        departamento = :departamento, ciudad = :ciudad, direccion = :direccion
                  WHERE email = :email'
            );
            $up->execute($valores);
            return (int) $id;
        }

        $in = $this->db->prepare(
            'INSERT INTO clientes (nombre, email, telefono, documento, departamento, ciudad, direccion)
             VALUES (:nombre, :email, :telefono, :documento, :departamento, :ciudad, :direccion)'
        );
        $in->execute($valores);
        return (int) $this->db->lastInsertId();
    }
}
