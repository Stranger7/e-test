<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 12.12.2014
 * Time: 10:59
 */

namespace core;

class View
{
    /**
     * @var string
     */
    private $name = '';

    /**
     * Variable determines whether the result is returned as a string,
     * or will be displayed in the browser
     *
     * @var bool
     */
    private $as_string = false;

    /**
     * @param $name
     * @param bool $as_string
     */
    public function __construct($name, $as_string = false)
    {
        $this->name = $name;
        $this->as_string = $as_string;
        $this->prepareFilename();
        return $this;
    }

    /**
     * @param mixed $data
     * @return $this|string
     */
    public function load($data = [])
    {
        ob_start();

        extract($data);
        include $this->name;

        App::logger()->debug('View ' . $this->name . ' loaded');

        if ($this->as_string)
        {
            $buffer = ob_get_contents();
            @ob_end_clean();
            return $buffer;
        } else {
            ob_end_flush();
        }
        return $this;
    }

    /**
     * Make file name for view
     */
    private function prepareFilename()
    {
        $extension = ((pathinfo($this->name, PATHINFO_EXTENSION) === '') ? '.php' : '');
        $this->name = str_replace(
            ['\\', '/'],
            DIRECTORY_SEPARATOR,
            BASE_PATH . trim(App::VIEW_PATH, '\\/') . DIRECTORY_SEPARATOR . $this->name . $extension
        );
        if (!file_exists($this->name)) {
            throw new \RuntimeException("File of view '{$this->name}' not exist or not readable");
        }
    }

    /**
     * @return array
     */
    public static function defaultJS()
    {
        if ($default_js = App::config()->get(Config::HTML_DEFAULTS_JS_SECTION)) {
            return array_values($default_js);
        }
        return [];
    }

    /**
     * @return array
     */
    public static function defaultCSS()
    {
        if ($default_css = App::config()->get(Config::HTML_DEFAULTS_CSS_SECTION)) {
            return array_values($default_css);
        }
        return [];
    }
}