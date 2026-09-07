<?php

namespace Formwork\Http;

use InvalidArgumentException;
use UnexpectedValueException;

/**
 * @see https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
 */
enum ResponseStatus: string
{
    // Informational
    case Continue = '100 Continue';
    case SwitchingProtocols = '101 Switching Protocols';
    case Processing = '102 Processing';
    case EarlyHints = '103 Early Hints';

    // Successful
    case OK = '200 OK';
    case Created = '201 Created';
    case Accepted = '202 Accepted';
    case NonAuthoritativeInformation = '203 Non-Authoritative Information';
    case NoContent = '204 No Content';
    case ResetContent = '205 Reset Content';
    case PartialContent = '206 Partial Content';
    case MultiStatus = '207 Multi-Status';
    case AlreadyReported = '208 Already Reported';
    case IMUsed = '226 IM Used';

    // Redirection
    case MultipleChoices = '300 Multiple Choices';
    case MovedPermanently = '301 Moved Permanently';
    case Found = '302 Found';
    case SeeOther = '303 See Other';
    case NotModified = '304 Not Modified';
    case UseProxy = '305 Use Proxy';
    case TemporaryRedirect = '307 Temporary Redirect';
    case PermanentRedirect = '308 Permanent Redirect';

    // Client Error
    case BadRequest = '400 Bad Request';
    case Unauthorized = '401 Unauthorized';
    case PaymentRequired = '402 Payment Required';
    case Forbidden = '403 Forbidden';
    case NotFound = '404 Not Found';
    case MethodNotAllowed = '405 Method Not Allowed';
    case NotAcceptable = '406 Not Acceptable';
    case ProxyAuthenticationRequired = '407 Proxy Authentication Required';
    case RequestTimeout = '408 Request Timeout';
    case Conflict = '409 Conflict';
    case Gone = '410 Gone';
    case LengthRequired = '411 Length Required';
    case PreconditionFailed = '412 Precondition Failed';
    case PayloadTooLarge = '413 Payload Too Large';
    case URITooLong = '414 URI Too Long';
    case UnsupportedMediaType = '415 Unsupported Media Type';
    case RangeNotSatisfiable = '416 Range Not Satisfiable';
    case ExpectationFailed = '417 Expectation Failed';
    case MisdirectedRequest = '421 Misdirected Request';
    case UnprocessableEntity = '422 Unprocessable Entity';
    case Locked = '423 Locked';
    case FailedDependency = '424 Failed Dependency';
    case TooEarly = '425 Too Early';
    case UpgradeRequired = '426 Upgrade Required';
    case PreconditionRequired = '428 Precondition Required';
    case TooManyRequests = '429 Too Many Requests';
    case RequestHeaderFieldsTooLarge = '431 Request Header Fields Too Large';
    case UnavailableForLegalReasons = '451 Unavailable For Legal Reasons';

    // Server Error
    case InternalServerError = '500 Internal Server Error';
    case NotImplemented = '501 Not Implemented';
    case BadGateway = '502 Bad Gateway';
    case ServiceUnavailable = '503 Service Unavailable';
    case GatewayTimeout = '504 Gateway Timeout';
    case HTTPVersionNotSupported = '505 HTTP Version Not Supported';
    case VariantAlsoNegotiates = '506 Variant Also Negotiates';
    case InsufficientStorage = '507 Insufficient Storage';
    case LoopDetected = '508 Loop Detected';
    case NotExtended = '510 Not Extended';
    case NetworkAuthenticationRequired = '511 Network Authentication Required';

    /**
     * Get the status code
     */
    public function code(): int
    {
        return (int) explode(' ', $this->value, 2)[0];
    }

    /**
     * Get the status message
     */
    public function message(): string
    {
        return explode(' ', $this->value, 2)[1];
    }

    /**
     * Get the response status from a status code
     */
    public static function fromCode(int $code): ResponseStatus
    {
        foreach (self::cases() as $case) {
            if ($case->code() === $code) {
                return $case;
            }
        }

        throw new InvalidArgumentException('HTTP status code not found');
    }

    /**
     * Get the response status type
     */
    public function type(): ResponseStatusType
    {
        $code = $this->code();

        if ($code >= 100 && $code <= 199) {
            return ResponseStatusType::Informational;
        }

        if ($code >= 200 && $code <= 299) {
            return ResponseStatusType::Successful;
        }

        if ($code >= 300 && $code <= 399) {
            return ResponseStatusType::Redirection;
        }

        if ($code >= 400 && $code <= 499) {
            return ResponseStatusType::ClientError;
        }

        if ($code >= 500 && $code <= 599) {
            return ResponseStatusType::ServerError;
        }

        throw new UnexpectedValueException(sprintf('Invalid response status code: %d', $code));
    }
}
