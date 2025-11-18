<?php
// 最簡單版：只要 PHP 執行得到、網頁有回應，就算健康
http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
echo "OK";
