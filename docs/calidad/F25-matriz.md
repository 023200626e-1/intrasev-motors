# F25. Matriz de comportamiento y regresión

Copia independiente del apartado F25 de `Informe-Calidad.md`. Salida real: `docs/calidad/evidencias/phpunit-pruebas-carrito.txt` (Antes) y `docs/calidad/evidencias/phpunit-despues.txt` (Después).

- Entorno: PHP 8.2.12, PHPUnit 11.5.56, MySQL aislado `ds_g1_testing` (prefijo `db_`) configurado solo en `.env` local; nunca se usó la base de producción `ds_g1`.
- Migraciones ejecutadas con `DatabaseTestTrait` (`$migrate = true`, `$refresh = true`, `$namespace = 'App'`). El esquema original usa nombres de llaves foráneas no soportados por SQLite, por eso se aplicó la alternativa de la guía.
- CSRF desactivado globalmente, sin token en las peticiones.
- Resultado final: suite completa 22 pruebas / 45 aserciones / 0 fallos. `CarritoTest` 6/20, `CarritoServiceTest` 5/7, `CarritoActualizarTest` 5/12, `PrestamoServiceMantenimientoTest` 6/6. Hay 2 deprecaciones de PHPUnit originadas en `DatabaseTestTrait::resetMigrationSeedCount()`, no en las pruebas nuevas.

| CP | Escenario | Método de prueba | Antes (código original) | Después (código final) |
| :--- | :--- | :--- | :--- | :--- |
| CP-01 | `GET carrito/obtener` sin sesión | `testObtenerSinSesionEsRechazado` | Verde: `success:false` y "Debes iniciar sesión para comprar." | Verde: mismo JSON y mismo mensaje exacto. |
| CP-02 | `POST carrito/agregar` con `id_rol = 1` | `testAdministradorNoPuedeAgregar` | Verde: `success:false` y "Los administradores no pueden realizar compras."; carrito sin filas | Verde: mismo mensaje; `ROL_ADMINISTRADOR` conserva el valor 1. |
| CP-03 | Producto con `stock = 0` en `POST carrito/agregar` | `testAgregarSinStockEsRechazado` | Verde: `success:false` y "Producto sin stock disponible."; no se crea fila en `tCarritoCompras` | Verde: mismo mensaje y carrito sin filas. |
| CP-04 | Cantidad mayor al stock en `POST carrito/procesar_pago` | `testProcesarPagoRechazaCantidadMayorAlStock` | Verde: `success:false`, mensaje con "Laptop Gamer" y "2 disp"; stock sigue en 2 y 0 pedidos | Verde: mismo mensaje, stock sigue en 2 y 0 pedidos. |
| CP-05 | Cantidad igual al stock en `POST carrito/procesar_pago` | `testProcesarPagoConCantidadIgualAlStock` | Verde: `success:true` con `id_pedido`; stock 0, carrito vacío y pedido `Pagado` | Verde: mismo resultado; `Pagado` ahora viene de `ESTADO_PAGADO`. |
| CP-06 | `POST carrito/actualizar` con `accion=restar` sobre cantidad 1 | `testActualizarRestarEliminaItem` | Verde: `success:true` y el ítem se elimina | Verde: el ítem se elimina mediante `guardarCantidad()`; `success:true`. |

CP-04 y CP-05 vigilan lo que tocó R1; CP-06 vigila lo que tocó R2. Las expectativas no cambiaron. `CarritoActualizarTest.php` agrega cinco escenarios de R2: `sumar` sobre el límite, `fijar` ajustando al stock, `fijar` en 0, ítem de otro usuario y acción desconocida.
