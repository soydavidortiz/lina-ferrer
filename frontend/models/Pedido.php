<?php
declare(strict_types=1);

/* =========================================================
   LIRA FERRER — Modelo Pedido
   ========================================================= */
final class Pedido
{
    private PDO $db;

    /** Acepta la conexión o la crea sola si no se envía */
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::conexion();
    }

    /** Inserta el pedido y devuelve ['id' => ..., 'codigo' => 'LF-261024-A1B2C3'] */
    public function crear(array $d): array
    {
        $st = $this->db->prepare(
            'INSERT INTO pedidos
               (codigo, cliente_id, producto_id, cantidad, precio_unitario, envio, total,
                metodo_pago, departamento, ciudad, direccion, notas, ip)
             VALUES
               (:codigo, :cliente_id, :producto_id, :cantidad, :precio_unitario, :envio, :total,
                :metodo_pago, :departamento, :ciudad, :direccion, :notas, :ip)'
        );

        for ($intento = 0; $intento < 5; $intento++) {
            $codigo = $this->generarCodigo();
            try {
                $st->execute([
                    ':codigo'          => $codigo,
                    ':cliente_id'      => $d['cliente_id'],
                    ':producto_id'     => $d['producto_id'],
                    ':cantidad'        => $d['cantidad'],
                    ':precio_unitario' => $d['precio_unitario'],
                    ':envio'           => $d['envio'],
                    ':total'           => $d['total'],
                    ':metodo_pago'     => $d['metodo_pago'],
                    ':departamento'    => $d['departamento'],
                    ':ciudad'          => $d['ciudad'],
                    ':direccion'       => $d['direccion'],
                    ':notas'           => ($d['notas'] ?? '') !== '' ? $d['notas'] : null,
                    ':ip'              => $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
                return ['id' => (int) $this->db->lastInsertId(), 'codigo' => $codigo];
            } catch (PDOException $e) {
                // 23000 = código duplicado → genera otro y reintenta
                if ((string) $e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }
        throw new RuntimeException('No se pudo generar un código de pedido único.');
    }

    /** Busca un pedido por su código (útil para consultas futuras) */
    public function porCodigo(string $codigo): ?array
    {
        $st = $this->db->prepare(
            'SELECT p.*, c.nombre AS cliente, pr.nombre AS producto
               FROM pedidos p
               JOIN clientes c   ON c.id  = p.cliente_id
               JOIN productos pr ON pr.id = p.producto_id
              WHERE p.codigo = ? LIMIT 1'
        );
        $st->execute([$codigo]);
        $fila = $st->fetch();
        return $fila ?: null;
    }

    private function generarCodigo(): string
    {
        return 'LF-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}
