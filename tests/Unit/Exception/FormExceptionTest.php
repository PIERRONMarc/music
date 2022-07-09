<?php

namespace App\Tests\Unit\Exception;

use App\Exception\FormHttpException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;

class FormExceptionTest extends TestCase
{
    public function testGetters(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formException = new FormHttpException($form);

        $this->assertInstanceOf(FormInterface::class, $formException->getForm());
        $this->assertInstanceOf(FormErrorIterator::class, $formException->getErrors());
    }
}
