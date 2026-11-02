<?php
declare(strict_types=1);

/* =========================================================
   LIRA FERRER — Modelo Producto
   ========================================================= */
final class Producto
{
    private const CAMPOS = 'id, slug, nombre, color, detalle, descripcion, precio, stock,
                            edicion_total, imagen, galeria, especificaciones, activo';
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::conexion();
    }

    /** Todas las piezas activas */
    public function todos(): array
    {
        $st = $this->db->query(
            'SELECT ' . self::CAMPOS . ' FROM productos WHERE activo = 1 ORDER BY orden ASC, id ASC'
        );
        return array_map(fn(array $f) => $this->formatear($f), $st->fetchAll());
    }

    /** Una pieza activa por slug */
    public function porSlug(string $slug): ?array
    {
        $st = $this->db->prepare(
            'SELECT ' . self::CAMPOS . ' FROM productos WHERE slug = ? AND activo = 1 LIMIT 1'
        );
        $st->execute([$slug]);
        $fila = $st->fetch();
        return $fila ? $this->formatear($fila) : null;
    }

    /** Bloquea la fila durante una compra (usar dentro de una transacción) */
    public function bloquearPorSlug(string $slug): ?array
    {
        $st = $this->db->prepare(
            'SELECT ' . self::CAMPOS . ' FROM productos WHERE slug = ? LIMIT 1 FOR UPDATE'
        );
        $st->execute([$slug]);
        $fila = $st->fetch();
        return $fila ? $this->formatear($fila) : null;
    }

    /** Resta stock solo si alcanza */
    public function descontarStock(int $id, int $cantidad): bool
    {
        $st = $this->db->prepare('UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?');
        $st->execute([$cantidad, $id, $cantidad]);
        return $st->rowCount() === 1;
    }

    private function formatear(array $f): array
    {
        return [
            'id'               => (int) $f['id'],
            'slug'             => $f['slug'],
            'nombre'           => $f['nombre'],
            'color'            => $f['color'],
            'detalle'          => $f['detalle'],
            'descripcion'      => $f['descripcion'],
            'precio'           => (int) $f['precio'],
            'stock'            => (int) $f['stock'],
            'edicion_total'    => (int) $f['edicion_total'],
            'imagen'           => $f['imagen'],
            'galeria'          => $this->decodificar($f['galeria'] ?? null),
            'especificaciones' => $this->decodificar($f['especificaciones'] ?? null),
            'activo'           => (bool) $f['activo'],
        ];
    }

    private function decodificar(?string $json): array
    {
        if (!$json) {
            return [];
        }
        $v = json_decode($json, true);
        return is_array($v) ? $v : [];
    }
}
