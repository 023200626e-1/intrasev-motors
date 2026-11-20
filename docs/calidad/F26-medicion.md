# F26. Medición manual y comparación

Copia independiente del apartado F26 de `Informe-Calidad.md`. Sin PHPStan ni PHPMD, por indicación del docente.

- Entorno declarado en F22: PHP 8.3.33, CodeIgniter 4.5.1, PHPUnit 11.5.56. Tanto la línea base como la medición final se ejecutaron con PHP 8.2.12; las cifras son conteo de código y no dependen de la versión.
- Archivos medidos: `app/Controllers/CarritoController.php` (375 → 366 líneas) y `app/Services/CarritoService.php` (no existía → 64 líneas). Total del módulo: 375 → 430 líneas.
- Anclas Git: commit base F22 `ac64f7d`, línea base del turno 2 `75f596c`, código final refactorizado `f319f92`. Los commits posteriores son solo documentación.
- Evidencia: `docs/calidad/evidencias/medicion-manual.md`, `metricas-final.txt`, `phpunit-antes.txt`, `phpunit-pruebas-carrito.txt`, `phpunit-r1.txt`, `phpunit-despues.txt` y `diff-final.patch`.

| Indicador (mismo alcance) | Inicial | Final | Interpretación |
| :--- | :--- | :--- | :--- |
| Líneas del controlador | 375 | 366 | Bajó 9. R1 eliminó 4 consultas y 4 bucles; R2 sumó constantes y 2 métodos privados. |
| Líneas de `procesar_pago` | 73 (233-305) | 67 (238-304) | Bajó 6: salieron la consulta y el bucle de validación de stock. La transacción sigue en el controlador. |
| Líneas de `actualizar` | 44 (98-141) | 36 (118-153) | Bajó 8 por `guardarCantidad()` y `esItemDelUsuario()`. |
| Copias de la consulta de ítems | 4 (`join('tProductos'`) | 1 (en `CarritoService`) | DT-01 resuelto. |
| Bucles que suman el total | 4 (`$total +=`) | 1 (en `CarritoService`) | DT-02 resuelto conservando `$total = 0` entero. |
| Comparaciones `['cantidad'] >` (indicador adicional) | no medido | 1 (en `CarritoService`) | Validación de stock centralizada; el controlador elige el mensaje. |
| Complejidad por conteo de `procesar_pago` | 8 | 7 (5 `if` + 1 `foreach` + 1) | Bajó 1 al mover el bucle de stock al servicio. |
| Complejidad por conteo de `actualizar` | 11 | 8 (7 `if` + 1) | Bajó 3: dos `if` se fueron a `guardarCantidad()` y el `||` se fue a `esItemDelUsuario()`. |
| Pruebas aprobadas y fallidas | OK: 12 pruebas, 26 aserciones, 0 fallos | OK: 22 pruebas, 45 aserciones, 0 fallos | Subió la cobertura; las 6 pruebas originales de `CarritoTest` mantuvieron expectativas y resultado. 2 deprecaciones de `DatabaseTestTrait`, no del código agregado. |

**Regla de conteo:** líneas de método desde la firma hasta la llave de cierre inclusive; complejidad = `if` (incluido `else if`) + `foreach` + `&&` + `||` + `?:` + `??` + 1. Desglose final: `procesar_pago` = 5 + 1 + 1 = 7; `actualizar` = 7 + 1 = 8.

**Interpretación general:** ningún indicador de duplicación o complejidad subió. Aumentan el total de líneas del módulo (por la clase nueva) y el número de pruebas (por la red adicional); ambos están explicados y respaldados por `metricas-final.txt` y `phpunit-despues.txt`.
