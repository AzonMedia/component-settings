<?php
declare(strict_types=1);

namespace GuzabaPlatform\Settings\Controllers;

use Guzaba2\Authorization\Role;
use Guzaba2\Http\Method;
use GuzabaPlatform\Platform\Application\BaseController;
use GuzabaPlatform\Platform\Authentication\Models\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Users
 * @package GuzabaPlatform\Users\Controllers
 *
 */
class Settings extends BaseController
{
    protected const CONFIG_DEFAULTS = [
        'routes' => [
            '/admin/settings/{page}/{limit}/{search_values}/{sort_by}/{sort}' => [
                Method::HTTP_GET => [self::class, 'main']
            ],
        ],
    ];

    protected const CONFIG_RUNTIME = [];

    public const LISTING_COLUMNS = [
        'setting_id',
        'setting_name',
        'setting_value',
    ];

    /**
     * @param int $page
     * @param int $limit
     * @param string $search_values
     * @param string $sort_by Sorting column
     * @param string $sort Sort direction
     * @return ResponseInterface
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\LogicException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     * @throws \Guzaba2\Kernel\Exceptions\ConfigurationException
     * @throws \ReflectionException
     */
    public function main(int $page, int $limit, string $search_values, string $sort_by, string $sort): ResponseInterface
    {
        $struct = [];

        if ($sort_by === 'none') {
            $sort_by = 'setting_name';
        }
        $sort_desc = false;
        if (strtoupper($sort) === 'DESC') {
            $sort_desc = true;
        }

        $offset = ($page - 1) * $limit;
        $search = json_decode(base64_decode(urldecode($search_values)));

        $struct['listing_columns'] = self::LISTING_COLUMNS;
        $struct['record_properties'] = \GuzabaPlatform\Settings\Controllers\Setting::RECORD_PROPERTIES;
        $struct['editable_record_properties'] = \GuzabaPlatform\Settings\Controllers\Setting::EDITABLE_RECORD_PROPERTIES;

        //$struct['data'] = Users::get_data_by((array) $search, $offset, $limit, $use_like = TRUE, $sort_by, (bool) $sort_desc, $total_found_rows);
        $struct['data'] = \GuzabaPlatform\Settings\Models\Setting::get_data_by((array) $search, $offset, $limit, true, $sort_by, $sort_desc, $total_found_rows);

        $struct['totalItems'] = $total_found_rows;
        if ($limit) {
            $struct['numPages'] = ceil($struct['totalItems'] / $limit);
        } else {
            $struct['numPages'] = 1;
        }

        return self::get_structured_ok_response($struct);
    }


}