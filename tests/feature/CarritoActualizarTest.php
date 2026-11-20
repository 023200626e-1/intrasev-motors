<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Regresión adicional para R2: protege las cuatro rutas de actualizar().
 *
 * @internal
 */
final class CarritoActualizarTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';

    /**
     * @return array{id_usuario:int, id_producto:int}
     */
    private function crearContexto(int $stock): array
    {
        $this->db->table('tRoles')->insert([
            'id_rol'     => 2,
            'nombre_rol' => 'Cliente',
        ]);

        $this->db->table('tUsuarios')->insert([
            'id_usuario'         => 1,
            'id_rol'             => 2,
            'usuario'            => 'cliente_actualizar',
            'nombre_completo'    => 'Cliente de prueba',
            'correo_electronico' => 'cliente_actualizar@example.com',
            'contrasena_hash'    => 'hash',
            'fecha_creacion'     => date('Y-m-d H:i:s'),
        ]);

        $this->db->table('tProductos')->insert([
            'nombre' => 'Producto de actualizacion',
            'precio' => 10.00,
            'stock'  => $stock,
        ]);

        return [
            'id_usuario'  => 1,
            'id_producto' => (int) $this->db->insertID(),
        ];
    }

    private function insertarItem(array $contexto, int $cantidad): int
    {
        $this->db->table('tCarritoCompras')->insert([
            'id_usuario'     => $contexto['id_usuario'],
            'id_producto'    => $contexto['id_producto'],
            'cantidad'       => $cantidad,
            'fecha_agregado' => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insertID();
    }

    private function cantidadDe(int $idCarrito): int
    {
        $item = $this->db->table('tCarritoCompras')
            ->where('id_carrito', $idCarrito)->get()->getRowArray();

        return (int) $item['cantidad'];
    }

    private function sesionCliente(): array
    {
        return ['id_usuario' => 1, 'id_rol' => 2];
    }

    public function testSumarRechazaAlSuperarStock(): void
    {
        $contexto  = $this->crearContexto(2);
        $idCarrito = $this->insertarItem($contexto, 2);

        $response = $this->withSession($this->sesionCliente())
            ->post('carrito/actualizar', [
                'id_carrito' => $idCarrito,
                'accion'     => 'sumar',
            ]);
        $json = json_decode($response->getJSON(), true);

        $this->assertFalse($json['success']);
        $this->assertSame('Solo tenemos 2 unidades disponibles de este producto.', $json['message']);
        $this->assertSame(2, $this->cantidadDe($idCarrito));
    }

    public function testFijarAjustaLaCantidadAlStock(): void
    {
        $contexto  = $this->crearContexto(2);
        $idCarrito = $this->insertarItem($contexto, 1);

        $response = $this->withSession($this->sesionCliente())
            ->post('carrito/actualizar', [
                'id_carrito' => $idCarrito,
                'accion'     => 'fijar',
                'cantidad'   => 5,
            ]);
        $json = json_decode($response->getJSON(), true);

        $this->assertTrue($json['success']);
        $this->assertSame(2, $this->cantidadDe($idCarrito));
    }

    public function testFijarCeroEliminaElItem(): void
    {
        $contexto  = $this->crearContexto(5);
        $idCarrito = $this->insertarItem($contexto, 1);

        $response = $this->withSession($this->sesionCliente())
            ->post('carrito/actualizar', [
                'id_carrito' => $idCarrito,
                'accion'     => 'fijar',
                'cantidad'   => 0,
            ]);
        $json = json_decode($response->getJSON(), true);

        $this->assertTrue($json['success']);
        $this->assertSame(0, $this->db->table('tCarritoCompras')
            ->where('id_carrito', $idCarrito)->countAllResults());
    }

    public function testItemDeOtroUsuarioEsRechazado(): void
    {
        $contexto = $this->crearContexto(5);

        $this->db->table('tUsuarios')->insert([
            'id_usuario'         => 2,
            'id_rol'             => 2,
            'usuario'            => 'otro_cliente',
            'nombre_completo'    => 'Otro cliente',
            'correo_electronico' => 'otro_cliente@example.com',
            'contrasena_hash'    => 'hash',
            'fecha_creacion'     => date('Y-m-d H:i:s'),
        ]);

        $idCarrito = $this->insertarItem([
            'id_usuario'  => 2,
            'id_producto' => $contexto['id_producto'],
        ], 1);

        $response = $this->withSession($this->sesionCliente())
            ->post('carrito/actualizar', [
                'id_carrito' => $idCarrito,
                'accion'     => 'sumar',
            ]);
        $json = json_decode($response->getJSON(), true);

        $this->assertFalse($json['success']);
        $this->assertSame('Item no válido.', $json['message']);
        $this->assertSame(1, $this->cantidadDe($idCarrito));
    }

    public function testAccionDesconocidaNoCambiaElItem(): void
    {
        $contexto  = $this->crearContexto(5);
        $idCarrito = $this->insertarItem($contexto, 1);

        $response = $this->withSession($this->sesionCliente())
            ->post('carrito/actualizar', [
                'id_carrito' => $idCarrito,
                'accion'     => 'otra',
            ]);
        $json = json_decode($response->getJSON(), true);

        $this->assertTrue($json['success']);
        $this->assertSame(1, $this->cantidadDe($idCarrito));
    }
}
