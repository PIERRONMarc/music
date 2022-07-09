<?php

namespace App\Tests\Functional\Factory;

use App\Exception\FormHttpException;
use App\Factory\NormalizerFactory;
use App\Serializer\FormExceptionNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class NormalizerFactoryTest extends TestCase
{
    public function testGettingNormalizer(): void
    {
        $normalizerFactory = new NormalizerFactory([
            new FormExceptionNormalizer(),
        ]);

        $form = $this->createMock(FormInterface::class);
        $formException = new FormHttpException($form);

        $this->assertInstanceOf(FormExceptionNormalizer::class, $normalizerFactory->getNormalizer($formException));
        $this->assertNull($normalizerFactory->getNormalizer(new AccessDeniedHttpException()));
    }
}
