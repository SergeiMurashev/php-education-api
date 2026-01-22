<?php

return [
    "jwt_secret" => "CHANGE_ME_TO_SOMETHING_RANDOM_32+",

    "access_ttl_seconds"  => 900,       // 15 минут
    "refresh_ttl_seconds" => 60 * 60 * 24 * 7, // 7 дней
];