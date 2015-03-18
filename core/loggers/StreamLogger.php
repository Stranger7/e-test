<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 26.09.2014
 * Time: 20:35
 */

/**
 * Usage:
 *   $logger->info('message 1');
 *   $logger->debug(
 *     'message 2',
 *     [
 *       'key1' => 'value1',
 *       'key2' => 'value2'
 *     ]
 *   );
 */

namespace core\loggers;

use core\App;
use core\Config;
use core\Utils;
use core\generic\Logger;

/**
* Class StreamLogger
* @package core\loggers
*/
class StreamLogger extends Logger
{
    const DEFAULT_LOG_FILENAME = 'logs/app.log';

    /**
     * @var string
     */
    protected $filename;

    public function __construct()
    {
        $this->filename = BASE_PATH . self::DEFAULT_LOG_FILENAME;
        parent::__construct();

        // Get from INI
        $this->filename = ($item = App::config()->get(Config::LOGGER_SECTION, 'filename'))
            ? $item
            : $this->filename;
    }

    public function start()
    {
        parent::start();
        if (!file_exists($this->filename))
        {
            // try create file
            $fp = @fopen($this->filename, 'w');
            @fclose($fp);
            if (!file_exists($this->filename)) {
                App::failure(App::LOGGER_INVALID_FILENAME, "can't create file {$this->filename}");
            }
        }
    }

    /**
     * Specifies log file name
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Write $message to log file
     * @param string $message
     */
    protected function write($message)
    {
        $fp = $this->openFile($this->filename);
        flock($fp, LOCK_EX);
        fputs($fp, $message . PHP_EOL);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    /**
     * Create formatted string for writing to log
     * @param int $level
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function formatMessage($level, $message, $context = [])
    {
        return (
            $this->getTimestamp($this->date_format . ' ' . $this->time_format) . ' ' .
            $this->withSuffix($this->getIPAddress()) .
            $this->getLevelName($level) . ' => ' .
            $message .
            (!empty($context) ? PHP_EOL . $this->indent(Utils::arrayToString($context, PHP_EOL)) : '')
        );
    }

    /**
     * Open log file
     * @param string $filename
     * @return resource
     */
    protected function openFile($filename)
    {
        $real_path_of_file = realpath($filename);
        if (file_exists($filename) && !is_writable($filename))
        {
            throw new \RuntimeException("log file $filename"
                . " (real path: $real_path_of_file) could not be written to."
                . " Check that appropriate permissions have been set.");
        }
        $fp = @fopen($this->filename, 'a');
        if (!$fp)
        {
            throw new \RuntimeException("Fatal: can't create log file $filename"
                . " (real path: $real_path_of_file)");
        }
        return $fp;
    }
}
