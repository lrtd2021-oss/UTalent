<?php
// Modelo: es la única parte del sistema que escribe SQL. Siempre usa consultas
// preparadas (los "?") para que lo que escribe el usuario no pueda romper la consulta.
class Oferta {

    const CAMPOS = ['titulo', 'empresa', 'departamento', 'tipo', 'salario', 'postulacion_online', 'primer_empleo', 'enlace', 'fecha'];

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Arma el WHERE solo con los filtros que el usuario completó.
    public function buscar(array $filtros): array {
        $condiciones = ['1 = 1'];
        $valores = [];
        if (!empty($filtros['texto'])) {
            $condiciones[] = '(o.titulo LIKE ? OR o.empresa LIKE ?)';
            array_push($valores, "%{$filtros['texto']}%", "%{$filtros['texto']}%");
        }
        if (!empty($filtros['departamento'])) {
            $condiciones[] = 'o.departamento = ?';
            $valores[] = $filtros['departamento'];
        }
        if (!empty($filtros['tipo'])) {
            $condiciones[] = 'o.tipo = ?';
            $valores[] = $filtros['tipo'];
        }
        if (!empty($filtros['con_salario']))   $condiciones[] = 'o.salario IS NOT NULL';
        if (!empty($filtros['online']))        $condiciones[] = 'o.postulacion_online = 1';
        if (!empty($filtros['primer_empleo'])) $condiciones[] = 'o.primer_empleo = 1';

        $sql = 'SELECT o.*, f.nombre AS fuente FROM ofertas o LEFT JOIN fuentes f ON f.id = o.fuente_id
                WHERE ' . implode(' AND ', $condiciones) . ' ORDER BY o.fecha DESC LIMIT 200';
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($valores);
        return $consulta->fetchAll();
    }

    public function buscarPorId(int $id): ?array {
        $consulta = $this->pdo->prepare('SELECT * FROM ofertas WHERE id = ?');
        $consulta->execute([$id]);
        return $consulta->fetch() ?: null;
    }

    // Alta. Si ya existe una oferta con el mismo enlace, la actualiza en vez de duplicarla.
    public function guardar(array $oferta, ?int $fuenteId = null): void {
        $sql = 'INSERT INTO ofertas (fuente_id, ' . implode(', ', self::CAMPOS) . ')
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), empresa = VALUES(empresa),
                    departamento = VALUES(departamento), salario = VALUES(salario), vista_en = NOW()';
        $this->pdo->prepare($sql)->execute([$fuenteId, ...$this->valores($oferta)]);
    }

    // Modificación (desde el panel de administración).
    public function actualizar(int $id, array $oferta): void {
        $sql = 'UPDATE ofertas SET ' . implode(' = ?, ', self::CAMPOS) . ' = ? WHERE id = ?';
        $this->pdo->prepare($sql)->execute([...$this->valores($oferta), $id]);
    }

    // Baja.
    public function eliminar(int $id): void {
        $this->pdo->prepare('DELETE FROM ofertas WHERE id = ?')->execute([$id]);
    }

    // Borra las ofertas de una fuente que no aparecieron en la última actualización (ya cerraron).
    public function borrarNoVistas(int $fuenteId, string $desde): int {
        $consulta = $this->pdo->prepare('DELETE FROM ofertas WHERE fuente_id = ? AND vista_en < ?');
        $consulta->execute([$fuenteId, $desde]);
        return $consulta->rowCount();
    }

    public function fuentes(): array {
        return $this->pdo->query('SELECT * FROM fuentes')->fetchAll();
    }

    // Se usa la hora de MySQL (no la de PHP) para comparar con vista_en sin problemas de zona horaria.
    public function horaActual(): string {
        return $this->pdo->query('SELECT NOW()')->fetchColumn();
    }

    // Pasa la oferta al orden de CAMPOS, listo para los "?" de la consulta.
    private function valores(array $oferta): array {
        $valores = [];
        foreach (self::CAMPOS as $campo) {
            $valor = $oferta[$campo] ?? null;
            if (is_bool($valor)) $valor = (int) $valor; // MySQL guarda true/false como 1/0
            if ($valor === '') $valor = null;           // campo vacío del formulario = sin dato
            $valores[] = $valor;
        }
        return $valores;
    }
}
