# UTalent

Buscador de ofertas de empleo para estudiantes de UTU. Junta en un solo lugar los llamados
públicos de **Uruguay Concursa** y **UTU**, y las ofertas privadas de **BuscoJobs**, con filtros por
departamento, tipo (público/privado), salario visible, postulación online y primer empleo.

Hecho con PHP y MySQL sin frameworks, siguiendo el patrón MVC. La fundamentación, el diagrama
de clases y el uso de IA están en [docs/documentacion.md](docs/documentacion.md).

## Cómo ejecutarlo (XAMPP)

1. Copiar la carpeta del proyecto dentro de `C:\xampp\htdocs\utalent`.
2. Iniciar **Apache** y **MySQL** desde el panel de XAMPP.
3. Abrir phpMyAdmin → pestaña **Importar** → elegir `base_de_datos.sql`.
4. Entrar a `http://localhost/utalent`.
5. Ir a **Administrar** (contraseña: `utalent2026`) y tocar **Actualizar ofertas**.
   Puede tardar de 15 segundos a 2 minutos, según qué tan rápido responda Uruguay Concursa.

**Sin Apache:** también funciona con el servidor que trae PHP. Con MySQL iniciado, ejecutar
`C:\xampp\php\php.exe -S localhost:8000` dentro de la carpeta del proyecto y entrar a
`http://localhost:8000`.

## Estructura

```
index.php                     Punto de entrada: según ?accion= llama al controlador
config.php                    Conexión a la base, contraseña del admin, departamentos
base_de_datos.sql             Crea las tablas "fuentes" y "ofertas"
modelos/Oferta.php            Modelo: todas las consultas SQL
controladores/OfertaControlador.php   Controlador: buscar, administrar, actualizar
Servicios/Fuente.php          Clase base que comparten todos los portales
Servicios/UruguayConcursa.php Lee la API JSON de Uruguay Concursa
Servicios/Utu.php             Lee la API JSON de WordPress de la web de UTU
Servicios/BuscoJobs.php       Lee el JSON que BuscoJobs incluye en su página
vistas/                       Las pantallas (HTML con un poco de PHP)
estilos.css                   Diseño
docs/                         Documentación y diagrama de clases
```

## Cómo agregar un portal nuevo

1. Crear `Servicios/NuevoPortal.php` con una clase que extienda `Fuente` y tenga el método
   `obtenerOfertas()`, que devuelve las ofertas con el mismo formato que las otras fuentes.
2. Agregar la fila en la base:
   `INSERT INTO fuentes (nombre, url, clase) VALUES ('Nuevo Portal', 'https://...', 'NuevoPortal');`

No hay que tocar nada más: el botón **Actualizar ofertas** recorre todas las fuentes de la tabla.
