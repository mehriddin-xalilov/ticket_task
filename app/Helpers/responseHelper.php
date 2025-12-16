<?php

use Illuminate\Http\JsonResponse;

/**
 * You can use this function to return an ok responses in a consistent way
 * @param null $data
 * @param int $status Default value is 200 it should be a valid HTTP status code
 * @param array $meta
 * @param string $message
 * @return JsonResponse
 */
function okResponse($data = null, int $status = 200, array $meta = [], string $message = "OK"): JsonResponse
{
    $message = getStatusMessage($status);
    $response = [
        'status_code' => $status,
        'message' => $message,
        'data' => $data
    ];
    if (!empty($meta)) {
        $response['meta'] = $meta;
    }
    return response()->json($response, $status);
}

function getStatusMessage(int $status): string
{
    $statusMessages = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
    ];
    return $statusMessages[$status] ?? $statusMessages[500];
}

function validateStatusCode($code)
{
    $statusCodes = [
        200,
        201,
        204,
        400,
        401,
        403,
        404,
        405,
        422,
        429,
        500
    ];
    if (!in_array($code, $statusCodes)) {
        return 500;
    }
    return $code;
}

/**
 * You can use this function to return an error responses in a consistent way
 * @param array $data Additional data to be returned
 * @param null $message
 * @param int $status Default value is 500 it should be a valid HTTP status code
 * @return JsonResponse
 */
function errorResponse($message = null, int $status = 500, array $data = []): JsonResponse
{
    if (!$message) {
        $message = getStatusMessage($status);
    }
    $status = validateStatusCode($status);
    $response = [
        'status_code' => $status,
        'message' => $message
    ];
    if (!empty($data)) {
        $response['data'] = $data;
    }
    return response()->json($response, validateStatusCode($status));
}
