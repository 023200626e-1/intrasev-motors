# F25. Matriz de comportamiento y regresión — columna "Antes"

Copia independiente del apartado F25 de `Informe-Calidad.md`. Salida real: `docs/calidad/evidencias/phpunit-pruebas-carrito.txt`.

- Entorno: PHP 8.2.12, PHPUnit 11.5.56, MySQL aislado `ds_g1_testing` (prefijo `db_`) configurado solo en `.env` local; nunca se usó la base de producción `ds_g1`.
- Migraciones ejecutadas con `DatabaseTestTrait` (`$migrate = true`, `$refresh = true`, `$namespace = 'App'`). El esquema original usa nombres de llaves foráneas no soportados por SQLite, por eso se aplicó la alternativa de la guía.
- CSRF desactivado globalmente, sin token en las peticiones.
- Resultado: CarritoTest 6 pruebas / 20 aserciones / 0 fallos; suite completa 12 pruebas / 26 aserciones / 0 fallos. Hay 1 deprecación de PHPUnit originada en `DatabaseTestTrait::resetMigrationSeedCount()`, no en estas pruebas.

| CP | Escenario | Método de prueba | Antes (código original) | Después |
| :--- | :--- | :--- | :--- | :--- |
| CP-01 | `GET carrito/obtener` sin sesión | `testObtenerSinSesionEsRechazado` | Verde: `success:false` y "Debes iniciar sesión para comprar." | (lo mide Persona 5) |
| CP-02 | `POST carrito/agregar` con `id_rol = 1` | `testAdministradorNoPuedeAgregar` | Verde: `success:false` y "Los administradores no pueden realizar compras."; carrito sin filas | (lo mide Persona 5) |
| CP-03 | Producto con `stock = 0` en `POST carrito/agregar` | `testAgregarSinStockEsRechazado` | Verde: `success:false` y "Producto sin stock disponible."; no se crea fila en `tCarritoCompras` | (lo mide Persona 5) |
| CP-04 | Cantidad mayor al stock en `POST carrito/procesar_pago` | `testProcesarPagoRechazaCantidadMayorAlStock` | Verde: `success:false`, mensaje con "Laptop Gamer" y "2 disp"; stock sigue en 2 y 0 pedidos | (lo mide Persona 5) |
| CP-05 | Cantidad igual al stock en `POST carrito/procesar_pago` | `testProcesarPagoConCantidadIgualAlStock` | Verde: `success:true` con `id_pedido`; stock 0, carrito vacío y pedido `Pagado` | (lo mide Persona 5) |
| CP-06 | `POST carrito/actualizar` con `accion=restar` sobre cantidad 1 | `testActualizarRestarEliminaItem` | Verde: `success:true` y el ítem se elimina | (lo mide Persona 5) |

CP-04 y CP-05 vigilan lo que toca R1; CP-06 vigila lo que toca R2. Las expectativas se mantienen sin cambios para la medición "Después".
