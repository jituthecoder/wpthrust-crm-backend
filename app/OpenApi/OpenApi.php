<?php

namespace App\OpenApi;

/**
 * @OA\Info(
 *      title="WPThrust Lead CRM API",
 *      version="1.0.0",
 *      description="API documentation for WPThrust Lead CRM",
 *      @OA\Contact(
 *          email="admin@wpthrust.com",
 *          name="WPThrust"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Local Development Server"
 * )
 *
 * @OA\SecurityScheme(
 *      securityScheme="bearerAuth",
 *      type="http",
 *      scheme="bearer",
 *      bearerFormat="JWT"
 * )
 */
class OpenApi
{
}