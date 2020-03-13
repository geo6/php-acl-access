<?php

declare(strict_types=1);

namespace App;

use Geo6\Zend\Log\Log as Geo6Log;
use Laminas\Log\Logger;

class Log
{
    const DIRECTORY = 'data/log';

    private $path;

    public function __construct(string $message, array $extra = [], int $priority = Logger::INFO)
    {
        $this->path = self::DIRECTORY.'/'.date('Ym').'.log';

        Geo6Log::write($this->path, $message, $extra, $priority);
    }
}