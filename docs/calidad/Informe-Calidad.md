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

*(Completar por Persona 2: Jhon Richard Daniel Guillen Hurtado)*

---

## F24. Plan de refactorización

*(Completar por Personas 4 y 5: Richard Triveños Llacta y Renato Aldair Mamani Apaza)*

---

## F25. Matriz de comportamiento y regresión

*(Completar por Persona 3 y Persona 5: Frank Puma Ucharo y Renato Aldair Mamani Apaza)*

---

## F26. Protocolo de análisis estático y comparación

*(Completar por Persona 2 y Persona 5 — Nota: No se instalan herramientas externas como PHPStan o PHPMD)*

---

## F27. Bitácora y evidencias Git

| Commit (hash) | Persona | Archivo o cambio | Justificación y prueba asociada |
| :--- | :--- | :--- | :--- |
| `0444810` | Persona 1 (Jean Pol Sullca Caballero) | `docs/calidad/evidencias/.gitkeep`, `docs/calidad/evidencias/phpunit-antes.txt`, `docs/calidad/Informe-Calidad.md` | Creación de línea base en rama `refactor/carrito`, estructura del informe, formato F22 y evidencia inicial de PHPUnit (6 tests, 6 assertions OK). |

---

## Incidencias

*(Completar por Persona 2: Jhon Richard Daniel Guillen Hurtado)*

---

## F28. Cierre y revisión docente

*(Completar por Persona 5: Renato Aldair Mamani Apaza)*
