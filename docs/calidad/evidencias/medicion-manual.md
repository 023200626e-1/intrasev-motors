# Medición manual "antes" — módulo Carrito

Turno: Persona 2. Fecha: 2026-11-18. Propósito: dejar fija la línea base y el procedimiento exacto para que la Persona 5 repita la misma medición al final, sin cambiar criterios. No se usaron PHPStan ni PHPMD, por indicación del docente.

## 1. Ancla de la medición

- Archivo medido: `app/Controllers/CarritoController.php`.
- Tamaño: 375 líneas.
- SHA-256 del archivo medido: `1733064a99d1a416bc1f7ac6f91a7e25758b7050a013acaf2b3a242b812bfbb6`.
- Entorno declarado en F22 por la Persona 1: PHP 8.3.33, CodeIgniter 4.5.1, PHPUnit 11.5.56. Los conteos de esta medición se ejecutaron en un puesto con PHP 8.2.12 sobre el mismo archivo (SHA-256 idéntico en el clon Git), por lo que las cifras de lectura de código no cambian. La medición final debe ejecutarse en el entorno de la línea base (PHP 8.3.33) para mantener la comparación.
- Rama: `refactor/carrito`. Commit base registrado en F22: `ac64f7d`. Commit sobre el que se registró esta línea base: `75f596c` (`git rev-parse --short HEAD` al iniciar el turno 2). El SHA-256 del archivo se mantiene como doble verificación.
- Nota: el directorio de trabajo donde se contó el código no tenía `.git`; el mismo `CarritoController.php` fue verificado en el clon de GitHub y ambos archivos tienen el SHA-256 indicado arriba.

## 2. Comandos exactos (Windows / PowerShell)

Ejecutar todos desde la raíz del proyecto:

```powershell
# 1. Líneas del archivo
(Get-Content app\Controllers\CarritoController.php).Count
# Resultado: 375

# 2. Copias de la consulta de ítems (DT-01)
(Select-String -Path app\Controllers\CarritoController.php -SimpleMatch "join('tProductos'").Count
# Resultado: 4   (líneas 39, 178, 242, 316)

# 3. Bucles que suman el total (DT-02)
(Select-String -Path app\Controllers\CarritoController.php -SimpleMatch '$total +=').Count
# Resultado: 4   (líneas 47, 194, 260, 328)

# 4. Ubicación de los métodos a medir
Select-String -Path app\Controllers\CarritoController.php -Pattern 'function (procesar_pago|actualizar)'
# Resultado: procesar_pago en 233, actualizar en 98

# 5. Sintaxis y pruebas
php -l app\Controllers\CarritoController.php
# No syntax errors detected in app\Controllers\CarritoController.php

vendor\bin\phpunit *> docs\calidad\evidencias\phpunit-antes.txt
# OK (6 tests, 6 assertions)
```

En Linux/Mac los equivalentes son `wc -l app/Controllers/CarritoController.php`, `grep -c "join('tProductos'" ...` y `grep -c '\$total +=' ...`.

## 3. Regla para contar líneas de método y complejidad

- **Líneas de un método:** desde la línea de la firma `public function ...` hasta la llave de cierre `}` inclusive. Se cuenta en el editor con el archivo sin modificar.
  - `procesar_pago`: líneas 233-305 → **73 líneas**.
  - `actualizar`: líneas 98-141 → **44 líneas**.
- **Complejidad por conteo:** dentro de ese rango de líneas se cuentan todas las apariciones de `if` (incluido el `if` de `else if`; el `else` suelto no se cuenta), `foreach`, `&&`, `||`, `?:` y `??`, y al total se le suma 1. Es una convención declarada, no una métrica de herramienta: si otra persona cuenta distinto, se anota la diferencia y se conserva esta regla.

Desglose real:

| Método | `if` | `foreach` | `&&` | `\|\|` | `?:` | `??` | +1 | Complejidad |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `procesar_pago` (233-305) | 5 (235, 248, 254, 269, 300) | 2 (253, 288) | 0 | 0 | 0 | 0 | 1 | **8** |
| `actualizar` (98-141) | 9 (100, 109, 115, 117, 121, 123, 128, 130, 133) | 0 | 0 | 1 (109) | 0 | 0 | 1 | **11** |

## 4. Resultados iniciales (columna "Inicial" de F26)

| Indicador | Valor inicial |
| --- | --- |
| Líneas del controlador | 375 |
| Líneas de `procesar_pago` | 73 |
| Líneas de `actualizar` | 44 |
| Copias de la consulta de ítems | 4 |
| Bucles que suman el total | 4 |
| Complejidad de `procesar_pago` | 8 |
| Complejidad de `actualizar` | 11 |
| Pruebas | OK: 6 pruebas, 6 aserciones, 0 fallos |

La suite actual cubre `tests/unit/PrestamoServiceMantenimientoTest.php`; todavía no existe `tests/feature/CarritoTest.php` (lo entrega la Persona 3). Por eso este resultado es la condición inicial de todo el proyecto y no se atribuye a la refactorización.

## 5. Instrucciones para la medición "después" (Persona 5)

1. Tomar el hash final con `git rev-parse --short HEAD`.
2. Repetir los mismos comandos del apartado 2 y la misma regla del apartado 3, sin cambiar los criterios.
3. Para las copias de la consulta y los bucles de total, contar **juntos** `app/Controllers/CarritoController.php` y `app/Services/CarritoService.php`. Antes el servicio no existía y sumaba cero, así que el alcance del módulo es comparable.
4. Llenar la columna "Final" de F26 e interpretar cada cifra, incluidas las que suban (por ejemplo, el total de líneas del módulo puede aumentar al aparecer el servicio).
5. Guardar la salida de PHPUnit en `docs/calidad/evidencias/phpunit-despues.txt` y no cambiar las expectativas de las pruebas de regresión.
