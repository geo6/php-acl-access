<?php

declare(strict_types=1);

namespace App;

use Ramsey\Uuid\Uuid;

class RecoveryCode
{
    /** @var string */
    const DIRECTORY = 'data/code';

    /** @var int */
    const LENGTH = 6;

    /** @var int */
    const TIMEOUT = (1 * 60 * 60);

    /** @var string */
    private $code;

    /** @var string */
    private $uuid;

    public function __construct(int $length = self::LENGTH)
    {
        $this->generate($length);
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getUUID(): string
    {
        return $this->uuid;
    }

    private function generate(int $length = self::LENGTH): string
    {
        $this->code = '';
        for ($i = 0; $i < $length; $i++) {
            $this->code .= random_int(0, 9);
        }

        return $this->code;
    }

    public function store(?array $data = null, string $directory = self::DIRECTORY): string
    {
        if (!file_exists($directory) || !is_dir($directory)) {
            mkdir($directory, 0700, true);
        }

        $this->uuid = (Uuid::uuid4())->toString();

        $content = sprintf('code = %s', $this->code).PHP_EOL;

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $content .= sprintf('%s = %s', $key, $value).PHP_EOL;
            }
        }

        file_put_contents($directory.'/'.$this->uuid, $content);

        return $this->uuid;
    }

    public static function clean(string $directory = self::DIRECTORY, int $timeout = self::TIMEOUT): void
    {
        if (file_exists($directory) && is_dir($directory) && is_readable($directory)) {
            $glob = glob($directory.'/*');

            if ($glob !== false) {
                foreach ($glob as $file) {
                    $time = filemtime($file);

                    if ($time < time() - $timeout) {
                        unlink($file);
                    }
                }
            }
        }
    }
}
