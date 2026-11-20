# F24. Plan de refactorización — columna R1

Copia independiente del apartado F24 de `Informe-Calidad.md`. La columna R2 la completa la Persona 5.

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

Evidencia: `docs/calidad/evidencias/phpunit-r1.txt`. Resultado: 17 pruebas, 33 aserciones, 0 fallos (1 deprecación de `DatabaseTestTrait`, no de las pruebas nuevas).
