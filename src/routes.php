<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/', function (Request $request, Response $response, array $args) {
    return $response->withRedirect('/login');
});

$app->get('/login', function (Request $request, Response $response, array $args) {
    return $this->view->render($response, 'login.twig', [
        
    ]);
});

$app->post('/login', function (Request $req, Response $res) {
    $dbo = $this->db->query('SELECT * FROM users');
    $dbo->execute();
    $users = $dbo->fetchAll();
    $body = $req->getParsedBody();
//    echo $body[password];
    $res = json_encode($body);
});
