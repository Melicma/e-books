<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/', function (Request $request, Response $response, array $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        return $response->withRedirect('/login');
    } else {
        return $response->withRedirect('/content');
    }
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
        $session->userId = $users[0]['UserID'];
        $session->userEmail = $body['email'];
        return $res->withRedirect('/content');
    }
});

$app->get('/content', function (Request $req, Response $res, array $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        return $this->view->render($res, 'login.twig', [
            'sessionError' => true
        ]);
    }

    $sqlWorks =
        'SELECT WorkID, Status, Year, Title '.
        'FROM '.
        ' works ';

    $sqlConnections =
        'SELECT '.
        ' AuthPubID as "id" '.
        'FROM '.
        ' connection '.
        'WHERE '.
        ' WorkID = ? '.
        'AND '.
        ' Type = "author"';

    $sqlAuthor =
        'SELECT * '.
        'FROM '.
        ' authors_publishers '.
        'WHERE '.
        ' ID = ? ';

    $sqlAuthors =
        'SELECT DISTINCT '.
        ' au.* '.
        'FROM '.
        ' authors_publishers au '.
        'LEFT JOIN connection con '.
        ' ON con.AuthPubId = au.ID  '.'
        WHERE '.
        ' con.Type = \'author\' '.
        'ORDER BY '.
        ' au.LastName';

    $sqlYears =
        'SELECT DISTINCT '.
        ' Year '.
        'FROM '.
        ' works '.
        'WHERE '.
        ' Year <> -1 '.
        'ORDER BY '.
        ' Year';

    $dbo = $this->db->prepare($sqlWorks);
    $dbo->execute();

    $works = $dbo->fetchAll();

    foreach ($works as $key => $work) {
        $dbo = $this->db->prepare($sqlConnections);
        $dbo->execute(array($work['WorkID']));
        $authorIds = $dbo->fetchAll();

        $works[$key]['Authors'] = array();
        $dbo = $this->db->prepare($sqlAuthor);
        foreach ($authorIds as $id) {
            $dbo->execute(array($id['id']));
            array_push($works[$key]['Authors'], $dbo->fetchAll());
        }
    }

    $dbo = $this->db->prepare($sqlAuthors);
    $dbo->execute();

    $authors = $dbo->fetchAll();

    $dbo = $this->db->prepare($sqlYears);
    $dbo->execute();

    $years = $dbo->fetchAll();


//    $key =  substr(md5(rand()), 0, 7);
//    $hash = bin2hex($key);
//    $key = hex2bin($hash);

// todo only for testing
//    $session->delete('userId');
//    $session::destroy();
    return $this->view->render($res, 'main.twig', [
        'user' => $session->userEmail,
        'works' => $works,
        'authors' => $authors,
        'years' => $years,
        'filterYearUnknown' => true,
        'filterNew' => true,
        'filterIncomplete' => true,
        'filterChecked' => true,
        'filterComplete' => true
    ]);
});

$app->post('/content', function (Request $req, Response $res) {
    $session = $this->session;
    //todo to future not render but redirect
    if (!$session->exists('userId')) {
        return $this->view->render($res, 'login.twig', [
            'sessionError' => true
        ]);
    }

    $sqlAuthors =
        'SELECT DISTINCT '.
        ' au.* '.
        'FROM '.
        ' authors_publishers au '.
        'LEFT JOIN connection con '.
        ' ON con.AuthPubId = au.ID '.
        'WHERE '.
        ' con.Type = \'author\' '.
        'ORDER BY '.
        ' au.LastName';

    $sqlAuthorsById =
        'SELECT DISTINCT '.
        ' au.* '.
        'FROM '.
        ' authors_publishers au '.
        'LEFT JOIN connection con '.
        ' ON con.AuthPubId = au.ID '.
        'WHERE '.
        ' con.Type = \'author\' '.
        'AND '.
        ' con.WorkID = ? '.
        'ORDER BY '.
        ' au.LastName';

    $sqlYears =
        'SELECT DISTINCT '.
        ' Year '.
        'FROM '.
        ' works '.
        'WHERE '.
        ' Year <> -1 '.
        'ORDER BY '.
        ' Year';

    $sqlWorks =
        'SELECT WorkID, Title, Year, Status '.
        'FROM '.
        ' works ';

    $sqlFulltextSearch =
        'SELECT '.
        ' WorkID '.
        'FROM '.
        ' worksIndex '.
        'WHERE '.
        ' worksIndex ';

    $dbo = $this->db->prepare($sqlYears);
    $dbo->execute();

    $years = $dbo->fetchAll();

    $dbo = $this->db->prepare($sqlAuthors);
    $dbo->execute();

    $authors = $dbo->fetchAll();

    $body = $req->getParsedBody();

    $fAuthors = isset($body['authors']) ? $body['authors'] : null;
    $fUnknownYear = isset($body['unknownYear']) ? $body['unknownYear'] : null;
    $fNew = isset($body['new']) ? $body['new'] : null;
    $fIncomplete = isset($body['incomplete']) ? $body['incomplete'] : null;
    $fChecked = isset($body['checked']) ? $body['checked'] : null;
    $fComplete = isset($body['complete']) ? $body['complete'] : null;

    if ($fAuthors || !$fUnknownYear || $fNew || $fIncomplete || $fChecked || $fComplete || $body['yearFrom'] != '--' || $body['yearTo'] != '--' || $body['fulltext']) {
        $sqlWorks .= 'WHERE ';
    } else {
        return $res->withRedirect('/content');
    }

    $otherFilter = false;
    $otherFilter2 = false;
    $leftBracketMissing = false;
    $params = array();
    // if date from is higher than date to
    if ((int)$body['yearTo'] < (int)$body['yearFrom'] && $body['yearTo'] != '--' && $body['yearFrom'] != '--') {
        return $this->view->render($res, 'main.twig', [
            'user' => $session->userEmail,
//        'works' => $works,
            'authors' => $authors,
            'years' => $years,
            'filterAuthors' => $fAuthors,
            'filterYearFrom' => $body['yearFrom'],
            'filterYearTo' => $body['yearTo'],
            'filterYearUnknown' => $fUnknownYear,
            'filterFulltext' => $body['fulltext'],
            'filterNew' => $fNew,
            'filterIncomplete' => $fIncomplete,
            'filterChecked' => $fChecked,
            'filterComplete' => $fComplete,
            'filterYearError' => true
        ]);
    }
    // filter works depends on year of publishing
    if ($body['yearFrom'] != '--') {
        if ($fUnknownYear) {
            $sqlWorks .= '(';
            $leftBracketMissing = true;
            if ($body['yearTo'] != '--') {
                $sqlWorks .= '(';
            }
        }
        $otherFilter = true;
        $sqlWorks .= 'Year >= ? ';
        array_push($params, $body['yearFrom']);
    }
    if ($body['yearTo'] != '--') {
        if ($otherFilter) {
            $sqlWorks .= ' AND Year <= ? ';
        } else {
            $sqlWorks .= ' Year <= ? ';
            $otherFilter = true;
        }
        array_push($params, $body['yearTo']);
    }
    if (!$fUnknownYear) {
        if (!$otherFilter) {
            // case Y <> -1
            if ($body['yearFrom'] == '--' && $body['yearTo'] == '--') {
                $otherFilter = true;
                $sqlWorks .= ' Year <> -1 ';
            }

        } else {
            //case Y < number AND Y <> -1
            if ($body['yearFrom'] == '--' && $body['yearTo'] != '--') {
                $sqlWorks .= ' AND Year <> -1 ';
            }
        }
    } else {
        //case (Y > number OR Y = -1)
        if ($leftBracketMissing && $body['yearTo'] == '--') {
            $sqlWorks .= ' OR Year = -1) ';
            $leftBracketMissing = false;
        }
        // (Y > number AND Y < number2) OR Y = -1
        if ($leftBracketMissing && $body['yearTo'] != '--' && $body['yearFrom'] != '--') {
            $sqlWorks .= ') OR Year = -1) ';
            $leftBracketMissing = false;
        }
    }

    if ($fNew || $fIncomplete || $fChecked || $fComplete) {
        if ($otherFilter) {
            $sqlWorks .= ' AND ( ';
        } else {
            $sqlWorks .= ' ( ';
        }

        if ($fNew) {
            $sqlWorks .= ' Status = \'nové\' ';
            $otherFilter2 = true;
        }

        if ($fIncomplete) {
            if ($otherFilter2) {
                $sqlWorks .= ' OR Status = \'rozděláno\' ';
            } else {
                $sqlWorks .= ' Status = \'rozděláno\' ';
                $otherFilter2 = true;
            }
        }

        if ($fChecked) {
            if ($otherFilter2) {
                $sqlWorks .= ' OR Status = \'zkontrolováno\' ';
            } else {
                $sqlWorks .= ' Status = \'zkontrolováno\' ';
                $otherFilter2 = true;
            }
        }

        if ($fComplete) {
            if ($otherFilter2) {
                $sqlWorks .= ' OR Status = \'hotovo\' ';
            } else {
                $sqlWorks .= ' Status = \'hotovo\' ';
                $otherFilter2 = true;
            }
        }

        $sqlWorks .= ' ) ';
    }

    $dbo = $this->db->prepare($sqlWorks);
    $dbo->execute($params);

    $works = $dbo->fetchAll();

    $workIds = array();
    $includeFulltextSearch = false;
    if ($body['fulltext']) {
        $sqlFulltextSearch .= ' MATCH \'Fulltext:' . $body['fulltext'] . ' OR Title:' . $body['fulltext'] . '\'';
        $dbo = $this->db->prepare($sqlFulltextSearch);
        $dbo->execute();
        $ids = $dbo->fetchAll();
        foreach ($ids as $el) {
            array_push($workIds, $el['WorkID']);
        }
        $includeFulltextSearch = true;
    }

    $worksOut = array();
    foreach ($works as $key => $work) {
        $dbo = $this->db->prepare($sqlAuthorsById);
        $dbo->execute(array($work['WorkID']));
        $workAuthors = $dbo->fetchAll();

        $works[$key]['Authors'] = array();
        $includeAuthor = false;
        foreach ($workAuthors as $id) {
            array_push($works[$key]['Authors'], array($id));
            if ($fAuthors && !$includeAuthor) {
                if (in_array($id['Name'] . ' ' . $id['LastName'], $fAuthors)) {
                    $includeAuthor = true;
                }
            }
        }

        if ($fAuthors) {
            if ($includeAuthor) {
                if ($includeFulltextSearch) {
                    if (in_array($work['WorkID'], $workIds)) {
                        array_push($worksOut, $works[$key]);
                    }
                } else {
                    array_push($worksOut, $works[$key]);
                }
            }
        } else {
            if ($includeFulltextSearch) {
                if (in_array($work['WorkID'], $workIds)) {
                    array_push($worksOut, $works[$key]);
                }
            }
        }
    }

    if ($fAuthors || $body['fulltext']) {
        $works = $worksOut;
    }

    return $this->view->render($res, 'main.twig', [
        'user' => $session->userEmail,
        'works' => $works,
        'authors' => $authors,
        'years' => $years,
        'filterAuthors' => $fAuthors,
        'filterYearFrom' => $body['yearFrom'],
        'filterYearTo' => $body['yearTo'],
        'filterYearUnknown' => $fUnknownYear,
        'filterFulltext' => $body['fulltext'],
        'filterNew' => $fNew,
        'filterIncomplete' => $fIncomplete,
        'filterChecked' => $fChecked,
        'filterComplete' => $fComplete
    ]);

});

$app->get('/logout', function (Request $req, Response $res, array $args) {
    $session = $this->session;
    $session->delete('userId');
    $session::destroy();

    return $res->withRedirect('/login');
});

$app->get('/change-password', function (Request $req, Response $res, array $args) use($app){
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    return $this->view->render($res, 'changePasswdord.twig', []);
});

$app->post('/change-password', function (Request $req, Response $res) {
    $session = $this->session;
    if (!$session->exists('userId')) {
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

    $dbo->execute(array($session->userId));
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

    $dbo->execute(array(hash('sha256', $newPassword.$salt) ,bin2hex($salt) , $session->userID));

    return $res->withRedirect('/content');
});

$app->get('/metadata/{id}', function (Request $req, Response $res, $args){
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    $sqlWork =
        'SELECT * '.
        'FROM '.
        ' works '.
        'WHERE '.
        ' WorkID = ?';

    $sqlAuthors =
        'SELECT DISTINCT '.
        ' au.* '.
        'FROM '.
        ' authors_publishers au '.
        'LEFT JOIN connection con '.
        ' ON con.AuthPubId = au.ID  '.'
        WHERE '.
        ' con.Type = \'author\' '.
        'AND '.
        ' con.WorkID = ? '.
        'ORDER BY '.
        ' au.LastName';


    // get information about work
    $dbo = $this->db->prepare($sqlWork);
    $dbo->execute(array($args['id']));
    $work = $dbo->fetchAll();

    $dbo = $this->db->prepare($sqlAuthors);
    $dbo->execute(array($args['id']));


    $work[0]['Authors'] = $dbo->fetchAll();


    return $this->view->render($res, 'metadata.twig', [
        'user' => $session->userEmail,
        'work' => $work[0]
    ]);
});