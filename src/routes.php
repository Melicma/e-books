<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

//$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
//    // Sample log message
//    $this->logger->info("Slim-Skeleton '/' route");
//
//    // Render index view
//    return $this->renderer->render($response, 'index.phtml', $args);
//});

$app->get('/login', function (Request $request, Response $response, array $args) {

    $users = $this->db->query('SELECT * FROM users');
    if ($users->execute()) {
        // on success
        $data = $users->fetch()["UserEmail"];
        print_r($data);
        $response = $this->renderer->render($response, "login.phtml", ["data" => $data]);
        return $response;
    } else {
        $data = null;
        return $response;
    }
});