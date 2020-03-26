<?php

declare(strict_types=1);

namespace App\Handler\Exception;

use Laminas\Log\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class ReCAPTCHAException extends AbstractException
{
    /** @var string */
    private $username;

    /** @var float */
    private $score;

    /** @var float */
    private $threshold;

    public function __construct(
        ?string $username,
        ?float $score,
        float $threshold = 0.5,
        int $code = 0,
        ?Throwable $previous = null,
        ?ServerRequestInterface $request = null
    ) {
        $this->username = $username;
        $this->score = $score;
        $this->threshold = $threshold;

        parent::__construct('reCAPTCHA failed.', $code, $previous);

        $this->log('Failed reCAPTCHA with a score of {score} ({username}).', Logger::CRIT, $request);
    }

    public function getData(): array
    {
        return [
            'username'  => $this->username,
            'score'     => $this->score,
            'threshold' => $this->threshold,
        ];
    }
}
