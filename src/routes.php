<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/', function (Request $request, Response $response, array $args) {
    return $response->withRedirect('/login');
});

$app->get('/login', function (Request $req, Response $res, array $args) {
    return $this->view->render($res, 'login.twig', [
        'logError' => false
    ]);
});

$app->post('/login', function (Request $req, Response $res) {
    $body = $req->getParsedBody();

    $sql =
        'SELECT * '.
        'FROM '.
        ' users '.
        'WHERE '.
        ' UserEmail = ?';

    $dbo = $this->db->prepare($sql);

    $dbo->execute(array($body['email']));

    $users = $dbo->fetchAll();
    if (empty($users) || ($body['password'] !== $users[0]['Password'])) {
        return $this->view->render($res, 'login.twig', [
            'logError' => true
        ]);
    } else {
        return $this->view->render($res, 'main.twig', []);
    }
});
