<?php
declare(strict_types=1);

/* =========================================================
   LIRA FERRER — Modelo Sitio
   Textos, prensa, preguntas frecuentes y trayectoria.
   ========================================================= */
final class Sitio
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::conexion();
    }

    /** Configuración como objeto clave => valor */
    public function config(): array
    {
        $st = $this->db->query('SELECT clave, valor FROM sitio_config ORDER BY grupo ASC, clave ASC');
        $salida = [];
        foreach ($st->fetchAll() as $fila) {
            $salida[$fila['clave']] = $fila['valor'];
        }
        return $salida;
    }

    public function ficha(): array
    {
        return $this->db->query(
            'SELECT id, etiqueta, valor FROM ficha_pieza WHERE activo = 1 ORDER BY orden ASC, id ASC'
        )->fetchAll();
    }

    public function pilares(): array
    {
        return $this->db->query(
            'SELECT id, titulo, texto, icono FROM pilares WHERE activo = 1 ORDER BY orden ASC, id ASC'
        )->fetchAll();
    }

    public function prensa(): array
    {
        return $this->db->query(
            'SELECT id, medio, titulo, resumen, fecha, imagen, url
             FROM prensa WHERE activo = 1 ORDER BY orden ASC, id ASC'
        )->fetchAll();
    }

    public function reconocimientos(): array
    {
        return $this->db->query(
            'SELECT id, titulo, texto, imagen
             FROM reconocimientos WHERE activo = 1 ORDER BY orden ASC, id ASC'
        )->fetchAll();
    }

    public function faq(): array
    {
        return $this->db->query(
            'SELECT id, pregunta, respuesta FROM faq WHERE activo = 1 ORDER BY orden ASC, id ASC'
        )->fetchAll();
    }

    public function hitos(): array
    {
        return $this->db->query(
            'SELECT id, anio, titulo, texto FROM hitos WHERE activo = 1 ORDER BY orden ASC, id ASC'
        )->fetchAll();
    }

    /** Todo el contenido del sitio en una sola respuesta */
    public function todo(): array
    {
        return [
            'config'           => $this->config(),
            'ficha'            => $this->ficha(),
            'pilares'          => $this->pilares(),
            'prensa'           => $this->prensa(),
            'reconocimientos'  => $this->reconocimientos(),
            'faq'              => $this->faq(),
            'hitos'            => $this->hitos(),
        ];
    }
}
