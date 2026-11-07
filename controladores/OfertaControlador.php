<?php
// Controlador: recibe lo que pide el usuario, le pide los datos al modelo y elige qué vista mostrar.
class OfertaControlador {

    private Oferta $modelo;

    public function __construct(Oferta $modelo) {
        $this->modelo = $modelo;
    }

    // Página principal: buscador público con filtros.
    public function buscar(): void {
        $ofertas = $this->modelo->buscar($_GET);
        $this->mostrar('buscar', ['ofertas' => $ofertas]);
    }

    // Panel de administración: listado + formulario de alta/edición.
    public function admin(): void {
        if (empty($_SESSION['admin'])) {
            $this->mostrar('login');
            return;
        }
        $ofertaEditada = isset($_GET['id']) ? $this->modelo->buscarPorId((int) $_GET['id']) : null;
        $this->mostrar('admin', [
            'ofertas' => $this->modelo->buscar($_GET),
            'ofertaEditada' => $ofertaEditada,
        ]);
    }

    public function entrar(): void {
        $this->exigirPost();
        if (password_verify($_POST['clave'] ?? '', CLAVE_ADMIN)) {
            session_regenerate_id(true); // nuevo id de sesión al iniciar, para que no se pueda reutilizar uno viejo
            $_SESSION['admin'] = true;
        } else {
            $_SESSION['mensaje'] = 'Contraseña incorrecta.';
        }
        $this->irA('admin');
    }

    public function salir(): void {
        session_destroy();
        $this->irA('buscar');
    }

    // Alta (sin id) o modificación (con id) de una oferta cargada a mano.
    public function guardar(): void {
        $this->exigirAdmin();
        $oferta = [
            'titulo'             => trim($_POST['titulo'] ?? ''),
            'empresa'            => trim($_POST['empresa'] ?? ''),
            'departamento'       => $_POST['departamento'] ?? '',
            'tipo'               => $_POST['tipo'] ?? '',
            'salario'            => trim($_POST['salario'] ?? ''),
            'postulacion_online' => isset($_POST['postulacion_online']),
            'primer_empleo'      => isset($_POST['primer_empleo']),
            'enlace'             => trim($_POST['enlace'] ?? ''),
            'fecha'              => ($_POST['fecha'] ?? '') ?: date('Y-m-d'),
        ];
        $error = $this->validar($oferta);
        if ($error) {
            $_SESSION['mensaje'] = $error;
            $this->irA('admin');
        }

        try {
            if (!empty($_POST['id'])) {
                $this->modelo->actualizar((int) $_POST['id'], $oferta);
                $_SESSION['mensaje'] = 'Oferta modificada.';
            } else {
                $this->modelo->guardar($oferta);
                $_SESSION['mensaje'] = 'Oferta agregada.';
            }
        } catch (PDOException $excepcion) {
            if ($excepcion->getCode() != 23000) throw $excepcion; // 23000 = dato repetido (enlace UNIQUE)
            $_SESSION['mensaje'] = 'Ya existe otra oferta con ese enlace.';
        }
        $this->irA('admin');
    }

    public function eliminar(): void {
        $this->exigirAdmin();
        $this->modelo->eliminar((int) ($_POST['id'] ?? 0));
        $_SESSION['mensaje'] = 'Oferta eliminada.';
        $this->irA('admin');
    }

    // Trae las ofertas nuevas de cada portal. Si un portal falla, se avisa y se sigue con los demás.
    public function actualizar(): void {
        $this->exigirAdmin();
        set_time_limit(0); // los portales pueden tardar minutos; sin esto PHP corta a los 30 s
        $resumen = [];
        foreach ($this->modelo->fuentes() as $fuente) {
            $inicio = $this->modelo->horaActual();
            try {
                $lector = new $fuente['clase'](); // ej: "BuscoJobs" -> new BuscoJobs()
                $ofertas = $lector->obtenerOfertas();
                foreach ($ofertas as $oferta) {
                    $this->modelo->guardar($oferta, $fuente['id']);
                }
                // Solo se borran las cerradas si la fuente respondió algo; si vino vacía, puede estar caída.
                $cerradas = count($ofertas) > 0 ? $this->modelo->borrarNoVistas($fuente['id'], $inicio) : 0;
                $resumen[] = "{$fuente['nombre']}: " . count($ofertas) . " ofertas, $cerradas cerradas.";
            } catch (Throwable $excepcion) { // Throwable atrapa cualquier falla, incluso datos con formato inesperado
                error_log("UTalent - {$fuente['nombre']}: " . $excepcion->getMessage());
                $resumen[] = "{$fuente['nombre']}: no respondió ({$excepcion->getMessage()}).";
            }
        }
        $_SESSION['mensaje'] = implode(' ', $resumen);
        $this->irA('admin');
    }

    // ---- Funciones de apoyo ----

    private function validar(array $oferta): ?string {
        if ($oferta['titulo'] === '' || $oferta['empresa'] === '') return 'El título y la empresa son obligatorios.';
        if (!in_array($oferta['departamento'], [...DEPARTAMENTOS, 'Varios departamentos', 'Sin especificar'])) return 'Departamento no válido.';
        if (!in_array($oferta['tipo'], ['Público', 'Privado'])) return 'Tipo no válido.';
        // Solo enlaces http/https: así nadie puede guardar un enlace "javascript:" que ejecute código.
        if ($oferta['enlace'] !== '' && !preg_match('#^https?://#', $oferta['enlace'])) return 'El enlace debe empezar con http:// o https://';
        if (mb_strlen($oferta['titulo']) > 255 || mb_strlen($oferta['salario']) > 60) return 'Algún texto es demasiado largo.';
        if (!DateTime::createFromFormat('Y-m-d', $oferta['fecha'])) return 'Fecha no válida.';
        return null;
    }

    // Las acciones que cambian datos solo aceptan POST del admin y con el token del formulario.
    // El token evita que otra página haga enviar un formulario al navegador del admin (CSRF).
    private function exigirAdmin(): void {
        $this->exigirPost();
        if (empty($_SESSION['admin'])) $this->irA('admin');
    }

    private function exigirPost(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['token'] ?? '', $_POST['token'] ?? '')) {
            http_response_code(400);
            exit('Pedido no válido.');
        }
    }

    // Convierte ['ofertas' => ...] en la variable $ofertas y carga la plantilla, que incluye la vista.
    private function mostrar(string $vista, array $datos = []): void {
        extract($datos);
        $mensaje = $_SESSION['mensaje'] ?? null;
        unset($_SESSION['mensaje']); // el mensaje se muestra una sola vez
        require 'vistas/plantilla.php';
    }

    private function irA(string $accion): never {
        header("Location: index.php?accion=$accion");
        exit;
    }
}
