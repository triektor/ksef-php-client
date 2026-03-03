<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Factories;

use N1ebieski\KSEFClient\Exceptions\HttpClient\BadRequestException;
use N1ebieski\KSEFClient\Exceptions\HttpClient\ClientException;
use N1ebieski\KSEFClient\Exceptions\HttpClient\Exception;
use N1ebieski\KSEFClient\Exceptions\HttpClient\InternalServerException;
use N1ebieski\KSEFClient\Exceptions\HttpClient\RateLimitException;
use N1ebieski\KSEFClient\Exceptions\HttpClient\ServerException;
use N1ebieski\KSEFClient\Exceptions\HttpClient\UnknownSystemException;
use N1ebieski\KSEFClient\Factories\AbstractFactory;
use N1ebieski\KSEFClient\Support\Utility;

final class ExceptionFactory extends AbstractFactory
{
    /**
     * @param null|object{exception?: object{exceptionDetailList: array<int, object{exceptionCode: int, exceptionDescription: string}>}, status?: object{code: int, description: string, details: array<int, string>}, message?: string, title?: string} $exceptionResponse
     */
    public static function make(
        int $statusCode,
        ?object $exceptionResponse
    ): Exception {
        $message = match (true) {
            isset($exceptionResponse->message) => $exceptionResponse->message,
            isset($exceptionResponse->title) => $exceptionResponse->title,
            default => null
        };

        /** @var class-string<Exception> $exceptionNamespace */
        $exceptionNamespace = match (true) {
            $statusCode === 400 => Utility::value(function () use ($exceptionResponse, &$message): string {
                /** @var object{exception: object{exceptionDetailList: array<int, object{exceptionCode: int, exceptionDescription: string}>}} $exceptionResponse */
                $message = self::getExceptionMessage($exceptionResponse);

                return BadRequestException::class;
            }),
            $statusCode === 429 => Utility::value(function () use ($exceptionResponse, &$message): string {
                /** @var object{status: object{code: int, description: string, details: array<int, string>}} $exceptionResponse */
                $message = self::getStatusMessage($exceptionResponse);

                return RateLimitException::class;
            }),
            $statusCode === 500 => InternalServerException::class,
            $statusCode === 501 => UnknownSystemException::class,
            $statusCode > 400 && $statusCode < 500 => ClientException::class,
            $statusCode > 500 => ServerException::class,
            default => Exception::class
        };

        return new $exceptionNamespace(
            message: $message ?? '',
            code: $statusCode,
            context: $exceptionResponse
        );
    }

    /**
     * @param object{status: object{code: int, description: string, details: array<int, string>}} $exceptionResponse
     */
    private static function getStatusMessage(object $exceptionResponse): string
    {
        return "{$exceptionResponse->status->code} {$exceptionResponse->status->description}";
    }

    /**
     * @param object{exception: object{exceptionDetailList: array<int, object{exceptionCode: int, exceptionDescription: string}>}} $exceptionResponse
     */
    private static function getExceptionMessage(object $exceptionResponse): ?string
    {
        $exceptions = $exceptionResponse->exception->exceptionDetailList;

        $firstException = $exceptions[0] ?? null;

        if ($firstException !== null) {
            return "{$firstException->exceptionCode} {$firstException->exceptionDescription}";
        }

        return null;
    }
}
