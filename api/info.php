<?php
// 临时版本探针：只输出版本信息，不暴露 phpinfo 的路径/配置/环境变量
header('Content-Type: text/plain; charset=UTF-8');
echo 'PHP_VERSION=', PHP_VERSION, "\n";
echo 'PHP_SAPI=', PHP_SAPI, "\n";
echo 'PHP_INT_SIZE=', PHP_INT_SIZE, "\n";
echo 'OPCACHE=', extension_loaded('Zend OPcache') ? 'on' : 'off', "\n";
