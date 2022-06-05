<?php

namespace App\Exception;

use Exception;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FormHttpException extends HttpException
{
    protected FormInterface $form;

    /**
     * @param mixed[] $headers
     */
    public function __construct(
        FormInterface $form,
        int $statusCode = 400,
        string $message = 'Validation failed',
        Exception $previous = null,
        array $headers = [],
        ?int $code = 0
    ) {
        parent::__construct($statusCode, $message, $previous, $headers, $code);

        $this->form = $form;
    }

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    public function getErrors(): FormErrorIterator
    {
        return $this->form->getErrors(true);
    }
}
