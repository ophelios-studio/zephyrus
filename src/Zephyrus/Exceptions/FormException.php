<?php namespace Zephyrus\Exceptions;

use Zephyrus\Application\Form;

class FormException extends ZephyrusRuntimeException
{
    private ?Form $failedForm;
    private ?string $redirectPath = null;

    public function __construct(?Form $failedForm, ?string $message = null)
    {
        parent::__construct($message ?? 'Form validation failed.');
        $this->failedForm = $failedForm;
    }

    public function setRedirectPath(string $redirectPath): void
    {
        $this->redirectPath = $redirectPath;
    }

    public function getRedirectPath(): ?string
    {
        return $this->redirectPath;
    }

    public function getForm(): ?Form
    {
        return $this->failedForm;
    }
}
