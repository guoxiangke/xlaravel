<?php

return [
    'r2_share_audio' => env('R2_SHARE_AUDIO', 'https://r2.savefamily.net'),
    'r2_share_video' => env('R2_SHARE_VIDEO', 'https://r2.savefamily.net'),
    'resource_all_json_domain' => env('RESOURCE_ALL_JSON_DOMAIN', 'https://febc.blob.core.windows.net/jso/'),
    
    // Additional R2 domains
    'r2_pub_domain' => env('R2_PUB_DOMAIN', 'https://pub-3813a5d14cba4eaeb297a0dba302143c.r2.dev'),
    'r2_tingdao_domain' => env('R2_TINGDAO_DOMAIN', 'https://pub-6de883f3fd4a43c28675e9be668042c2.r2.dev'),
    
    // External API domains
    'jtoday_domain' => env('JTODAY_DOMAIN', 'http://www.jtoday.org'),
    'odb_domain' => env('ODB_DOMAIN', 'https://dzxuyknqkmi1e.cloudfront.net'),
];
