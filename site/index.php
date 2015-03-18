<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 29.09.2014
 * Time: 21:35
 */
$app_name = '<<< Input app name here >>>';
$app_mode = 'development';

require_once '../app/bootstrap.php';
\core\App::init($app_name, $app_mode);
\core\App::run();
