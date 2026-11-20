<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class CarritoTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';

    /**
     * Datos mínimos compartidos: rol cliente, usuario y producto.
     *
     * @return array{id_usuario:int, id_producto:int}
     */
    private function crearContexto(int $stock = 5, string $nombre = 'Producto de prueba'): array
    {
        $this->db->table('tRoles')->insert([
            'id_rol'     => 2,
            'nombre_rol' => 'Cliente',
        ]);

        $this->db->table('tUsuarios')->insert([
            'id_usuario'         => 1,
            'id_rol'             => 2,
            'usuario'            => 'cliente_test',
            'nombre_completo'    => 'Cliente de prueba',
            'correo_electronico' => 'cliente_test@example.com',
            'contrasena_hash'    => 'hash',
            'fecha_creacion'     => date('Y-m-d H:i:s'),
        ]);

        $this->db->table('tProductos')->insert([
            'nombre' => $nombre,
            'precio' => 10.00,
            'stock'  => $stock,
        ]);

        return [
            'id_usuario'  => 1,
            'id_producto' => (int) $this->db->insertID(),
        ];
    }

    private function sesionCliente(): array
    {
        return ['id_usuario' => 1, 'id_rol' => 2];
    }

    private function jsonDe($response): array
    {
        return json_decode($response->getJSON(), true);
    }

    public function testObtenerSinSesionEsRechazado(): void
    {
        $response = $this->get('carrito/obtener');
        $json     = $this->jsonDe($response);

        $this->assertFalse($json['success']);
        $this->assertSame('Debes iniciar sesión para comprar.', $json['message']);
    }

    public function testAdministradorNoPuedeAgregar(): void
    {
        $response = $this->withSession(['id_usuario' => 1, 'id_rol' => 1])
            ->post('carrito/agregar', ['id_producto' => 999]);

        $json = $this->jsonDe($response);

        $this->assertFalse($json['success']);
        $this->assertSame('Los administradores no pueden realizar compras.', $json['message']);
        $this->assertSame(0, $this->db->table('tCarritoCompras')->countAllResults());
    }

    public function testAgregarSinStockEsRechazado(): void
    {
        $contexto = $this->crearContexto(0);

        $response = $this->withSession($this->sesionCliente())
            ->post('carrito/agregar', ['id_producto' => $contexto['id_producto']]);

        $json = $this->jsonDe($response);

        $this->assertFalse($json['success']);
        $this->assertSame('Producto sin stock disponible.', $json['message']);
        $this->assertSame(0, $this->db->table('tCarritoCompras')->countAllResults());
    }

    public function testProcesarPagoRechazaCantidadMayorAlStock(): void
    {
        $contexto = $this->crearContexto(2, 'Laptop Gamer');

        $this->db->table('tCarritoCompras')->insert([
            'id_usuario'     => $contexto['id_usuario'],
            'id_producto'    => $contexto['id_producto'],
            'cantidad'       => 5,
            'fecha_agregado' => date('Y-m-d H:i:s'),
        ]);

        $response = $this->withSession($this->sesionCliente())->post('carrito/procesar_pago');
        $json     = $this->jsonDe($response);

        $this->assertFalse($json['success']);
        $this->assertStringContainsString('Laptop Gamer', $json['message']);
        $this->assertStringContainsString('2 disp', $json['message']);

        $producto = $this->db->table('tProductos')
            ->where('id_producto', $contexto['id_producto'])->get()->getRowArray();
        $this->assertSame(2, (int) $producto['stock']);

        $this->assertSame(0, $this->db->table('tPedidos')->countAllResults());
    }

    public function testProcesarPagoConCantidadIgualAlStock(): void
    {
        $contexto = $this->crearContexto(2);

        $this->db->table('tCarritoCompras')->insert([
            'id_usuario'     => $contexto['id_usuario'],
            'id_producto'    => $contexto['id_producto'],
            'cantidad'       => 2,
            'fecha_agregado' => date('Y-m-d H:i:s'),
        ]);

        $response = $this->withSession($this->sesionCliente())->post('carrito/procesar_pago');
        $json     = $this->jsonDe($response);

        $this->assertTrue($json['success']);
        $this->assertArrayHasKey('id_pedido', $json);

        $producto = $this->db->table('tProductos')
            ->where('id_producto', $contexto['id_producto'])->get()->getRowArray();
        $this->assertSame(0, (int) $producto['stock']);

        $this->assertSame(0, $this->db->table('tCarritoCompras')->countAllResults());

        $pedido = $this->db->table('tPedidos')
            ->where('id_pedido', $json['id_pedido'])->get()->getRowArray();
        $this->assertSame('Pagado', $pedido['estado_pedido']);
    }

    public function testActualizarRestarEliminaItem(): void
    {
        $contexto = $this->crearContexto(5);

        $this->db->table('tCarritoCompras')->insert([
            'id_usuario'     => $contexto['id_usuario'],
            'id_producto'    => $contexto['id_producto'],
            'cantidad'       => 1,
            'fecha_agregado' => date('Y-m-d H:i:s'),
        ]);
        $idCarrito = $this->db->insertID();

        $response = $this->withSession($this->sesionCliente())
            ->post('carrito/actualizar', [
                'id_carrito' => $idCarrito,
                'accion'     => 'restar',
            ]);

        $json = $this->jsonDe($response);

        $this->assertTrue($json['success']);
        $this->assertSame(0, $this->db->table('tCarritoCompras')
            ->where('id_carrito', $idCarrito)->countAllResults());
    }
}
