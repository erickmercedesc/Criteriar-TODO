<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'SecondBrain API',
    description: 'Documentación de la API REST para SecondBrain. Manejo de TODOs y Criterios.'
)]
#[OA\SecurityScheme(
    securityScheme: 'apiAuth',
    type: 'http',
    scheme: 'bearer',
    description: 'Inserta tu API Key generada desde tu perfil.'
)]
abstract class Controller
{
    //
}
