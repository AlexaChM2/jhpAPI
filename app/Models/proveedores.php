<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedores extends Model
{
    /**
     * Tabla del modelo.
     */
    protected $table = 'proveedores';

    /**
     * Clave primaria.
     */
    protected $primaryKey = 'id_proveedor';

        public $timestamps = false;

   
    protected $fillable = [
        'prov_nombre',
        'prov_contacto',
        'prov_telefono',
        'prov_email',
        'prov_direccion',
    ];
}
