<?php
// Proxy delegate to Laravel's public/index.php
// Required because LSWS quirk: returns 404 on / when no indexFile in docroot
// before processing .htaccess rewrite. With this proxy, Laravel takes over
// when index.html is removed (i.e., when vertical activates real content).
require __DIR__ . '/public/index.php';
