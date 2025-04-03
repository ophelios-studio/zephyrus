<?php namespace Zephyrus\Core\Mailer;

use InvalidArgumentException;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Zephyrus\Core\Configuration\Mailer\MailerConfiguration;
use Zephyrus\Exceptions\Mailer\MailerAttachmentNotFoundException;
use Zephyrus\Exceptions\Mailer\MailerException;
use Zephyrus\Exceptions\Mailer\MailerInvalidAddressException;
use Zephyrus\Network\ResponseBuilder;
use Zephyrus\Utilities\FileSystem\File;

class Mailer
{
    private PHPMailer $phpMailer;
    private ?MailerService $service;

    /**
     * If no service is given, the class will make the raw email response available in send instead of sending the
     * email through SMTP.
     *
     * @param MailerConfiguration $configuration
     */
    public function __construct(MailerConfiguration $configuration)
    {
        $this->phpMailer = new PHPMailer(true);
        $this->phpMailer->CharSet = 'UTF-8';
        $this->service = $configuration->buildService();
        $this->service->initialize($this->phpMailer);
        if ($configuration->getDefaultFromAddress()) {
            $this->setFrom($configuration->getDefaultFromAddress(), $configuration->getDefaultFromName());
        }
    }

    public function getPhpMailer(): PHPMailer
    {
        return $this->phpMailer;
    }

    public function setTemplate(string $template, array $data = []): void
    {
        $this->setBody($this->makeHtmlBody($template, $data));
    }

    private function makeHtmlBody(string $template, array $data = []): string
    {
        $response = new ResponseBuilder()->render($template, $data);
        return $response->getContent();
    }

    /**
     * Applies the FROM address and name for all emails built from this Mailer instance.
     *
     * @param string $email
     * @param string|null $name
     * @throws MailerInvalidAddressException
     */
    public function setFrom(string $email, ?string $name = null): void
    {
        try {
            $this->phpMailer->setFrom($email, $name ?? '');
        } catch (Exception) {
            throw new MailerInvalidAddressException($email);
        }
    }

    /**
     * @throws MailerException
     */
    public function send(bool $asHtml = true): string
    {
        return $this->service?->send($this->phpMailer, $asHtml)
            ?? $this->buildRawMimeMessage($asHtml);
    }

    /**
     * @throws MailerException
     */
    public function buildRawMimeMessage(bool $asHtml = true): string
    {
        $this->phpMailer->IsHTML($asHtml);
        try {
            $this->phpMailer->preSend();
        } catch (Exception $exception) {
            throw new MailerException($exception->getMessage());
        }
        return $this->phpMailer->getSentMIMEMessage();
    }

    public function clearAttachments(): void
    {
        $this->phpMailer->clearAttachments();
    }

    public function clearRecipients(): void
    {
        $this->phpMailer->clearAllRecipients();
    }

    public function setSubject(string $subject): void
    {
        $this->phpMailer->Subject = $subject;
    }

    /**
     * Applies the given $content as the email body that will be sent to recipients. The $altContent is used for clients
     * which cannot render HTML content.
     *
     * @param string $content
     * @param string $altContent
     */
    public function setBody(string $content, string $altContent = ""): void
    {
        $this->phpMailer->Body = $content;
        $this->phpMailer->AltBody = $altContent;
    }

    /**
     * @throws MailerInvalidAddressException
     */
    public function addRecipient(string $email, string $name = ""): void
    {
        try {
            $this->phpMailer->addAddress($email, $name);
        } catch (Exception) {
            throw new MailerInvalidAddressException($email);
        }
    }

    /**
     * @throws MailerInvalidAddressException
     */
    public function addRecipientCc(string $email, string $name = ""): void
    {
        try {
            $this->phpMailer->addCC($email, $name);
        } catch (Exception) {
            throw new MailerInvalidAddressException($email);
        }
    }

    /**
     * @throws MailerInvalidAddressException
     */
    public function addRecipientBcc(string $email, string $name = ""): void
    {
        try {
            $this->phpMailer->addBCC($email, $name);
        } catch (Exception) {
            throw new MailerInvalidAddressException($email);
        }
    }

    /**
     * @throws MailerInvalidAddressException
     */
    public function addRecipients(array $recipients): void
    {
        foreach ($recipients as $email => $name) {
            $this->addRecipient($email, $name);
        }
    }

    /**
     * @throws MailerInvalidAddressException
     */
    public function addReplyTo(string $email, string $name = ""): void
    {
        try {
            $this->phpMailer->addReplyTo($email, $name);
        } catch (Exception) {
            throw new MailerInvalidAddressException($email);
        }
    }

    /**
     * Adds an attachment from the filesystem. The file will then be accessible for download by the recipients. The
     * given path must point to an existing and accessible file on the filesystem, throws an exception otherwise. If
     * the $filename argument is supplied, this will override the original filename in the mail (so when users download
     * the file, they will have this filename). If you need to add an attachment from a URL, make sure to fetch it
     * beforehand and store it within your filesystem and then use this method.
     *
     * @param string $path
     * @param string|null $filename
     * @throws MailerAttachmentNotFoundException
     * @throws MailerException
     */
    public function addAttachment(string $path, ?string $filename = null): void
    {
        try {
            $attachment = new File($path);
            $this->phpMailer->addAttachment(
                $path,
                $filename ?? $attachment->getFilename(),
                PHPMailer::ENCODING_BASE64,
                $attachment->getMimeType()
            );
        } catch (InvalidArgumentException) {
            throw new MailerAttachmentNotFoundException($path);
        } catch (Exception $exception) {
            throw new MailerException($exception->getMessage());
        }
    }

    /**
     * Adds an embedded (inline) attachment from a file. This can include images, sounds, and just about any other
     * document type. These differ from 'regular' attachments in which they are intended to be displayed inline with
     * the message, not just attached for download. This is used in HTML messages that embed the images the HTML refers
     * to using the `$cid` value in `img` tags, for example `<img src="cid:mylogo">`.
     *
     * @param string $path
     * @param string $cid
     * @param string|null $filename
     * @throws MailerAttachmentNotFoundException
     * @throws MailerException
     */
    public function addInlineAttachment(string $path, string $cid, ?string $filename = null): void
    {
        try {
            $attachment = new File($path);
            $this->phpMailer->addEmbeddedImage(
                $path,
                $cid,
                $filename ?? $attachment->getFilename(),
                PHPMailer::ENCODING_BASE64,
                $attachment->getMimeType()
            );
        } catch (InvalidArgumentException) {
            throw new MailerAttachmentNotFoundException($path);
        } catch (Exception $exception) {
            throw new MailerException($exception->getMessage());
        }
    }

    /**
     * Adds an attachment from a string or binary attachment (non-filesystem) that will be accessible for download by
     * the recipients. This method can be used to attach ascii or binary data, such as a BLOB record from a database.
     * Filename must be supplied including the file extension. Optionally, the $mimeType can be given for precise
     * content type information for the attachment. Leave null to let the PHPMailer algorithm figure out the proper
     * mime type from the given filename extension.
     *
     * @param string $string
     * @param string $filename
     * @param string|null $mimeType
     * @throws MailerException
     */
    public function addBinaryAttachment(string $string, string $filename, ?string $mimeType = null): void
    {
        try {
            $this->phpMailer->addStringAttachment(
                $string,
                $filename,
                PHPMailer::ENCODING_BASE64,
                $mimeType ?? ''
            );
        } catch (Exception $exception) {
            throw new MailerException($exception->getMessage());
        }
    }

    /**
     * Adds an embedded (inline) attachment from a string or binary (non-filesystem) that will be accessible within the
     * email structure (see method addBinaryAttachment).
     *
     * @param string $string
     * @param string $filename
     * @param string|null $mimeType
     * @throws MailerException
     * @return void
     */
    public function addBinaryInlineAttachment(string $string, string $filename, ?string $mimeType = null): void
    {
        try {
            $this->phpMailer->addStringAttachment(
                $string,
                $filename,
                PHPMailer::ENCODING_BASE64,
                $mimeType ?? '',
                'inline'
            );
        } catch (Exception $exception) {
            throw new MailerException($exception->getMessage());
        }
    }
}
