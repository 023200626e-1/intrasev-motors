<?php

namespace Tests\Unit;

use App\Services\CarritoService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pruebas unitarias adicionales: no reemplazan a las de regresión.
 *
 * @internal
 */
final class CarritoServiceTest extends CIUnitTestCase
{
    private CarritoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CarritoService();
    }

    public function testTotalDeCarritoVacioEsCeroEntero(): void
    {
        $this->assertSame(0, $this->service->calcularTotal([]));
    }

    public function testTotalSumaPrecioPorCantidad(): void
    {
        $items = [
            ['precio' => 10, 'cantidad' => 2],
            ['precio' => 3,  'cantidad' => 4],
        ];

        $this->assertSame(32, $this->service->calcularTotal($items));
    }

    public function testTotalConservaAritmeticaConPreciosDecimales(): void
    {
        $items = [
            ['precio' => 10.5, 'cantidad' => 2],
        ];

        $this->assertEquals(21, $this->service->calcularTotal($items));
    }

    public function testNoHayItemSinStockCuandoTodoEstaDentroDelLimite(): void
    {
        $items = [
            ['nombre' => 'A', 'cantidad' => 1, 'stock' => 3],
            ['nombre' => 'B', 'cantidad' => 3, 'stock' => 3],
        ];

        $this->assertNull($this->service->primerItemSinStock($items));
    }

    public function testDevuelvePrimerItemQueSuperaElStock(): void
    {
        $items = [
            ['nombre' => 'A', 'cantidad' => 1, 'stock' => 3],
            ['nombre' => 'B', 'cantidad' => 5, 'stock' => 2],
            ['nombre' => 'C', 'cantidad' => 9, 'stock' => 1],
        ];

        $item = $this->service->primerItemSinStock($items);

        $this->assertNotNull($item);
        $this->assertSame('B', $item['nombre']);
        $this->assertSame(2, $item['stock']);
    }
}
