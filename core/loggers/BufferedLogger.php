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
 * Time: 20:36
 */

/**
 * Usage:
 *   $logger = BufferedLogger::getInstance();
 *   $logger->setFilename('/path/file.log');
 *   $logger->setHeader('-- header1 --');
 *   $logger->info('message 1');
 *   $logger->debug(
 *     'message 2',
 *     [
 *       'key1' => 'value1',
 *       'key2' => 'value2'
 *     ]
 *   );
 *   $logger->flush();
 *   $logger->setHeader('-- header2 --');
 *   $logger->info('Second block');
 */

namespace core\loggers;

use core\Utils;

/**
 * Class BufferedLogger
 * @package core\loggers
 */
class BufferedLogger extends StreamLogger
{
    /**
     * Batch of messages
     * @var array
     */
    private $buffer = [];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Auto flush buffer to log-file
     */
    public function stop()
    {
        $this->flush();
        parent::stop();
    }

    /**
     * Flush messages from buffer to log-file and clear buffer
     */
    public function flush()
    {
        $fp = $this->openFile($this->filename);
        $first_string = true;
        flock($fp, LOCK_EX);
        foreach($this->buffer as $message)
        {
            if ($first_string) {
                $message .=  ' {' . $this->getTitle() . '}';
                $first_string = false;
            }
            fwrite($fp, $message . PHP_EOL);
        }
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        $this->buffer = [];
    }

    /**
     * Add message to batch
     * @param string $message
     */
    protected function write($message)
    {
        // Add start line
        if (empty($this->buffer))
        {
            $this->buffer[] = $this->getTimestamp($this->date_format . ' ' . $this->time_format);
        }
        $this->buffer[] = $message;
    }

    /**
     * Create formatted string for writing to log
     * @param $level
     * @param $message
     * @param array $context
     * @return string
     */
    protected function formatMessage($level, $message, $context = [])
    {
        return (
            $this->getTimestamp($this->time_format) . ' ' .
            $this->withSuffix($this->getIPAddress()) .
            $this->getLevelName($level) . ' => ' .
            $message .
            (!empty($context) ? PHP_EOL . $this->indent(Utils::arrayToString($context, PHP_EOL)) : '')
        );
    }
}
