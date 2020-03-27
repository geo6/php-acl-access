<?php

declare(strict_types=1);

namespace App;

use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mime;
use Mezzio\Template\TemplateRendererInterface;

class Mail
{
    public static function send(
        array $config,
        TemplateRendererInterface $renderer,
        string $to,
        string $subject,
        string $template,
        array $data
    ): void {
        $html = $renderer->render($template, $data);

        $bodyHtml = new Mime\Part($html);
        $bodyHtml->setEncoding(Mime\Mime::ENCODING_QUOTEDPRINTABLE);
        $bodyHtml->setType(Mime\Mime::TYPE_HTML);
        $bodyHtml->setCharset('UTF-8');

        $body = new Mime\Message();
        $body->addPart($bodyHtml);

        $mail = new Message();
        $mail->setEncoding('UTF-8');
        $mail->setBody($body);
        $mail->setFrom($config['from']);
        $mail->addTo($to);
        $mail->setSubject($subject);

        $transport = new SmtpTransport();
        $transport->setOptions(new SmtpOptions($config['smtp']));
        $transport->send($mail);
    }
}
