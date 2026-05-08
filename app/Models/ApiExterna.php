<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiExterna extends Model
{
    protected $table = 'apis_externas';

    protected $fillable = [
        'nombre', 'descripcion', 'tipo', 'proveedor', 'url_base', 'url_sandbox',
        'version', 'auth_tipo', 'api_key', 'api_secret', 'access_token',
        'refresh_token', 'token_expires_at', 'headers_adicionales', 'configuracion',
        'rate_limit_requests', 'rate_limit_periodo', 'requests_hoy', 'ultimo_request',
        'ambiente', 'activo', 'ultimo_test', 'ultimo_test_exitoso'
    ];

    protected $casts = [
        'headers_adicionales' => 'array',
        'configuracion' => 'array',
        'activo' => 'boolean',
        'ultimo_test_exitoso' => 'boolean',
        'token_expires_at' => 'datetime',
        'ultimo_request' => 'datetime',
        'ultimo_test' => 'datetime',
    ];

    protected $hidden = ['api_key', 'api_secret', 'access_token', 'refresh_token'];
}