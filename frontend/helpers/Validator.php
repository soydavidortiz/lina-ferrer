<?php
declare(strict_types=1);

/* =========================================================
   Validador encadenable
   $v = (new Validator($datos))->requerido('email', '...')->email('email', '...');
   ========================================================= */
final class Validator
{
    private array $datos;
    private array $errores = [];

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function get(string $campo): string
    {
        $v = $this->datos[$campo] ?? '';
        return is_scalar($v) ? trim((string) $v) : '';
    }

    private function vacio(string $campo): bool
    {
        return $this->get($campo) === '';
    }

    private function tieneError(string $campo): bool
    {
        return isset($this->errores[$campo]);
    }

    private function agregar(string $campo, string $mensaje): void
    {
        if (!$this->tieneError($campo)) {
            $this->errores[$campo] = $mensaje;
        }
    }

    /** Las reglas opcionales se saltan si el campo está vacío o ya falló */
    private function omitir(string $campo): bool
    {
        return $this->vacio($campo) || $this->tieneError($campo);
    }

    public function requerido(string $campo, string $mensaje): self
    {
        if ($this->vacio($campo)) $this->agregar($campo, $mensaje);
        return $this;
    }

    public function longitud(string $campo, int $min, int $max, string $mensaje): self
    {
        if (!$this->omitir($campo)) {
            $largo = mb_strlen($this->get($campo));
            if ($largo < $min || $largo > $max) $this->agregar($campo, $mensaje);
        }
        return $this;
    }

    public function email(string $campo, string $mensaje): self
    {
        if (!$this->omitir($campo) && !filter_var($this->get($campo), FILTER_VALIDATE_EMAIL)) {
            $this->agregar($campo, $mensaje);
        }
        return $this;
    }

    public function telefono(string $campo, string $mensaje): self
    {
        if (!$this->omitir($campo)) {
            $digitos = preg_replace('/\D+/', '', $this->get($campo));
            if (strlen($digitos) < 7 || strlen($digitos) > 15) $this->agregar($campo, $mensaje);
        }
        return $this;
    }

    public function patron(string $campo, string $regex, string $mensaje): self
    {
        if (!$this->omitir($campo) && !preg_match($regex, $this->get($campo))) {
            $this->agregar($campo, $mensaje);
        }
        return $this;
    }

    public function entero(string $campo, int $min, int $max, string $mensaje): self
    {
        $v = filter_var($this->datos[$campo] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => $min, 'max_range' => $max],
        ]);
        if ($v === false) $this->agregar($campo, $mensaje);
        return $this;
    }

    public function enLista(string $campo, array $lista, string $mensaje): self
    {
        if (!$this->tieneError($campo) && !in_array($this->get($campo), $lista, true)) {
            $this->agregar($campo, $mensaje);
        }
        return $this;
    }

    public function aceptado(string $campo, string $mensaje): self
    {
        $v = $this->datos[$campo] ?? false;
        if (!in_array($v, [true, 1, '1', 'true', 'on'], true)) $this->agregar($campo, $mensaje);
        return $this;
    }

    public function falla(): bool
    {
        return !empty($this->errores);
    }

    public function errores(): array
    {
        return $this->errores;
    }
}
