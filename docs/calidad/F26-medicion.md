# F26. Medición manual y comparación — columna "Inicial"

Copia independiente del apartado F26 de `Informe-Calidad.md`. Procedimiento completo en `docs/calidad/evidencias/medicion-manual.md`. No se usaron PHPStan ni PHPMD, por indicación del docente.

## Protocolo

- Entorno declarado en F22: PHP 8.3.33, CodeIgniter 4.5.1, PHPUnit 11.5.56. Los conteos se hicieron sobre el mismo archivo (SHA-256 idéntico en el clon Git) desde un puesto con PHP 8.2.12; las cifras son de lectura de código y no dependen de la versión. La medición final debe hacerse en el entorno de la línea base.
- Archivo medido: `app/Controllers/CarritoController.php` (375 líneas). Alcance final: el mismo archivo más `app/Services/CarritoService.php` para copias de consulta y bucles de total.
- Ancla: SHA-256 `1733064a99d1a416bc1f7ac6f91a7e25758b7050a013acaf2b3a242b812bfbb6`. Rama `refactor/carrito`; commit base F22 `ac64f7d`; commit sobre el que se registró la línea base `75f596c`.
- Evidencia: `docs/calidad/evidencias/phpunit-antes.txt` (Persona 1), `docs/calidad/evidencias/medicion-manual.md` y `docs/calidad/evidencias/phpunit-pruebas-carrito.txt` (Persona 3).

| Indicador (mismo alcance) | Inicial | Final | Interpretación |
| --- | --- | --- | --- |
| Líneas del controlador | 375 | (lo mide Persona 5) | Línea base del tamaño del controlador. Debe bajar al extraer el servicio; si el total del módulo sube, explicarlo. |
| Líneas de `procesar_pago` | 73 (233-305) | (lo mide Persona 5) | Método más largo; R1 debe reducirlo. |
| Líneas de `actualizar` | 44 (98-141) | (lo mide Persona 5) | Método que ataca R2; reducción moderada esperada. |
| Copias de la consulta de ítems | 4 (`join('tProductos'`) | (lo mide Persona 5) | Confirma DT-01. Debe quedar en 1 dentro del servicio. |
| Bucles que suman el total | 4 (`$total +=`) | (lo mide Persona 5) | Confirma DT-02. Debe quedar en 1 dentro del servicio. |
| Complejidad por conteo de `procesar_pago` | 8 | (lo mide Persona 5) | Confirma DT-03; se espera que baje al mover validación y cálculo. |
| Complejidad por conteo de `actualizar` | 11 | (lo mide Persona 5) | Confirma DT-04; R2 debe bajarla al extraer la guardada de cantidad. |
| Pruebas aprobadas y fallidas | OK: 12 pruebas, 26 aserciones, 0 fallos (6 originales + 6 de `CarritoTest.php`) | (lo mide Persona 5) | Condición inicial con la red de seguridad en verde sobre el código sin refactorizar. |

## Regla de conteo usada

- Líneas de método: desde la firma hasta la llave de cierre inclusive.
- Complejidad: dentro del rango se cuentan `if` (incluido el de `else if`), `foreach`, `&&`, `||`, `?:` y `??`, y se suma 1.
  - `procesar_pago`: `if`=5, `foreach`=2 → 5+2+1 = **8**.
  - `actualizar`: `if`=9, `||`=1 → 9+1+1 = **11**.

La Persona 5 debe repetir los mismos comandos y contar juntos controlador y servicio para las copias de consulta y los bucles de total; antes el servicio no existía y sumaba cero.
