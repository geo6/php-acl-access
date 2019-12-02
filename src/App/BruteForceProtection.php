<?php

declare(strict_types=1);

namespace App;

use DateTime;
use ErrorException;
use Psr\Http\Message\ServerRequestInterface;

class BruteForceProtection
{
    /** @var string */
    const DIRECTORY = 'data/lock';

    /** @var string */
    private $ip;

    /** @var int */
    private $count = 0;

    /** @var DateTime */
    private $creation;

    /** @var DateTime */
    private $lastUpdate;

    /** @var DateTime */
    private $lockUntil;

    public function __construct(ServerRequestInterface $request)
    {
        self::clean();

        $this->ip = self::getIP($request);

        $this->read();
    }

    private function getPath(): string
    {
        return self::DIRECTORY.'/'.md5($this->ip);
    }

    private function read(): void
    {
        if (file_exists($this->getPath())) {
            if (!is_readable($this->getPath())) {
                throw new ErrorException(
                    sprintf('Lock file "%s" is not readable.', $this->getPath())
                );
            }

            $data = json_decode(file_get_contents($this->getPath()), true);

            $this->creation = new DateTime($data['creation']);
            $this->count = $data['count'];
            $this->lastUpdate = new DateTime($data['lastUpdate']);
            $this->lockUntil = !is_null($data['lockUntil']) ? new DateTime($data['lockUntil']) : null;
        }
    }

    private function write(): void
    {
        $data = [
            'ip'         => $this->ip,
            'creation'   => $this->creation->format('c'),
            'count'      => $this->count,
            'lastUpdate' => $this->lastUpdate->format('c'),
            'lockUntil'  => !is_null($this->lockUntil) ? $this->lockUntil->format('c') : null,
        ];

        file_put_contents($this->getPath(), json_encode($data));
    }

    public function clear(): void
    {
        if (file_exists($this->getPath())) {
            unlink($this->getPath());
        }
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function isLocked(): bool
    {
        if (is_null($this->lockUntil)) {
            return false;
        }

        return $this->lockUntil >= (new DateTime());
    }

    public function increment(): int
    {
        $time = new DateTime();

        if (is_null($this->creation)) {
            $this->creation = $time;
        }

        $this->count++;
        $this->lastUpdate = $time;

        $this->write();

        return $this->count;
    }

    public function lockUntil(DateTime $datetime): void
    {
        $time = new DateTime();

        if (is_null($this->creation)) {
            $this->creation = $time;
        }

        $this->lockUntil = $datetime;
        $this->lastUpdate = $time;

        $this->write();
    }

    private static function clean(): void
    {
        $glob = glob(self::DIRECTORY.'/*');

        foreach ($glob as $path) {
            $json = json_decode(file_get_contents($path), true);

            $now = new DateTime();
            $diff = $now->diff(new DateTime($json['lastUpdate']));

            if ($diff->days >= 1) {
                unlink($path);
            }
        }
    }

    private static function getIP(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();

        if (isset($server['HTTP_X_FORWARDED_FOR'])) {
            return $server['HTTP_X_FORWARDED_FOR'];
        }

        return $server['REMOTE_ADDR'];
    }
}
