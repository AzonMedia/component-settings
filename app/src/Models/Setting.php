<?php

declare(strict_types=1);

namespace GuzabaPlatform\Settings\Models;

use GuzabaPlatform\Catalog\Base\Interfaces\ItemInterface;
use GuzabaPlatform\Platform\Application\BaseActiveRecord;

/**
 * Class Setting
 * @package GuzabaPlatform\Settings\Models
 *
 * @property string $setting_name
 * @property mixed $setting_value
 */
class Setting extends BaseActiveRecord
{

    protected const CONFIG_DEFAULTS = [
        'main_table'            => 'settings',
        'route'                 => '/admin/settings/setting',//to be used for editing and deleting

        'object_name_property'  => 'setting_name',//required by BaseActiveRecord::get_object_name_property()

    ];

    protected const CONFIG_RUNTIME = [];

    public static function create(string $setting_name, /* mixed */ $setting_value): self
    {
        $Setting = new static();
        $Setting->setting_name = $setting_name;
        $Setting->setting_value = $setting_value;
        $Setting->write();
        return $Setting;
    }
}