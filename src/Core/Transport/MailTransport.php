<?php

declare(strict_types=1);

namespace Naf\Mail\Core\Transport;

use Naf\Mail\Core\Transport\Trait\MailMessageRenderer;
use Naf\Mail\Core\TransportInterface;
use Naf\Mail\Exceptions\MailException;
use Naf\Mail\Models\Mail;

class MailTransport implements TransportInterface
{
    use MailMessageRenderer;

    /**
     * @param Mail $mail
     *
     * @return bool
     * @throws MailException
     */
    public function sendMail(Mail $mail): bool
    {
        [
            'to'      => $to,
            'subject' => $subject,
            'header'  => $header,
            'body'    => $body
        ] = $this->render($mail);

        if (!mail($to, $subject, $body, $header)) {
            throw new MailException('Unable to send mail using PHP mail()');
        }

        return true;
    }

}