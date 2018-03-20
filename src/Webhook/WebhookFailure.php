<?php declare(strict_types=1);

namespace BeBound\SDK\Webhook;

class WebhookFailure extends \RuntimeException
{
    public const HTTP_CODE_WRONG_BEAPP = 404;
    public const HTTP_CODE_WRONG_AUTHORIZATION = 401;
    public const HTTP_CODE_OPERATION_NOT_FOUND = 400;
    public const HTTP_CODE_INTERNAL_ERROR = 500;

    public const BB_ERROR_REQUEST_REJECTED = 'BB_ERROR_REQUEST_REJECTED';
    public const BB_ERROR_AUTHORIZATION = 'BB_ERROR_AUTHORIZATION';
    public const BB_ERROR_METHOD_NOT_FOUND = 'BB_ERROR_METHOD_NOT_FOUND';
    public const BB_ERROR_UNKNOWN_USER_SPECIFIED_ERROR = 'BB_ERROR_UNKNOWN_USER_SPECIFIED_ERROR';

    public static function wrongBeapp(): self
    {
        return new self(self::BB_ERROR_REQUEST_REJECTED, self::HTTP_CODE_WRONG_BEAPP);
    }

    public static function wrongAuthorization(): self
    {
        return new self(self::BB_ERROR_AUTHORIZATION, self::HTTP_CODE_WRONG_AUTHORIZATION);
    }

    public static function wrongOperation(): self
    {
        return new self(self::BB_ERROR_METHOD_NOT_FOUND, self::HTTP_CODE_OPERATION_NOT_FOUND);
    }
}
