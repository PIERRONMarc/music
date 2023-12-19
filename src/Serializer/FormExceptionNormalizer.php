<?php

namespace App\Serializer;

use App\Exception\FormHttpException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FormExceptionNormalizer implements NormalizerInterface
{
    /**
     * @param FormHttpException $exception
     * @param ?string           $format
     *
     * @return mixed[]
     */
    public function normalize($exception, string $format = null, array $context = []): array
    {
        $violations = [];
        $errors = $exception->getErrors();

        foreach ($errors as $error) {
            $violations[] = [
                'property' => $error->getOrigin()->getName(),
                'message' => $error->getMessage(),
            ];
        }

        return $violations;
    }

    /**
     * @param null $format
     * @param array<mixed> $context     */
    public function supportsNormalization($data, $format = null, $context = []): bool
    {
        return $data instanceof FormHttpException;
    }

    /**
     * @return array<int, string>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [FormHttpException::class];
    }
}
