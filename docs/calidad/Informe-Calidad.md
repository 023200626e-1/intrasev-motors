# Informe de calidad - Carrito y Checkout

## F22. Identificación y alcance

| Campo | Valor del equipo |
| :--- | :--- |
| **Proyecto, equipo y versión** | IntraServ Motors — Equipo: Jean Pol Sullca Caballero, Jhon Richard Daniel Guillen Hurtado, Frank Puma Ucharo, Richard Triveños Llacta, Renato Aldair Mamani Apaza — PHP: 8.3.33 — CodeIgniter: 4.5.1 |
| **Módulo elegido y razón** | Carrito y Checkout (`app/Controllers/CarritoController.php`); concentra 4 consultas SQL duplicadas, reglas de cálculo y validación de stock repetidas, y lógica transaccional de pago acoplada en el controlador. |
| **Requisito(s)/historia(s) asociados** | Gestión de carrito de compras y proceso de checkout/pago documentados en las fichas previas (`docs/mantenimiento/F15`–`F21`). |
| **Flujo observable y URL / operación** | `GET carrito/obtener`, `POST carrito/agregar`, `POST carrito/actualizar`, `GET carrito/checkout`, `POST carrito/procesar_pago`, `POST carrito/cancelar_pago` |
| **Clases/rutas de código examinadas** | `app/Controllers/CarritoController.php`, `app/Models/CarritoComprasModel.php`, `app/Models/ProductoModel.php`, `app/Models/PedidoModel.php`, `app/Models/DetallePedidoModel.php` |
| **Commit inicial (hash), rama y fecha** | `ac64f7d` — Rama: `refactor/carrito` — Fecha: 18/11/2026 |

---

## F23. Inventario y priorización de deuda técnica

Hallazgos verificados leyendo `app/Controllers/CarritoController.php` (375 líneas) y `app/Config/Routes.php`. Los números de línea son reales para el archivo medido. También se entrega una copia independiente en `docs/calidad/F23-inventario.md`.

| ID | Archivo:método y líneas | Smell o riesgo | Evidencia concreta | Efecto sobre el mantenimiento | Probabilidad de cambio y esfuerzo | Prioridad |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| DT-01 | `CarritoController::obtener` 37-41; `checkout` 176-180; `procesar_pago` 240-244; `cancelar_pago` 314-318 | Duplicación de consulta / acceso a datos repetido | La unión `tCarritoCompras` con `tProductos` filtrada por `id_usuario` aparece 4 veces. El `select` no es igual: `obtener` incluye `id_carrito`, `checkout` incluye `imagen`, y los métodos de pago omiten esas columnas. | Un cambio de esquema o de columnas obliga a editar 4 puntos; además cada ruta puede devolver un conjunto distinto de campos. | Probabilidad alta; esfuerzo medio-bajo (extraer la consulta a un servicio). | **Alta**: afecta carrito y pago, y cualquier cambio de esquema se propaga a 4 métodos. |
| DT-02 | `obtener` 44-48; `checkout` 188-195; `procesar_pago` 252-261; `cancelar_pago` 326-329 | Duplicación de reglas de negocio | `$total += $item['precio'] * $item['cantidad'];` aparece 4 veces. La validación `cantidad > stock` está en `checkout` (190) y `procesar_pago` (254), cada una con un mensaje distinto. | Cambiar la regla del total o del límite de stock exige tocar varios métodos y deja mensajes inconsistentes. | Probabilidad alta; esfuerzo medio. | **Alta**: son las reglas de stock y monto que protegen la compra. |
| DT-03 | `procesar_pago` 233-305 (73 líneas, complejidad 8); `cancelar_pago` 307-368; `checkout` 169-231 | Múltiples responsabilidades, acoplamiento y método extenso | El controlador abre la conexión con `\Config\Database::connect()` (173, 238, 312), inicia y cierra transacciones (264/298, 331/365), actualiza pedidos, descuenta stock (290-291) y vacía el carrito. No existe `app/Services`. | La lógica crítica de pago depende del HTTP para probarse y no se puede reutilizar. | Probabilidad alta; esfuerzo alto. | **Alta**: concentra las reglas más sensibles del módulo. |
| DT-04 | `CarritoController::actualizar` 98-141 (44 líneas, complejidad 11) | Método con varias rutas de decisión / complejidad local | Cadena `if / else if / else if` en 115, 121 y 128. La regla "cantidad ≤ 0 elimina el ítem" se repite en `restar` (123-127) y `fijar` (133-137). `sumar` rechaza si supera el stock (117-119), pero `fijar` lo ajusta al tope (130-132). | Dos criterios distintos de stock conviven en el mismo método; editar uno puede cambiar sin querer el otro. | Probabilidad media-alta; esfuerzo medio-bajo. | **Media**: genera complejidad real, pero su impacto está acotado a una acción del carrito. |
| DT-05 | `test` 13-17; `checkAuth` 24; `checkout` 205 y 214; `procesar_pago` 272 y 281; `cancelar_pago` 338 y 346; `Routes.php` 38 | Literales repetidos y código temporal | Los estados `'Pendiente'`, `'Pagado'` y `'Cancelado'` están escritos a mano; la comparación `id_rol == 1` también es literal. `test()` sigue comentado como "Método de prueba temporal" y tiene la ruta `carrito/test`. | Cambiar un estado o el rol administrador obliga a buscar en todo el controlador; además queda un endpoint temporal en producción. | Probabilidad media; esfuerzo bajo. | **Baja**: es ruido de mantenimiento, no altera la regla de negocio por sí solo. |

**Categoría no detectada:** nombres poco expresivos. Los métodos y variables principales describen su intención; no se inventa un hallazgo para llenar la categoría.

**Por qué se eligieron R1 y R2:** R1 (Extract Class) cubre DT-01, DT-02 y DT-03 por su prioridad alta y su impacto en stock y pago; aunque su esfuerzo es mayor, extraer `CarritoService` es viable porque las consultas y los bucles ya son independientes. R2 (Extract Method y constantes) cubre DT-04 y DT-05 por ser locales a `actualizar()` y a los literales, de bajo riesgo y ejecución rápida. Las Personas 4 y 5 confirmaron el alcance real en F24: R1 resuelve DT-01 y DT-02 y deja DT-03 parcial; R2 resuelve DT-04 y DT-05.

---

## F24. Plan de refactorización

Copia independiente de la columna R1 en `docs/calidad/F24-refactorizacion.md`.

| Campo | R1 (Persona 4: Richard Triveños Llacta) | R2 (Persona 5: Renato Aldair Mamani Apaza) |
| :--- | :--- | :--- |
| Hallazgo que resuelve | DT-01 y DT-02 completos; DT-03 solo en su parte de acceso a datos y reglas de negocio. La apertura/cierre de transacciones y la escritura de pedidos siguen en el controlador, así que DT-03 queda **resuelto en parte** y `INC-01` sigue pendiente. | DT-04 y DT-05 completos. `actualizar()` pasa de 44 a 36 líneas y su complejidad de 11 a 8; los literales de rol, acciones y estados quedan centralizados. |
| Tipo de técnica | `Extract Class` | `Extract Method` y reemplazo de literales por constantes |
| Archivo y método antes | `CarritoController::obtener`, `checkout`, `procesar_pago`, `cancelar_pago` | `CarritoController::actualizar` y los literales de estado en `checkout`, `procesar_pago` y `cancelar_pago` |
| Estructura deseada | `app/Services/CarritoService.php` con `obtenerItems($idUsuario)`, `calcularTotal($items)` y `primerItemSinStock($items)`. El controlador solo orquesta la petición y la respuesta. | Métodos privados `guardarCantidad()` y `esItemDelUsuario()`; constantes `ROL_ADMINISTRADOR`, `ACCION_*` y `ESTADO_*`. |
| Comportamiento que NO cambia | Respuestas JSON, mensajes, redirecciones, estado del pedido, stock descontado, las columnas del `select` de `obtener()`, el valor inicial `$total = 0` sin tipo `float` y el orden de validación (carrito vacío → stock). El controlador conserva sus mensajes propios en `checkout` y `procesar_pago`. | Mensajes, límites de stock, eliminación al llegar a 0, diferencia entre `sumar` (rechaza) y `fijar` (ajusta), acción desconocida y estados del pedido. Se conserva `==` en la comparación del rol, igual que en el original. |
| Pruebas que lo protegen | CP-01 a CP-06 de `tests/feature/CarritoTest.php`: `testObtenerSinSesionEsRechazado`, `testAdministradorNoPuedeAgregar`, `testAgregarSinStockEsRechazado`, `testProcesarPagoRechazaCantidadMayorAlStock`, `testProcesarPagoConCantidadIgualAlStock`, `testActualizarRestarEliminaItem`. Adicionalmente, 5 pruebas unitarias / 7 aserciones en `tests/unit/CarritoServiceTest.php`, que no reemplazan a la regresión. | CP-06 de `CarritoTest` más 5 pruebas nuevas de `tests/feature/CarritoActualizarTest.php` (12 aserciones): `sumar` sobre el límite, `fijar` ajustando al stock, `fijar` en 0, ítem de otro usuario y acción desconocida. CP-01 a CP-05 siguen en verde. |
| Riesgo o efecto colateral | Cambio de tipo del total, columnas del `select` y orden de validaciones. Se mitigaron conservando la aritmética y el `0` entero original, la lista de columnas de `obtener()` y la precedencia de las validaciones. No se movió la transacción al servicio por ser el cambio más riesgoso. | Tocar los métodos de pago que ya refactorizó la Persona 4 y confundir `sumar` con `fijar`. Se mitigó conservando las ramas originales y sustituyendo solo los literales por constantes del mismo valor. |
| Commit asociado | `980534c`, `aff4f4b`, `46ea5e4`, `40b1768`, `bb3b777` (cinco pasos de R1) y `d270502` (pruebas unitarias y evidencia). | `5ab9997` (regresión adicional), `2fd477e`, `34eb4af`, `af4eb21`, `f319f92` (cuatro pasos de R2). |

R1 terminó en verde: `CarritoTest` 6 pruebas / 20 aserciones, suite completa 17 pruebas / 33 aserciones, 0 fallos (`docs/calidad/evidencias/phpunit-r1.txt`). R2 terminó en verde: suite final 22 pruebas / 45 aserciones, 0 fallos (`docs/calidad/evidencias/phpunit-despues.txt`). Después de R1, el controlador tiene 0 copias de `join('tProductos'`, 0 de `$total +=` y 0 comparaciones `['cantidad'] >`; esas tres responsabilidades viven ahora en `CarritoService`. Después de R2, `actualizar()` quedó en 36 líneas y complejidad 8, sin cambiar mensajes ni la diferencia entre `sumar` y `fijar`.

---

## F25. Matriz de comportamiento y regresión

Seis escenarios escritos contra el comportamiento original antes de cualquier refactorización, ejecutados otra vez al cerrar R2 sin cambiar una sola expectativa. Copia independiente en `docs/calidad/F25-matriz.md`. Salida real: `docs/calidad/evidencias/phpunit-pruebas-carrito.txt` (Antes) y `docs/calidad/evidencias/phpunit-despues.txt` (Después).

- Entorno de ejecución: PHP 8.2.12, PHPUnit 11.5.56, base MySQL aislada `ds_g1_testing` con prefijo `db_` configurada solo en `.env` local (nunca se usó `ds_g1`). Las migraciones corren con `DatabaseTestTrait` (`$migrate = true`, `$refresh = true`, `$namespace = 'App'`). CSRF está desactivado, así que no se envió token.
- La migración original usa nombres de llaves foráneas que SQLite no acepta, por eso se siguió la alternativa de la guía: base MySQL de pruebas separada. No se cambió el esquema ni el código de producción.
- Resultado final: suite completa **22 pruebas, 45 aserciones, 0 fallos**. Desglose: `PrestamoServiceMantenimientoTest` 6/6, `CarritoTest` 6/20, `CarritoServiceTest` 5/7 y `CarritoActualizarTest` 5/12 (estas últimas son adicionales de R2). Hay 2 deprecaciones de PHPUnit originadas en `DatabaseTestTrait::resetMigrationSeedCount()` de las dos clases feature, no en las pruebas nuevas.

| CP | Escenario | Método de prueba | Antes (código original) | Después (código final) |
| :--- | :--- | :--- | :--- | :--- |
| CP-01 | `GET carrito/obtener` sin sesión | `testObtenerSinSesionEsRechazado` | Verde: JSON con `success:false` y mensaje exacto "Debes iniciar sesión para comprar." | Verde: mismo JSON y mismo mensaje exacto. |
| CP-02 | `POST carrito/agregar` con `id_rol = 1` | `testAdministradorNoPuedeAgregar` | Verde: JSON con `success:false` y "Los administradores no pueden realizar compras."; carrito sin filas | Verde: mismo mensaje; la constante `ROL_ADMINISTRADOR` conserva el valor 1. |
| CP-03 | Cliente con producto de `stock = 0` en `POST carrito/agregar` | `testAgregarSinStockEsRechazado` | Verde: `success:false` y "Producto sin stock disponible."; no se crea fila en `tCarritoCompras` | Verde: mismo mensaje y carrito sin filas. |
| CP-04 | Cantidad mayor al stock en `POST carrito/procesar_pago` | `testProcesarPagoRechazaCantidadMayorAlStock` | Verde: `success:false`, mensaje contiene "Laptop Gamer" y "2 disp"; el stock sigue en 2 y no hay pedidos | Verde: mismo mensaje, stock sigue en 2 y 0 pedidos. |
| CP-05 | Cantidad igual al stock en `POST carrito/procesar_pago` | `testProcesarPagoConCantidadIgualAlStock` | Verde: `success:true` con `id_pedido`; stock queda en 0, carrito vacío y pedido en `Pagado` | Verde: mismo resultado; `Pagado` ahora viene de `ESTADO_PAGADO`. |
| CP-06 | `POST carrito/actualizar` con `accion=restar` sobre cantidad 1 | `testActualizarRestarEliminaItem` | Verde: `success:true` y el ítem se elimina de `tCarritoCompras` | Verde: el ítem se elimina mediante `guardarCantidad()`; `success:true`. |

CP-04 y CP-05 protegen lo que toca R1 (stock, total, estado del pedido); CP-06 protege lo que toca R2. Las expectativas no cambiaron. Además, `CarritoActualizarTest.php` cubre cinco rutas adicionales de `actualizar()` que R2 tocó: `sumar` sobre el límite (rechaza), `fijar` por encima del stock (ajusta), `fijar` en 0 (elimina), ítem de otro usuario (`Item no válido.`) y acción desconocida (sin cambios).

---

## F26. Protocolo de análisis estático y comparación

Indicadores manuales repetibles, sin PHPStan ni PHPMD, por indicación del docente. Procedimiento y comandos exactos en `docs/calidad/evidencias/medicion-manual.md`;resultado final en `docs/calidad/evidencias/metricas-final.txt`; copia independiente en `docs/calidad/F26-medicion.md`.

- Entorno declarado en F22: PHP 8.3.33, CodeIgniter 4.5.1, PHPUnit 11.5.56. La medición final se ejecutó en el mismo puesto que la inicial, con PHP 8.2.12, porque no se dispuso del intérprete 8.3.33. Las cifras son conteo de código y no cambian con la versión; PHPUnit se ejecutó con 8.2.12 en ambos extremos.
- Archivos medidos: `app/Controllers/CarritoController.php` (366 líneas, SHA-256 `ffb63fdc0798c15888a24deb43a9a56b917b24a664da41c8dd0480964a6023ef`) y `app/Services/CarritoService.php` (64 líneas, SHA-256 `0d4dc6f0946e1d5742ff1aab50873f20b5cf50ad21867f0adaa7902bc9a08624`). Total del módulo: 430 líneas.
- Ancla Git: commit base F22 `ac64f7d`; línea base del turno 2 `75f596c`; código final refactorizado `f319f92`. Los commits posteriores a `f319f92` son solo documentación.
- Evidencia: `phpunit-antes.txt` (Persona 1), `phpunit-pruebas-carrito.txt` (Persona 3), `phpunit-r1.txt` (Persona 4), `phpunit-despues.txt`, `metricas-final.txt` y `diff-final.patch` (Persona 5).

| Indicador (mismo alcance) | Inicial | Final | Interpretación |
| :--- | :--- | :--- | :--- |
| Líneas del controlador | 375 | 366 | Bajó 9. R1 eliminó 4 consultas y 4 bucles; R2 sumó 7 constantes y 2 métodos privados, que compensan parte de la reducción. |
| Líneas de `procesar_pago` | 73 (233-305) | 67 (238-304) | Bajó 6: salieron la consulta y el bucle de validación de stock. La transacción sigue en el controlador por decisión de riesgo. |
| Líneas de `actualizar` | 44 (98-141) | 36 (118-153) | Bajó 8. `guardarCantidad()` absorbió los dos bloques de borrado/actualización y `esItemDelUsuario()` simplificó la guarda inicial. |
| Copias de la consulta de ítems | 4 (`join('tProductos'`) | 1 (en `CarritoService`) | DT-01 resuelto: una sola unión, con el `select` de `obtener()` para no cambiar el JSON. |
| Bucles que suman el total | 4 (`$total +=`) | 1 (en `CarritoService`) | DT-02 resuelto: una sola suma, conservando `$total = 0` sin tipo `float`. |
| Comparaciones `['cantidad'] >` (indicador adicional) | no medido | 1 (en `CarritoService`) | La validación de stock quedó centralizada; el controlador solo elige el mensaje. |
| Complejidad por conteo de `procesar_pago` | 8 | 7 (5 `if` + 1 `foreach` + 1) | Bajó 1 al mover el bucle de stock al servicio. Sigue siendo el método más complejo por la transacción y la escritura de pedidos. |
| Complejidad por conteo de `actualizar` | 11 | 8 (7 `if` + 1) | Bajó 3: dos `if` desaparecieron al extraer `guardarCantidad()` y el `||` de la validación de pertenencia se fue a `esItemDelUsuario()`. |
| Pruebas aprobadas y fallidas | OK: 12 pruebas, 26 aserciones, 0 fallos | OK: 22 pruebas, 45 aserciones, 0 fallos | Subió la cobertura con 5 pruebas unitarias de R1 y 5 de R2; las 6 originales de `CarritoTest` mantuvieron expectativas y resultado. Hay 2 deprecaciones de `DatabaseTestTrait`, no de las pruebas nuevas. |

**Regla de conteo:** líneas de método desde la firma hasta la llave de cierre inclusive; complejidad = cantidad de `if` (incluido el de `else if`), `foreach`, `&&`, `||`, `?:` y `??` dentro del rango, más 1. Desglose inicial: `procesar_pago` = 5 `if` + 2 `foreach` + 1 = 8; `actualizar` = 9 `if` + 1 `||` + 1 = 11. Desglose final: `procesar_pago` = 5 `if` + 1 `foreach` + 1 = 7; `actualizar` = 7 `if` + 1 = 8.

**Interpretación general:** ningún indicador de complejidad o duplicación subió. Las dos cifras que aumentan son el total de líneas del módulo (375 → 430, por la clase nueva que antes no existía) y el número de pruebas (12 → 22, por la red adicional). Dentro del controlador, el código bajó de 375 a 366 líneas; la nueva complejidad introducida está en métodos privados pequeños y en el servicio, que se prueban por separado.

---

## F27. Bitácora y evidencias Git

| Commit (hash) | Persona | Archivo o cambio | Justificación y prueba asociada |
| :--- | :--- | :--- | :--- |
| `0444810` | Persona 1 (Jean Pol Sullca Caballero) | `docs/calidad/evidencias/.gitkeep`, `docs/calidad/evidencias/phpunit-antes.txt`, `docs/calidad/Informe-Calidad.md` | Creación de línea base en rama `refactor/carrito`, estructura del informe, formato F22 y evidencia inicial de PHPUnit (6 tests, 6 assertions OK). |
| `7143513` | Persona 2 (Jhon Richard Daniel Guillen Hurtado) | `docs/calidad/Informe-Calidad.md`, `F23-inventario.md`, `F26-medicion.md`, `evidencias/medicion-manual.md` | Completar F23, incidencias y columna "Inicial" de F26 sobre el commit `75f596c`; conteos manuales y evidencia de PHPUnit sin cambios de código. |
| `b4f3d39` | Persona 3 (Frank Puma Ucharo) | `tests/feature/CarritoTest.php`, `docs/calidad/Informe-Calidad.md`, `F25-matriz.md`, `evidencias/phpunit-pruebas-carrito.txt` | Red de seguridad de 6 escenarios en verde sobre el código original; F25 columna "Antes" y actualización de la fila de pruebas de F26. |
| `980534c` | Persona 4 (Richard Triveños Llacta) | `app/Services/CarritoService.php`, `app/Controllers/CarritoController.php` | Paso 1 de R1: crear `CarritoService::obtenerItems()` y reemplazar la consulta en `obtener()`; `php -l` y `CarritoTest` en verde. |
| `aff4f4b` | Persona 4 (Richard Triveños Llacta) | `app/Controllers/CarritoController.php` | Paso 2 de R1: usar el servicio en `checkout()`; sin cambios en mensajes ni redirecciones. |
| `46ea5e4` | Persona 4 (Richard Triveños Llacta) | `app/Controllers/CarritoController.php` | Paso 3 de R1: usar el servicio en `procesar_pago()` y `cancelar_pago()`; controlador sin `join('tProductos'`. |
| `40b1768` | Persona 4 (Richard Triveños Llacta) | `app/Services/CarritoService.php`, `app/Controllers/CarritoController.php` | Paso 4 de R1: centralizar `calcularTotal()` conservando `$total = 0` entero; controlador sin `$total +=`. |
| `bb3b777` | Persona 4 (Richard Triveños Llacta) | `app/Services/CarritoService.php`, `app/Controllers/CarritoController.php` | Paso 5 de R1: centralizar `primerItemSinStock()`; cada método conserva su mensaje y se mantiene el orden de validaciones. |
| `d270502` | Persona 4 (Richard Triveños Llacta) | `tests/unit/CarritoServiceTest.php`, `docs/calidad/evidencias/phpunit-r1.txt` | 5 pruebas unitarias adicionales (7 aserciones) para total y validación de stock con arreglos; suite completa 17 pruebas / 33 aserciones / 0 fallos. |
| `51edb7c` | Persona 4 (Richard Triveños Llacta) | `docs/calidad/Informe-Calidad.md`, `F24-refactorizacion.md` | Completar F24 columna R1 (DT-01, DT-02 y DT-03 parcial) y F27 con los hashes de los cinco pasos y las pruebas unitarias. |
| `5ab9997` | Persona 5 (Renato Aldair Mamani Apaza) | `tests/feature/CarritoActualizarTest.php` | Regresión adicional de R2: 5 pruebas para `sumar`, `fijar`, ítem ajeno y acción desconocida; 12 aserciones en verde antes de tocar `actualizar()`. |
| `2fd477e` | Persona 5 (Renato Aldair Mamani Apaza) | `app/Controllers/CarritoController.php` | Paso 1 de R2: extraer `guardarCantidad()` y usarlo en `restar` y `fijar`; CP-06 y las 5 pruebas nuevas en verde. |
| `34eb4af` | Persona 5 (Renato Aldair Mamani Apaza) | `app/Controllers/CarritoController.php`, `tests/feature/CarritoActualizarTest.php` | Paso 2 de R2: extraer `esItemDelUsuario()` y reutilizarlo en `actualizar()` y `eliminar()`; se agregó la prueba de ítem de otro usuario. |
| `af4eb21` | Persona 5 (Renato Aldair Mamani Apaza) | `app/Controllers/CarritoController.php` | Paso 3 de R2: constantes `ROL_ADMINISTRADOR` y `ACCION_*` en `checkAuth()` y `actualizar()`; suite en verde. |
| `f319f92` | Persona 5 (Renato Aldair Mamani Apaza) | `app/Controllers/CarritoController.php` | Paso 4 de R2: constantes `ESTADO_*` en `checkout()`, `procesar_pago()` y `cancelar_pago()`; suite final 22 pruebas / 45 aserciones / 0 fallos. |
| `ebcdfa2` | Persona 5 (Renato Aldair Mamani Apaza) | `docs/calidad/Informe-Calidad.md`, `F24-refactorizacion.md`, `F25-matriz.md`, `F26-medicion.md`, `evidencias/phpunit-despues.txt`, `evidencias/metricas-final.txt`, `evidencias/diff-final.patch`, `evidencias/git-log-final.txt` | Cerrar F24 columna R2, F25 "Después", F26 "Final", F28 y F27; guardar la salida final de PHPUnit, la medición, el log hasta `f319f92` y el diff real `ac64f7d..f319f92 -- app tests`. |

---

## Incidencias

Defectos verificados al leer el código; no se corrigen dentro de R1 ni R2 porque cambiarían el comportamiento observable. Cada arreglo debe ir con su propia prueba y su propio commit.

| ID | Qué se observa | Dónde | Efecto posible | Recomendación |
| :--- | :--- | :--- | :--- | :--- |
| INC-01 | El stock se valida antes de iniciar la transacción | `procesar_pago`: validación en 254, `transStart()` en 264 | Dos pagos simultáneos podrían descontar el mismo stock | Validar dentro de la transacción, con prueba y commit propios |
| INC-02 | Una petición GET crea o modifica pedidos y detalles | `Routes.php:44` y `checkout` 198-228; sesión en 217 | Efectos secundarios al solo abrir la página | Separar la lectura de la creación del pedido |
| INC-03 | El nuevo stock se calcula con el valor leído antes | `procesar_pago`: lectura en 242, `stock - cantidad` en 290 y `update` en 291 | Lecturas desactualizadas | Descontar de forma atómica en la base de datos |
| INC-04 | Existe una ruta temporal que devuelve el id de sesión | `test()` 13-17 y `Routes.php:38` | Endpoint expuesto y posible fuga del id de sesión | Eliminarlo en commit separado, avisando del cambio de comportamiento |
| INC-05 | No se comprueba que el producto exista antes de usarlo | `actualizar`: `find` en 113 y uso de `$producto['stock']` en 117 y 130 | Error al acceder a un índice de `null` si el producto fue eliminado | Agregar la validación con su propia prueba |

---

## F28. Cierre y revisión docente

**Estado:** informe cerrado para revisión docente. Rama `refactor/carrito` sobre `https://github.com/023200626e-1/intrasev-motors`. Último commit de código: `f319f92`; los commits posteriores son documentación y están en F27.

El formato de F28 no aparece en las páginas revisadas de la guía de trabajo, así que se entrega como checklist de cierre; se puede ajustar si el docente proporciona una plantilla distinta.

| Entregable | Evidencia | Estado |
| :--- | :--- | :--- |
| F22, identificación y alcance | `Informe-Calidad.md`, `phpunit-antes.txt` | Completo, sin cambios de esta etapa |
| F23, inventario y priorización | `F23-inventario.md`, apartado F23 | Completo; DT-01 a DT-05 confirmados en F24 |
| F24, plan de refactorización | Apartado F24, `F24-refactorizacion.md` | Completo: columna R1 y columna R2 |
| F25, matriz de regresión | Apartado F25, `F25-matriz.md`, `phpunit-despues.txt` | Completo: columnas "Antes" y "Después" |
| F26, medición manual | Apartado F26, `F26-medicion.md`, `medicion-manual.md`, `metricas-final.txt` | Completo: columnas "Inicial" y "Final" con interpretación |
| F27, bitácora Git | Apartado F27, `git-log-final.txt`, `diff-final.patch` | Completo: hashes de las cinco personas y diff real `ac64f7d..f319f92 -- app tests` |
| Incidencias | Apartado "Incidencias" | Completo: INC-01 a INC-05 quedan fuera de R1/R2 |
| Código refactorizado | `CarritoController.php`, `CarritoService.php` | Completo: R1 y R2 en commits pequeños |
| Pruebas | `CarritoTest.php`, `CarritoServiceTest.php`, `CarritoActualizarTest.php` | Completas: 22 pruebas, 45 aserciones, 0 fallos |

**Verificación final:**

- `php -l` sin errores en controlador, servicio y pruebas; `git diff --check` sin avisos de whitespace.
- No se instalaron PHPStan ni PHPMD, no se cambiaron dependencias, versión de PHP ni esquema de base de datos.
- No se modificó el comportamiento observable: mismos mensajes, redirecciones, estados de pedido, stock descontado y diferencia entre `sumar` y `fijar`.
- Las dos deprecaciones de PHPUnit provienen de `DatabaseTestTrait`, no del código agregado por el equipo.
- Quedan fuera de alcance, con recomendación propia en incidencias: transacción y stock concurrente (INC-01 a INC-03), ruta temporal `carrito/test` (INC-04) y producto inexistente en `actualizar()` (INC-05).

**Sustentación:** cada integrante debe poder explicar F22 (Jean Pol), F23 (Jhon Richard), F25 (Frank), R1 (Richard) y R2 más F26/F28 (Renato), además de las incidencias y por qué no se corrigieron dentro de R1/R2.
