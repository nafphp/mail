<?php

declare(strict_types=1);

namespace NixPHP\Mail\Core\Transport\Trait;

use NixPHP\Mail\Models\Mail;

trait MailMessageRenderer
{

    /**
     * @param Mail $mail
     *
     * @return array{to: string, subject: string, header: string, body: string}
     */
    protected function render(Mail $mail): array
    {
        if (empty($mail->getRecipients())) {
            throw new \InvalidArgumentException('At least one recipient is required');
        }

        $to = implode(',', $mail->getRecipients());
        $subject = mb_encode_mimeheader($mail->getSubject(), 'UTF-8');

        $boundary = md5(uniqid((string)mt_rand(), true));
        $eol = "\r\n";

        // Headers
        $header  = 'MIME-Version: 1.0' . $eol;
        $header .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . $eol;
        $header .= 'From: ' . $mail->getFrom() . $eol;
        $header .= 'Reply-To: ' . $mail->getReplyTo() . $eol;

        if ($cc = $mail->getCc()) {
            $header .= 'Cc: ' . implode(',', $cc) . $eol;
        }

        if ($bcc = $mail->getBcc()) {
            $header .= 'Bcc: ' . implode(',', $bcc) . $eol;
        }

        // Body
        $body = '--' . $boundary . $eol;
        $body .= 'Content-Type: ' . ($mail->isHtml() ? 'text/html' : 'text/plain') . '; charset="utf-8"' . $eol;
        $body .= 'Content-Transfer-Encoding: 8bit' . $eol . $eol;
        $body .= $mail->getContent() . $eol;

        foreach ($mail->getAttachments() as $attachment) {

            $name     = $this->escape($attachment['name']);
            $mimetype = $this->escape($attachment['mimetype']);

            $body .= '--' . $boundary . $eol;
            $body .= 'Content-Type: ' . $mimetype . '; name="' . $name . '"' . $eol;
            if ($attachment['inline']) {
                $body .= 'Content-ID: <' . $name . '>' . $eol;
                $body .= 'Content-Disposition: inline; filename="' . $name . '"' . $eol;
            } else {
                $body .= 'Content-Disposition: attachment; filename="' . $name . '"' . $eol;
            }
            $body .= 'Content-Transfer-Encoding: base64' . $eol;
            $body .= 'X-Attachment-Id: ' . uniqid() . $eol . $eol;
            $body .= $attachment['encoded'] . $eol;
        }

        $body .= '--' . $boundary . '--' . $eol;

        return compact('to', 'subject', 'header', 'body');
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function escape(string $string): string
    {
        return str_replace(["\r", "\n"], [' ', ' '], $string);
    }

}