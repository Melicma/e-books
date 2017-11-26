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
    if (empty($users) || (hash('sha256', $body['password'].hex2bin($users[0]['Password2'])) !== $users[0]['Password'])) {
        return $this->view->render($res, 'login.twig', [
            'logError' => true
        ]);
    } else {
        // doing session staff
        $session = new \SlimSession\Helper;
        $session->ebooks = $users[0]['UserID'];
        return $res->withRedirect('/content');
    }
});

$app->get('/content', function (Request $req, Response $res, array $args) {
    $session = $this->session;
    if (!$session->exists('ebooks')) {
        return $this->view->render($res, 'login.twig', [
            'logError' => false
        ]);
    }

    $sql =
        'SELECT * '.
        'FROM '.
        ' users '.
        'WHERE '.
        ' UserId = ?';

    $dbo = $this->db->prepare($sql);

    $dbo->execute(array($session->ebooks));
    $user = $dbo->fetchAll();


//    $key =  substr(md5(rand()), 0, 7);
//    $hash = bin2hex($key);
//    $key = hex2bin($hash);


    $session->delete('ebooks');
    $session::destroy();
    return $this->view->render($res, 'main.twig', [
        'user' => $user[0]
    ]);

});

$app->get('/logout', function (Request $req, Response $res, array $args) {
    $session = $this->session;
    $session->delete('ebooks');
    $session::destroy();

    return $res->withRedirect('/login');
});