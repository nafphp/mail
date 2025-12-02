<?php

declare(strict_types=1);

namespace NixPHP\Mail\Models;

use NixPHP\Mail\Exceptions\MailException;

class Mail
{
    protected array $recipients = [];
    protected array $carbonCopies = [];
    protected array $blindCarbonCopies = [];
    protected string $from = '';
    protected string $replyTo = '';
    protected string $subject = '';
    protected string $content = '';
    protected bool $isHtml = true;

    /** @var array<array{name: string, path: string, encoded: string, mimetype: string, inline: bool}> List of email attachments */
    protected array $attachments = [];

    /**
     * Add a primary recipient to the email.
     *
     * @param string $address The recipient's email address
     * @return static Returns the Mail instance for method chaining
     */
    public function addTo(string $address): static
    {
        $this->recipients[] = $address;
        return $this;
    }

    /**
     * Add a carbon copy (CC) recipient to the email.
     *
     * CC recipients will receive a copy of the email, and their address
     * will be visible to all other recipients.
     *
     * @param string $address The CC recipient's email address
     * @return static Returns the Mail instance for method chaining
     */
    public function addCc(string $address): static
    {
        $this->carbonCopies[] = $address;
        return $this;
    }

    /**
     * Add a blind carbon copy (BCC) recipient to the email.
     *
     * BCC recipients will receive a copy of the email, but their address
     * will not be visible to other recipients.
     *
     * @param string $address The BCC recipient's email address
     * @return static Returns the Mail instance for method chaining
     */
    public function addBcc(string $address): static
    {
        $this->blindCarbonCopies[] = $address;
        return $this;
    }

    /**
     * Set the sender's email address.
     *
     * @param string $address The sender's email address
     * @return static Returns the Mail instance for method chaining
     */
    public function setFrom(string $address): static
    {
        $this->from = $address;
        return $this;
    }

    /**
     * Set the reply-to email address.
     *
     * This address will be used when recipients reply to the email.
     * If not set, replies will go to the sender's address.
     *
     * @param string $address The reply-to email address
     * @return static Returns the Mail instance for method chaining
     */
    public function setReplyTo(string $address): static
    {
        $this->replyTo = $address;
        return $this;
    }

    /**
     * Set the email subject line.
     *
     * @param string $subject The subject text
     * @return static Returns the Mail instance for method chaining
     */
    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set the email body content.
     *
     * @param string $content The email body content
     * @param bool   $isHtml  Whether the content is HTML (true) or plain text (false). Defaults to true.
     * @return static         Returns the Mail instance for method chaining
     */
    public function setContent(string $content, bool $isHtml = true): static
    {
        $this->content = $content;
        $this->isHtml = $isHtml;
        return $this;
    }

    /**
     * Add an attachment to the email.
     *
     * The file will be read from the filesystem, base64 encoded, and included
     * in the email. The MIME type is automatically detected.
     *
     * @param string $name   The attachment filename as it will appear in the email
     * @param string $path   The full filesystem path to the attachment file
     * @param bool   $inline Whether the attachment should be inline (embedded in content). Defaults to false.
     * @return static        Returns the Mail instance for method chaining
     * @throws MailException If the attachment file does not exist
     */
    public function addAttachment(string $name, string $path, bool $inline = false): static
    {
        if (!is_file($path)) {
            throw new MailException("Attachment file not found: $path");
        }

        $this->attachments[] = [
            'name'     => $name,
            'path'     => $path,
            'encoded'  => chunk_split(base64_encode(file_get_contents($path))),
            'mimetype' => mime_content_type($path),
            'inline'   => $inline,
        ];

        return $this;
    }

    /**
     * Get the list of primary recipients.
     *
     * @return array<string> Array of recipient email addresses
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    /**
     * Get the list of carbon copy (CC) recipients.
     *
     * @return array<string> Array of CC email addresses
     */
    public function getCc(): array
    {
        return $this->carbonCopies;
    }

    /**
     * Get the list of blind carbon copy (BCC) recipients.
     *
     * @return array<string> Array of BCC email addresses
     */
    public function getBcc(): array
    {
        return $this->blindCarbonCopies;
    }

    /**
     * Get the sender's email address.
     *
     * @return string The sender's email address
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * Get the reply-to email address.
     *
     * Falls back to the sender's address if no reply-to address was set.
     *
     * @return string The reply-to email address
     */
    public function getReplyTo(): string
    {
        return $this->replyTo ?: $this->from;
    }

    /**
     * Get the email subject line.
     *
     * @return string The subject text
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Get the email body content.
     *
     * @return string The email body content
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Check if the email content is HTML.
     *
     * @return bool True if the content is HTML, false if plain text
     */
    public function isHtml(): bool
    {
        return $this->isHtml;
    }

    /**
     * Get all email attachments.
     *
     * @return array<array{name: string, path: string, encoded: string, mimetype: string, inline: bool}> Array of attachment data
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

}