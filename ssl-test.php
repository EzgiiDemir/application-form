<?php

echo "PHP: " . PHP_VERSION . PHP_EOL;
echo "php.ini: " . php_ini_loaded_file() . PHP_EOL;
echo "curl.cainfo: " . ini_get("curl.cainfo") . PHP_EOL;
echo "openssl.cafile: " . ini_get("openssl.cafile") . PHP_EOL;

$ch = curl_init("https://apply.arucad.edu.tr/wp-json");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => false,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$response = curl_exec($ch);

if ($response === false) {
    echo "CURL ERROR: " . curl_error($ch) . PHP_EOL;
    echo "CURL ERRNO: " . curl_errno($ch) . PHP_EOL;
} else {
    echo "CURL SSL OK" . PHP_EOL;
    echo "HTTP STATUS: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL;
}

curl_close($ch);
