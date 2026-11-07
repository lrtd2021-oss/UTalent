<?php
// Molde común de todas las fuentes (patrón Strategy): cada portal se lee distinto,
// pero todos devuelven las ofertas con el mismo formato. Agregar un portal nuevo es
// crear una clase hija en esta carpeta y una fila en la tabla "fuentes".
abstract class Fuente {

    // Devuelve una lista de ofertas; cada una es un array con las claves:
    // titulo, empresa, departamento, tipo, salario, postulacion_online, primer_empleo, enlace, fecha
    abstract public function obtenerOfertas(): array;

    // Descarga una URL. Si se pasa $datosPost, la pide por POST enviando ese array como JSON.
    protected function descargar(string $url, ?array $datosPost = null): string {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_USERAGENT => 'UTalent/1.0 (proyecto educativo UTU)',
        ]);
        if ($datosPost !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($datosPost));
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        $respuesta = curl_exec($curl);
        $codigo = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($respuesta === false || $codigo !== 200) {
            throw new Exception("No se pudo leer $url (código $codigo $error)");
        }
        return $respuesta;
    }

    // Busca el nombre de un departamento dentro de un texto libre ("Sede Central, Montevideo.").
    // Se comparan sin tildes ni mayúsculas porque cada portal los escribe distinto.
    protected function detectarDepartamento(?string $texto): string {
        $textoLimpio = $this->sinTildes($texto ?? '');
        $encontrados = [];
        foreach (DEPARTAMENTOS as $departamento) {
            if (str_contains($textoLimpio, $this->sinTildes($departamento))) {
                $encontrados[] = $departamento;
            }
        }
        if (count($encontrados) > 1) return 'Varios departamentos'; // ej: "Canelones, Rocha y Salto"
        return $encontrados[0] ?? 'Sin especificar';
    }

    // "\u{AD}" es un guion invisible que algunos portales meten dentro de las palabras ("Rí­o").
    private function sinTildes(string $texto): string {
        return strtolower(strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', "\u{AD}" => '']));
    }
}
