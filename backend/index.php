<?php

header('Content-Type: application/json');

echo json_encode([
    'application' => 'CareBP',
    'backend' => 'PHP',
    'status' => 'running'
]);
