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

**Por qué se eligieron R1 y R2:** R1 (Extract Class) cubre DT-01, DT-02 y DT-03 por su prioridad alta y su impacto en stock y pago; aunque su esfuerzo es mayor, extraer `CarritoService` es viable porque las consultas y los bucles ya son independientes. R2 (Extract Method y constantes) cubre DT-04 y DT-05 por ser locales a `actualizar()` y a los literales, de bajo riesgo y ejecución rápida. Queda pendiente que las Personas 4 y 5 confirmen en sus F24 que sus cambios cubren exactamente esos IDs.

---

## F24. Plan de refactorización

Copia independiente de la columna R1 en `docs/calidad/F24-refactorizacion.md`.

| Campo | R1 (Persona 4: Richard Triveños Llacta) | R2 (Persona 5: Renato Aldair Mamani Apaza) |
| :--- | :--- | :--- |
| Hallazgo que resuelve | DT-01 y DT-02 completos; DT-03 solo en su parte de acceso a datos y reglas de negocio. La apertura/cierre de transacciones y la escritura de pedidos siguen en el controlador, así que DT-03 queda **resuelto en parte** y `INC-01` sigue pendiente. | *(lo completa Persona 5)* |
| Tipo de técnica | `Extract Class` | *(lo completa Persona 5)* |
| Archivo y método antes | `CarritoController::obtener`, `checkout`, `procesar_pago`, `cancelar_pago` | *(lo completa Persona 5)* |
| Estructura deseada | `app/Services/CarritoService.php` con `obtenerItems($idUsuario)`, `calcularTotal($items)` y `primerItemSinStock($items)`. El controlador solo orquesta la petición y la respuesta. | *(lo completa Persona 5)* |
| Comportamiento que NO cambia | Respuestas JSON, mensajes, redirecciones, estado del pedido, stock descontado, las columnas del `select` de `obtener()`, el valor inicial `$total = 0` sin tipo `float` y el orden de validación (carrito vacío → stock). El controlador conserva sus mensajes propios en `checkout` y `procesar_pago`. | *(lo completa Persona 5)* |
| Pruebas que lo protegen | CP-01 a CP-06 de `tests/feature/CarritoTest.php`: `testObtenerSinSesionEsRechazado`, `testAdministradorNoPuedeAgregar`, `testAgregarSinStockEsRechazado`, `testProcesarPagoRechazaCantidadMayorAlStock`, `testProcesarPagoConCantidadIgualAlStock`, `testActualizarRestarEliminaItem`. Adicionalmente, 5 pruebas unitarias / 7 aserciones en `tests/unit/CarritoServiceTest.php`, que no reemplazan a la regresión. | *(lo completa Persona 5)* |
| Riesgo o efecto colateral | Cambio de tipo del total, columnas del `select` y orden de validaciones. Se mitigaron conservando la aritmética y el `0` entero original, la lista de columnas de `obtener()` y la precedencia de las validaciones. No se movió la transacción al servicio por ser el cambio más riesgoso. | *(lo completa Persona 5)* |
| Commit asociado | `980534c`, `aff4f4b`, `46ea5e4`, `40b1768`, `bb3b777` (cinco pasos de R1) y `d270502` (pruebas unitarias y evidencia). | *(lo completa Persona 5)* |

R1 terminó en verde: `CarritoTest` 6 pruebas / 20 aserciones, suite completa 17 pruebas / 33 aserciones, 0 fallos. La salida quedó en `docs/calidad/evidencias/phpunit-r1.txt` (junto a la suite completa). Después de R1, el controlador tiene 0 copias de `join('tProductos'`, 0 de `$total +=` y 0 comparaciones `['cantidad'] >`; esas tres responsabilidades viven ahora en `CarritoService`.

---

## F25. Matriz de comportamiento y regresión

Seis escenarios escritos contra el comportamiento actual, antes de cualquier refactorización. Copia independiente en `docs/calidad/F25-matriz.md`. Salida real en `docs/calidad/evidencias/phpunit-pruebas-carrito.txt` (CarritoTest: 6 pruebas, 20 aserciones, 0 fallos; suite completa: 12 pruebas, 26 aserciones, 0 fallos).

- Entorno de ejecución: PHP 8.2.12, PHPUnit 11.5.56, base MySQL aislada `ds_g1_testing` con prefijo `db_` configurada solo en `.env` local (nunca se usó `ds_g1`). Las migraciones corren con `DatabaseTestTrait` (`$migrate = true`, `$refresh = true`, `$namespace = 'App'`). CSRF está desactivado, así que no se envió token.
- La migración original usa nombres de llaves foráneas que SQLite no acepta, por eso se siguió la alternativa de la guía: base MySQL de pruebas separada. No se cambió el esquema ni el código de producción.
- Hay 1 deprecación de PHPUnit proveniente del trait `DatabaseTestTrait` (`resetMigrationSeedCount()` con metadata en doc-comment), no de las pruebas nuevas.

| CP | Escenario | Método de prueba | Antes (código original) | Después |
| :--- | :--- | :--- | :--- | :--- |
| CP-01 | `GET carrito/obtener` sin sesión | `testObtenerSinSesionEsRechazado` | Verde: JSON con `success:false` y mensaje exacto "Debes iniciar sesión para comprar." | (lo mide Persona 5) |
| CP-02 | `POST carrito/agregar` con `id_rol = 1` | `testAdministradorNoPuedeAgregar` | Verde: JSON con `success:false` y "Los administradores no pueden realizar compras."; carrito sin filas | (lo mide Persona 5) |
| CP-03 | Cliente con producto de `stock = 0` en `POST carrito/agregar` | `testAgregarSinStockEsRechazado` | Verde: `success:false` y "Producto sin stock disponible."; no se crea fila en `tCarritoCompras` | (lo mide Persona 5) |
| CP-04 | Cantidad mayor al stock en `POST carrito/procesar_pago` | `testProcesarPagoRechazaCantidadMayorAlStock` | Verde: `success:false`, mensaje contiene "Laptop Gamer" y "2 disp"; el stock sigue en 2 y no hay pedidos | (lo mide Persona 5) |
| CP-05 | Cantidad igual al stock en `POST carrito/procesar_pago` | `testProcesarPagoConCantidadIgualAlStock` | Verde: `success:true` con `id_pedido`; stock queda en 0, carrito vacío y pedido en `Pagado` | (lo mide Persona 5) |
| CP-06 | `POST carrito/actualizar` con `accion=restar` sobre cantidad 1 | `testActualizarRestarEliminaItem` | Verde: `success:true` y el ítem se elimina de `tCarritoCompras` | (lo mide Persona 5) |

CP-04 y CP-05 protegen lo que toca R1 (stock, total, estado del pedido); CP-06 protege lo que toca R2. Las expectativas no se cambiarán al medir "Después".

---

## F26. Protocolo de análisis estático y comparación

Indicadores manuales repetibles, sin PHPStan ni PHPMD, por indicación del docente. Procedimiento completo y comandos exactos en `docs/calidad/evidencias/medicion-manual.md`; copia independiente en `docs/calidad/F26-medicion.md`.

- Entorno declarado en F22: PHP 8.3.33, CodeIgniter 4.5.1, PHPUnit 11.5.56. Los conteos de código se ejecutaron sobre el mismo archivo (SHA-256 `1733064a99d1a416bc1f7ac6f91a7e25758b7050a013acaf2b3a242b812bfbb6`) en un puesto con PHP 8.2.12; las cifras son de lectura de código y no dependen de la versión. La Persona 5 debe repetir la medición final en el mismo entorno de la línea base y ajustar esta nota si es necesario.
- Archivo medido: `app/Controllers/CarritoController.php` (375 líneas). Alcance final: el mismo archivo más `app/Services/CarritoService.php` para copias de consulta y bucles de total.
- Ancla Git: commit base F22 `ac64f7d`; commit sobre el que se registró la línea base del turno 2: `75f596c` (HEAD de `refactor/carrito` al iniciar la medición).
- Evidencia de pruebas: `docs/calidad/evidencias/phpunit-antes.txt` (Persona 1, sin modificar) y `docs/calidad/evidencias/phpunit-pruebas-carrito.txt` (CarritoTest y suite completa, Persona 3).

| Indicador (mismo alcance) | Inicial | Final | Interpretación |
| :--- | :--- | :--- | :--- |
| Líneas del controlador | 375 | (lo mide Persona 5) | Línea base del tamaño del controlador. Debe bajar al extraer el servicio; si el total del módulo sube, explicarlo. |
| Líneas de `procesar_pago` | 73 (233-305) | (lo mide Persona 5) | Método más largo del controlador; R1 debe reducirlo. |
| Líneas de `actualizar` | 44 (98-141) | (lo mide Persona 5) | Método que ataca R2; la reducción se espera moderada. |
| Copias de la consulta de ítems | 4 (`join('tProductos'`) | (lo mide Persona 5) | Confirma DT-01. Debe quedar en 1 dentro del servicio. |
| Bucles que suman el total | 4 (`$total +=`) | (lo mide Persona 5) | Confirma DT-02. Debe quedar en 1 dentro del servicio. |
| Complejidad por conteo de `procesar_pago` | 8 | (lo mide Persona 5) | Confirma DT-03; se espera que baje al mover validación y cálculo. |
| Complejidad por conteo de `actualizar` | 11 | (lo mide Persona 5) | Confirma DT-04; R2 debe bajarla al extraer la guardada de cantidad. |
| Pruebas aprobadas y fallidas | OK: 12 pruebas, 26 aserciones, 0 fallos (6 originales + 6 de `CarritoTest.php`) | (lo mide Persona 5) | Condición inicial ya con la red de seguridad en verde, medida sobre el código sin refactorizar. Persona 5 debe correr las mismas pruebas sin cambiar expectativas. |

**Regla de conteo:** líneas de método desde la firma hasta la llave de cierre inclusive; complejidad = cantidad de `if` (incluido el de `else if`), `foreach`, `&&`, `||`, `?:` y `??` dentro del rango, más 1. Desglose: `procesar_pago` = 5 `if` + 2 `foreach` + 1 = 8; `actualizar` = 9 `if` + 1 `||` + 1 = 11.

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
| `(registrar con git log -1 --format=%h)` | Persona 4 (Richard Triveños Llacta) | `docs/calidad/Informe-Calidad.md`, `F24-refactorizacion.md` | Completar F24 columna R1 (DT-01, DT-02 y DT-03 parcial) y F27 con los hashes de los cinco pasos y las pruebas unitarias. |

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

*(Completar por Persona 5: Renato Aldair Mamani Apaza)*
