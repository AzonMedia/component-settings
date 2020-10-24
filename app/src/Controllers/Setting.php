<?php

declare(strict_types=1);

namespace GuzabaPlatform\Settings\Controllers;

use Guzaba2\Http\Method;
use GuzabaPlatform\Platform\Application\BaseController;
use Psr\Http\Message\ResponseInterface;

class Setting extends BaseController
{

    protected const CONFIG_DEFAULTS = [
        'routes' => [
            '/admin/settings/dummy-route' => [
                Method::HTTP_GET => [self::class, 'main']
            ],
        ],
    ];

    protected const CONFIG_RUNTIME = [];

    public const RECORD_PROPERTIES = [
        'setting_id',
        'setting_name',
        'setting_value',
        'meta_object_uuid',
    ];

    /**
     * The editable properties form the UI. Must be a subset of @see self::RECORD_PROPERTIES
     */
    public const EDITABLE_RECORD_PROPERTIES = [
        'setting_name',
        'setting_value',
    ];

    //no need to implement any actions - ActiveRecordDefaultController will be used instead

    public function main(): ResponseInterface
    {

    }
}