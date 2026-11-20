<?php

namespace App\Services;

use Config\Database;

/**
 * Reglas compartidas del carrito: consulta de items, total y validación de stock.
 */
class CarritoService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Devuelve los items del carrito de un usuario unidos con el producto.
     * La lista de columnas es la del método obtener() original, que contiene
     * todas las columnas usadas por los demás métodos del controlador.
     */
    public function obtenerItems($idUsuario): array
    {
        return $this->db->table('tCarritoCompras')
            ->select('tCarritoCompras.id_carrito, tCarritoCompras.cantidad, tProductos.id_producto, tProductos.nombre, tProductos.precio, tProductos.imagen, tProductos.stock')
            ->join('tProductos', 'tProductos.id_producto = tCarritoCompras.id_producto')
            ->where('tCarritoCompras.id_usuario', $idUsuario)
            ->get()
            ->getResultArray();
    }

    /**
     * Suma precio * cantidad. Se conserva el valor inicial entero 0 del
     * controlador original para no cambiar el tipo del total en el JSON.
     */
    public function calcularTotal($items)
    {
        $total = 0;

        foreach ($items as $item) {
            $total += $item['precio'] * $item['cantidad'];
        }

        return $total;
    }

    /**
     * Devuelve el primer item cuya cantidad supera el stock, o null.
     * El controlador conserva sus propios mensajes porque checkout y
     * procesar_pago informan el problema de formas distintas.
     */
    public function primerItemSinStock($items)
    {
        foreach ($items as $item) {
            if ($item['cantidad'] > $item['stock']) {
                return $item;
            }
        }

        return null;
    }
}
