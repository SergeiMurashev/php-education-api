<?php

error_log("REQUEST: {$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}");

require __DIR__ . '/router.php';