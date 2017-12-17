<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/', function (Request $request, Response $response, array $args) {
    return $response->withRedirect('/login');
});

$app->get('/login', function (Request $req, Response $res, array $args) {
    $tmp = false;
    if ($req->getParam('sessionError')) {
        $tmp = true;
    }
    return $this->view->render($res, 'login.twig', [
        'sessionError' => $tmp
    ]);
})->setName('login');

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
            'sessionError' => true
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

// todo only for testing
//    $session->delete('ebooks');
//    $session::destroy();
    return $this->view->render($res, 'main.twig', [
        'user' => $user[0]
    ]);
});

$app->post('/content', function (Request $req, Response $res) {
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

$app->get('/logout', function (Request $req, Response $res, array $args) {
    $session = $this->session;
    $session->delete('ebooks');
    $session::destroy();

    return $res->withRedirect('/login');
});

$app->get('/change-password', function (Request $req, Response $res, array $args) use($app){
    $session = $this->session;
    if (!$session->exists('ebooks')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    return $this->view->render($res, 'changePasswdord.twig', []);
});

$app->post('/change-password', function (Request $req, Response $res) {
    $session = $this->session;
    if (!$session->exists('ebooks')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login', [], $data));
    }

    $sql =
        'SELECT * ' .
        'FROM ' .
        ' users ' .
        'WHERE ' .
        ' UserId = ?';

    $dbo = $this->db->prepare($sql);

    $dbo->execute(array($session->ebooks));
    $user = $dbo->fetchAll();
    $body = $req->getParsedBody();

    if (empty($user) || (hash('sha256', $body['oldPassword'].hex2bin($user[0]['Password2'])) !== $user[0]['Password'])) {
        return $this->view->render($res, 'changePasswdord.twig', [
            'oldPasswdError' => true
        ]);
    }

    if ($body['password1'] != $body['password2']) {
        return $this->view->render($res, 'changePasswdord.twig', [
            'passwdNotMatchError' => true
        ]);
    }

    $newPassword = $body['password1'];

    $salt =  substr(md5(rand()), 0, 7);
//    $salt = bin2hex($salt);

    print_r(hash('sha256', $newPassword.$salt));
    print_r(' '.bin2hex($salt));

    $sql =
        'UPDATE ' .
        ' users ' .
        'SET Password = ?, Password2 = ? ' .
        'WHERE ' .
        ' UserId = ?';

    $dbo = $this->db->prepare($sql);

    $dbo->execute(array(hash('sha256', $newPassword.$salt) ,bin2hex($salt) , $session->ebooks));

    return $res->withRedirect('/content');
});