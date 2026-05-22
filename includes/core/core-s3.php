<?php
/**
 * core-s3.php — функции для работы с MinIO (S3-совместимое хранилище)
 */

if (!defined('ABSPATH')) exit;


/**
 * Получить объект из MinIO
 */
function s3_get_object($bucket, $key) {
    $url = S3_ENDPOINT . '/' . $bucket . '/' . $key;
    $date = gmdate('D, d M Y H:i:s \G\M\T');
    $string_to_sign = "GET\n\n\n{$date}\n/{$bucket}/{$key}";
    $signature = base64_encode(hash_hmac('sha1', $string_to_sign, S3_SECRET, true));
    
    $response = wp_remote_get($url, [
        'headers' => [
            'Date' => $date,
            'Authorization' => 'AWS ' . S3_KEY . ':' . $signature,
        ],
        'timeout' => 10,
    ]);
    
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return '';
    }
    
    return wp_remote_retrieve_body($response);
}

/**
 * Положить объект в MinIO
 */
function s3_put_object($bucket, $key, $body, $content_type = 'text/html') {
    $url = S3_ENDPOINT . '/' . $bucket . '/' . $key;
    $date = gmdate('D, d M Y H:i:s \G\M\T');
    $content_md5 = base64_encode(md5($body, true));
    
    $string_to_sign = "PUT\n{$content_md5}\n{$content_type}\n{$date}\n/{$bucket}/{$key}";
    $signature = base64_encode(hash_hmac('sha1', $string_to_sign, S3_SECRET, true));
    
    $response = wp_remote_request($url, [
        'method' => 'PUT',
        'headers' => [
            'Date' => $date,
            'Content-Type' => $content_type,
            'Content-MD5' => $content_md5,
            'Authorization' => 'AWS ' . S3_KEY . ':' . $signature,
        ],
        'body' => $body,
        'timeout' => 30,
    ]);
    
    return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
}

/**
 * Получить подписанный URL (временный доступ) — AWS4-HMAC-SHA256
 */
function s3_get_presigned_url($bucket, $key, $expires = 3600) {
    $region = 'us-east-1';
    $timestamp = time();
    $amz_date = gmdate('Ymd\THis\Z', $timestamp);
    $date_short = gmdate('Ymd', $timestamp);
    
    $credential_scope = "{$date_short}/{$region}/s3/aws4_request";
    $credential = S3_KEY . "/{$credential_scope}";
    
    $canonical_request = "GET\n/{$bucket}/{$key}\n\nhost:audiobook.1001ranobe.ru:9000\n\nhost\nUNSIGNED-PAYLOAD";
    $hashed_canonical = hash('sha256', $canonical_request);
    
    $string_to_sign = "AWS4-HMAC-SHA256\n{$amz_date}\n{$credential_scope}\n{$hashed_canonical}";
    
    $date_key = hash_hmac('sha256', $date_short, 'AWS4' . S3_SECRET, true);
    $region_key = hash_hmac('sha256', $region, $date_key, true);
    $service_key = hash_hmac('sha256', 's3', $region_key, true);
    $signing_key = hash_hmac('sha256', 'aws4_request', $service_key, true);
    $signature = hash_hmac('sha256', $string_to_sign, $signing_key);
    
    return S3_ENDPOINT . "/{$bucket}/{$key}?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=" . urlencode($credential) . "&X-Amz-Date={$amz_date}&X-Amz-Expires={$expires}&X-Amz-SignedHeaders=host&X-Amz-Signature={$signature}";
}