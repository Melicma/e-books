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
    if (empty($users) || (hash('sha256', $body['password'].hex2bin($users[0]['Salt'])) !== $users[0]['Password'])) {
        return $this->view->render($res, 'login.twig', [
            'logError' => true
        ]);
    } else {
        // doing session staff
        $session = new \SlimSession\Helper;
        $session->userId = $users[0]['UserID'];
        $session->userEmail = $body['email'];
        $session->role = $users[0]['Role'];
        return $res->withRedirect('/content');
    }
});

$app->get('/content', function (Request $req, Response $res, array $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
//        return $this->view->render($res, 'login.twig', [
//            'sessionError' => true
//        ]);
    }

    $sqlWorks =
        'SELECT WorkID, Status, Year, Title '.
        'FROM '.
        ' works ';

    $sqlConnections =
        'SELECT '.
        ' AuthPubID as "id" '.
        'FROM '.
        ' author_work '.
        'WHERE '.
        ' WorkID = ? '.
        'AND '.
        ' Type = \'author\' ';

    $sqlAuthor =
        'SELECT * '.
        'FROM '.
        ' authors_publishers '.
        'WHERE '.
        ' AuthPubID = ? ';

    $sqlAuthors =
        'SELECT DISTINCT '.
        ' au.* '.
        'FROM '.
        ' authors_publishers au '.
        'LEFT JOIN author_work con '.
        ' ON con.AuthPubId = au.AuthPubID  '.'
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

    $sqlCountAttachments =
        'SELECT '.
        ' COUNT(*) as \'count\''.
        'FROM '.
        ' attachments '.
        'WHERE '.
        ' WorkID = ?';

    $dbo = $this->db->prepare($sqlWorks);
    $dbo->execute();

    $works = $dbo->fetchAll();

    foreach ($works as $key => $work) {
        $dbo = $this->db->prepare($sqlCountAttachments);
        $dbo->execute(array($work['WorkID']));
        $works[$key]['countAttachments'] = $dbo->fetch();
        
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
        'role' => $session->role,
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
        'LEFT JOIN author_work con '.
        ' ON con.AuthPubId = au.AuthPubID '.
        'WHERE '.
        ' con.Type = \'author\' '.
        'ORDER BY '.
        ' au.LastName';

    $sqlAuthorsById =
        'SELECT DISTINCT '.
        ' au.* '.
        'FROM '.
        ' authors_publishers au '.
        'LEFT JOIN author_work con '.
        ' ON con.AuthPubId = au.AuthPubID '.
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
            'role' => $session->role,
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
                if (in_array($id['Name'] . ' ' . $id['LastName'] . ' ' . $id['Corporation'], $fAuthors)) {
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
        'role' => $session->role,
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

    return $this->view->render($res, 'changePasswdord.twig', [
        'user' => $session->userEmail,
        'role' => $session->role
    ]);
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

    if (empty($user) || (hash('sha256', $body['oldPassword'].hex2bin($user[0]['Salt'])) !== $user[0]['Password'])) {
        return $this->view->render($res, 'changePasswdord.twig', [
            'oldPasswdError' => true,
            'user' => $session->userEmail,
            'role' => $session->role
        ]);
    } elseif (empty($body['password1']) || $body['password1'] != $body['password2']) {
        return $this->view->render($res, 'changePasswdord.twig', [
            'newPasswdError' => true,
            'user' => $session->userEmail,
            'role' => $session->role
        ]);
    } else {


        $newPassword = $body['password1'];

        $salt =  substr(md5(rand()), 0, 7);
//        $salt = bin2hex($salt);

        print_r(hash('sha256', $newPassword.$salt));
        print_r(' '.bin2hex($salt));

        $sql =
            'UPDATE ' .
            ' users ' .
            'SET Password = ?, Salt = ? ' .
            'WHERE ' .
            ' UserId = ?';

        $dbo = $this->db->prepare($sql);

        $dbo->execute(array(hash('sha256', $newPassword.$salt) ,bin2hex($salt) , $session->userID));

        return $res->withRedirect('/content');
    }

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
        'LEFT JOIN author_work con '.
        ' ON con.AuthPubId = au.AuthPubID  '.'
        WHERE '.
        ' con.Type = \'author\' '.
        'AND '.
        ' con.WorkID = ? '.
        'ORDER BY '.
        ' au.LastName';

    $sqlPublisher =
        'SELECT DISTINCT '.
        ' au.* '.
        'FROM '.
        ' authors_publishers au '.
        'LEFT JOIN author_work con '.
        ' ON con.AuthPubId = au.AuthPubID  '.'
        WHERE '.
        ' con.Type = \'publisher\' '.
        'AND '.
        ' con.WorkID = ? '.
        'ORDER BY '.
        ' au.Name';
    
//    $sqlAllAuthors =
//        'SELECT DISTINCT au.* '.
//        'FROM '.
//        ' authors_publishers au '.
//        'LEFT JOIN '.
//        ' connection c '.
//        'ON '.
//        ' au.ID = c.AuthPubID '.
//        'WHERE '.
//        ' c.Type = \'author\' ';
//
//    $sqlAllPublisher =
//        'SELECT DISTINCT au.* '.
//        'FROM '.
//        ' authors_publishers au '.
//        'LEFT JOIN '.
//        ' connection c '.
//        'ON '.
//        ' au.ID = c.AuthPubID '.
//        'WHERE '.
//        ' c.Type = \'publisher\' ';

    $sqlAll =
        'SELECT DISTINCT au.* '.
        'FROM '.
        ' authors_publishers au ';

    // get information about work
    $dbo = $this->db->prepare($sqlWork);
    $dbo->execute(array($args['id']));
    $work = $dbo->fetchAll();

    $dbo = $this->db->prepare($sqlAuthors);
    $dbo->execute(array($args['id']));

    $tmpAuthors = $dbo->fetchAll();

    $work[0]['Authors'] = array();
    
    foreach ($tmpAuthors as $el) {
        array_push($work[0]['Authors'], $el['Name'] . ' ' . $el['LastName'] . ' ' . $el['Corporation']);
    }
    
//    $dbo = $this->db->prepare($sqlAllAuthors);
//    $dbo->execute();
//
//    $authors = $dbo->fetchAll();
    
    $dbo = $this->db->prepare($sqlPublisher);
    $dbo->execute(array($args['id']));

    $tmpPublishers = $dbo->fetchAll();

    $work[0]['Publisher'] = array();

    foreach ($tmpPublishers as $el) {
        array_push($work[0]['Publisher'], $el['Name'] . ' ' . $el['LastName'] . ' ' . $el['Corporation']);
    }

//    $dbo = $this->db->prepare($sqlAllPublisher);
//    $dbo->execute();
//
//    $publishers = $dbo->fetchAll();

    $dbo = $this->db->prepare($sqlAll);
    $dbo->execute();

    $elements = $dbo->fetchAll();

    return $this->view->render($res, 'metadata.twig', [
        'user' => $session->userEmail,
        'role' => $session->role,
        'work' => $work[0],
//        'authors' => $authors,
//        'publishers' => $publishers,
        'elements' => $elements
    ]);
});

$app->post('/metadata/{id}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login', [], $data));
    }

    $sqlUpdate =
        'UPDATE '.
        ' works '.
        'SET '.
        ' Title = ?, Subtitle = ?, Year = ?, Place = ?, '.
        ' Edition = ?, Pages = ?, Inscription = ?, Motto = ?, '.
        ' MottoAuthor = ?, Format = ?, Signature = ?, Description = ?, EditNote = ? '.
        'WHERE '.
        ' WorkID = ?';

    $body = $req->getParsedBody();

    $uTitle = isset($body['title']) ? $body['title'] : null;
    $uSubtitle = isset($body['subtitle']) ? $body['subtitle'] : null;
    $uYear = isset($body['year']) ? $body['year'] : null;
    $uPlace = isset($body['place']) ? $body['place'] : null;
    $uEdition = isset($body['edition']) ? $body['edition'] : null;
    $uPages = isset($body['pages']) ? $body['pages'] : null;
    $uInscription = isset($body['inscription']) ? $body['inscription'] : null;
    $uMotto = isset($body['motto']) ? $body['motto'] : null;
    $uMottoAuthor = isset($body['mottoAuthor']) ? $body['mottoAuthor'] : null;
    $uFormat = isset($body['format']) ? $body['format'] : null;
    $uSignature = isset($body['signature']) ? $body['signature'] : null;
    $uDescription = isset($body['description']) ? $body['description'] : null;
    $uEditNote = isset($body['editNote']) ? $body['editNote'] : null;

    $params = array();
    array_push($params, $uTitle);
    array_push($params, $uSubtitle);
    array_push($params, $uYear);
    array_push($params, $uPlace);
    array_push($params, $uEdition);
    array_push($params, $uPages);
    array_push($params, $uInscription);
    array_push($params, $uMotto);
    array_push($params, $uMottoAuthor);
    array_push($params, $uFormat);
    array_push($params, $uSignature);
    array_push($params, $uDescription);
    array_push($params, $uEditNote);
    array_push($params, $args['id']);


    $dbo = $this->db->prepare($sqlUpdate);
    $dbo->execute($params);

    return $res->withRedirect('/metadata/' . $args['id']);
});



$app->get('/author-publisher/{id}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    $sqlAuthor =
        'SELECT * '.
        'FROM '.
        ' authors_publishers '.
        'WHERE '.
        ' AuthPubID = ?';

    $sqlAuthors =
        'SELECT '.
        ' AuthPubID, Name, LastName, Corporation  '.
        'FROM '.
        ' authors_publishers '.
        'WHERE '.
        ' Author IS NULL';

    $dbo = $this->db->prepare($sqlAuthors);
    $dbo->execute(array());
    $realAuthors = $dbo->fetchAll();

    $dbo = $this->db->prepare($sqlAuthor);
    $dbo->execute(array($args['id']));
    $element = $dbo->fetch();

    $tmpReal = '';
    if ($element['Author']) {
        $dbo->execute(array($element['Author']));
        $tmp = $dbo->fetch();
        $tmpReal = $tmp['Name'] . ' ' . $tmp['LastName'] . ' ' . $tmp['Corporation'];
    }


    return $this->view->render($res, 'authorPublisher.twig', [
        'user' => $session->userEmail,
        'role' => $session->role,
        'element' => $element,
        'realElements' => $realAuthors,
        'pseudonymReal' => $tmpReal
    ]);
});


$app->post('/author-publisher/{id}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login', [], $data));
    }

    $sqlUpdate =
        'UPDATE '.
        ' authors_publishers '.
        'SET '.
        ' Name = ?, LastName = ?, Corporation = ?, Author = ? '.
        'WHERE '.
        ' AuthPubID = ?';

    $body = $req->getParsedBody();

    $uName = isset($body['name']) ? $body['name'] : null;
    $uLastName = isset($body['lastName']) ? $body['lastName'] : null;
    $uCorporation = isset($body['corporation']) ? $body['corporation'] : null;

    if ($body['pseudonym'] == 0) {
        $uAuthor = null;
    } else {
        $uAuthor = $body['pseudonym'];
    }

    $params = array();
    array_push($params, $uName);
    array_push($params, $uLastName);
    array_push($params, $uCorporation);
    array_push($params, $uAuthor);
    array_push($params, $args['id']);


    $dbo = $this->db->prepare($sqlUpdate);
    $dbo->execute($params);

    return $res->withRedirect('/author-publisher/' . $args['id']);
});

$app->get('/delete-author-publisher/{id}', function (Request $req, Response $res, $args) {

    $sqlDeleteConnection =
        'DELETE '.
        'FROM '.
        ' author_work '.
        'WHERE '.
        ' AuthPubID = ? ';

    $sqlDelete =
        'DELETE '.
        'FROM '.
        ' authors_publishers '.
        'WHERE '.
        ' AuthPubID = ?';

    $sqlAuthor =
        'SELECT '.
        ' AuthPubID '.
        'FROM '.
        ' authors_publishers '.
        'WHERE '.
        ' Author = ?';

    $params = array();
    array_push($params, $args['id']);

    $dbo = $this->db->prepare($sqlAuthor);
    $dbo->execute($params);
    $id = $dbo->fetch()['AuthPubID'];
    if ($id) {
        $dbo = $this->db->prepare($sqlDeleteConnection);
        $dbo->execute(array($id));

        $dbo = $this->db->prepare($sqlDelete);
        $dbo->execute(array($id));
    }

    $dbo = $this->db->prepare($sqlDeleteConnection);
    $dbo->execute($params);

    $dbo = $this->db->prepare($sqlDelete);
    $dbo->execute($params);

    return $res->withRedirect('/list-author-publisher');
});

//$app->get('/author/{id}', function (Request $req, Response $res, $args) {
//    $session = $this->session;
//    if (!$session->exists('userId')) {
//        $data = ['sessionError' => true];
//        return $res->withRedirect($this->router->pathFor('login',[],$data));
//    }
//
//    $sqlAuthor =
//        'SELECT * '.
//        'FROM '.
//        ' authors_publishers '.
//        'WHERE '.
//        ' ID = ?';
//
//
//
//    $dbo = $this->db->prepare($sqlAuthor);
//    $dbo->execute(array($args['id']));
//    $author = $dbo->fetch();
//
//
//    return $this->view->render($res, 'authorPublisher.twig', [
//        'user' => $session->userEmail,
//        'author' => $author,
//        'isAuthor' => true
//    ]);
//});

//$app->post('/author/{id}', function (Request $req, Response $res, $args) {
//    $session = $this->session;
//    if (!$session->exists('userId')) {
//        $data = ['sessionError' => true];
//        return $res->withRedirect($this->router->pathFor('login', [], $data));
//    }
//
//    $sqlUpdate =
//        'UPDATE '.
//        ' authors_publishers '.
//        'SET '.
//        ' Name = ?, LastName = ?, Corporation = ? '.
//        'WHERE '.
//        ' ID = ?';
//
//    $body = $req->getParsedBody();
//
//    $uName = isset($body['name']) ? $body['name'] : null;
//    $uLastName = isset($body['lastName']) ? $body['lastName'] : null;
//    $uCorporation = isset($body['corporation']) ? $body['corporation'] : null;
//
//    $params = array();
//    array_push($params, $uName);
//    array_push($params, $uLastName);
//    array_push($params, $uCorporation);
//    array_push($params, $args['id']);
//
//
//    $dbo = $this->db->prepare($sqlUpdate);
//    $dbo->execute($params);
//
//    return $res->withRedirect('/author/' . $args['id']);
//});

$app->get('/new-author/{workId}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    $sqlAuthors =
        'SELECT '.
        ' AuthPubID, Name, LastName, Corporation  '.
        'FROM '.
        ' authors_publishers '.
        'WHERE '.
        ' Author IS NULL';

    $dbo = $this->db->prepare($sqlAuthors);
    $dbo->execute(array());
    $realAuthors = $dbo->fetchAll();

    $tmpReal = '';

    return $this->view->render($res, 'authorPublisher.twig', [
        'user' => $session->userEmail,
        'role' => $session->role,
        'author' => null,
        'isAuthor' => true,
        'newWorkID' => $args['workId'],
        'realElements' => $realAuthors,
        'pseudonymReal' => $tmpReal
    ]);
});

$app->post('/new-author/{workId}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login', [], $data));
    }

    $sqlInsert =
        'INSERT '.'INTO '.
        ' authors_publishers '.
        ' (Name, LastName, Corporation, Author) '.
        'VALUES '.
        ' (?, ?, ?, ?) ';

    $sqlGetId =
        'SELECT '.
        ' last_insert_rowid() as "id"';

    $sqlInsertConnection =
        'INSERT '.'INTO '.
        ' author_work '.
        ' (WorkID, AuthPubID, Type) '.
        'VALUES '.
        ' (?, ?, ?)';

    $body = $req->getParsedBody();

    $uName = isset($body['name']) ? $body['name'] : null;
    $uLastName = isset($body['lastName']) ? $body['lastName'] : null;
    $uCorporation = isset($body['corporation']) ? $body['corporation'] : null;
    
    if ($body['pseudonym'] == 0) {
        $uAuthor = null;
    } else {
        $uAuthor = $body['pseudonym'];
    }
    
    $params = array();
    array_push($params, $uName);
    array_push($params, $uLastName);
    array_push($params, $uCorporation);
    array_push($params, $uAuthor);


    $dbo = $this->db->prepare($sqlInsert);
    $dbo->execute($params);

    $dbo = $this->db->prepare($sqlGetId);
    $dbo->execute();

    $authId = $dbo->fetch()['id'];

    $params = array();
    array_push($params, $args['workId']);
    array_push($params, $authId);
    array_push($params, 'author');

    $dbo = $this->db->prepare($sqlInsertConnection);
    $dbo->execute($params);

    return $res->withRedirect('/metadata/' . $args['workId']);
});



$app->get('/new-author-publisher', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    $sqlAuthors =
        'SELECT '.
        ' AuthPubID, Name, LastName, Corporation  '.
        'FROM '.
        ' authors_publishers '.
        'WHERE '.
        ' Author IS NULL';

    $dbo = $this->db->prepare($sqlAuthors);
    $dbo->execute(array());
    $realAuthors = $dbo->fetchAll();

    $tmpReal = '';

    return $this->view->render($res, 'authorPublisher.twig', [
        'user' => $session->userEmail,
        'role' => $session->role,
        'author' => null,
        'newWorkID' => true,
        'realElements' => $realAuthors,
        'pseudonymReal' => $tmpReal
    ]);
});

$app->post('/new-author-publisher', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login', [], $data));
    }

    $sqlInsert =
        'INSERT '.'INTO '.
        ' authors_publishers '.
        ' (Name, LastName, Corporation, Author) '.
        'VALUES '.
        ' (?, ?, ?, ?) ';

    $body = $req->getParsedBody();

    $uName = isset($body['name']) ? $body['name'] : null;
    $uLastName = isset($body['lastName']) ? $body['lastName'] : null;
    $uCorporation = isset($body['corporation']) ? $body['corporation'] : null;

    if ($body['pseudonym'] == 0) {
        $uAuthor = null;
    } else {
        $uAuthor = $body['pseudonym'];
    }

    $params = array();
    array_push($params, $uName);
    array_push($params, $uLastName);
    array_push($params, $uCorporation);
    array_push($params, $uAuthor);


    $dbo = $this->db->prepare($sqlInsert);
    $dbo->execute($params);

    return $res->withRedirect('/list-author-publisher');
});

$app->get('/new-publisher/{workId}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    $sqlAuthors =
        'SELECT '.
        ' AuthPubID, Name, LastName, Corporation  '.
        'FROM '.
        ' authors_publishers '.
        'WHERE '.
        ' Author IS NULL';

    $dbo = $this->db->prepare($sqlAuthors);
    $dbo->execute(array());
    $realAuthors = $dbo->fetchAll();

    $tmpReal = '';

    return $this->view->render($res, 'authorPublisher.twig', [
        'user' => $session->userEmail,
        'role' => $session->role,
        'author' => null,
        'isAuthor' => false,
        'isPublisher' => true,
        'newWorkID' => $args['workId'],
        'realElements' => $realAuthors,
        'pseudonymReal' => $tmpReal
    ]);
});

$app->post('/new-publisher/{workId}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login', [], $data));
    }

    $sqlInsert =
        'INSERT '.'INTO '.
        ' authors_publishers '.
        ' (Name, LastName, Corporation, Author) '.
        'VALUES '.
        ' (?, ?, ?, ?) ';

    $sqlGetId =
        'SELECT '.
        ' last_insert_rowid() as "id"';

    $sqlInsertConnection =
        'INSERT '.'INTO '.
        ' author_work '.
        ' (WorkID, AuthPubID, Type) '.
        'VALUES '.
        ' (?, ?, ?)';

    $body = $req->getParsedBody();

    $uName = isset($body['name']) ? $body['name'] : null;
    $uLastName = isset($body['lastName']) ? $body['lastName'] : null;
    $uCorporation = isset($body['corporation']) ? $body['corporation'] : null;

    if ($body['pseudonym'] == 0) {
        $uAuthor = null;
    } else {
        $uAuthor = $body['pseudonym'];
    }

    $params = array();
    array_push($params, $uName);
    array_push($params, $uLastName);
    array_push($params, $uCorporation);
    array_push($params, $uAuthor);


    $dbo = $this->db->prepare($sqlInsert);
    $dbo->execute($params);

    $dbo = $this->db->prepare($sqlGetId);
    $dbo->execute();

    $authId = $dbo->fetch()['id'];

    $params = array();
    array_push($params, $args['workId']);
    array_push($params, $authId);
    array_push($params, 'publisher');

    $dbo = $this->db->prepare($sqlInsertConnection);
    $dbo->execute($params);

    return $res->withRedirect('/metadata/' . $args['workId']);
});

$app->get('/attachments/{workId}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    $sqlWork =
        'SELECT Title '.
        'FROM '.
        ' works '.
        'WHERE '.
        ' WorkID = ?';

    $sqlAttachments =
        'SELECT '.
        ' Filename, Identifier, AttachmentID '.
        'FROM '.
        ' attachments '.
        'WHERE '.
        ' WorkID = ?';

    $params = array();
    array_push($params, $args['workId']);

    $dbo = $this->db->prepare($sqlWork);
    $dbo->execute($params);

    $work = $dbo->fetch();
    $work['WorkID'] = $args['workId'];

    $dbo = $this->db->prepare($sqlAttachments);
    $dbo->execute($params);

    $attachmentsOut = array();
    $attachments = $dbo->fetchAll();

    foreach ($attachments as $el) {
        $tmp['Filename'] = $el['Filename'];
        $tmp['Identifier'] = $el['Identifier'];
        $tmp['ID'] = $el['AttachmentID'];
        $tmp['ThumbName'] = preg_replace('/(\.gif|\.jpg|\.png)/', '_small$1', $el['Filename']);
        array_push($attachmentsOut, $tmp);
    }

    return $this->view->render($res, 'attachments.twig', [
        'user' => $session->userEmail,
        'role' => $session->role,
        'work' => $work,
        'attachments' => $attachmentsOut
    ]);
});


$app->post('/attachments/{workId}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login', [], $data));
    }

    $sqlGetLastCounter =
        'SELECT '.
        ' COUNT(*) as count '.
        'FROM '.
        ' attachments '.
        'WHERE '.
        ' WorkID = ?';

    $sqlInsertAttach =
        'INSERT '.'INTO '.
        ' attachments '.
        ' (WorkID, Filename) '.
        'VALUES '.
        ' (?, ?)';

    $dbo = $this->db->prepare($sqlGetLastCounter);
    $dbo->execute(array($args['workId']));

    $counter = $dbo->fetch()['count'];

    if ($counter > 0) {
        $counter++;
    } else {
        $counter = 1;
    }

    $path = __DIR__.'/../public/images/' . str_pad($args['workId'], 5, '0', STR_PAD_LEFT) . '/';
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }

    array_multisort($_FILES['files']['name'], SORT_ASC, SORT_STRING, $_FILES['files']['type'], $_FILES['files']['tmp_name'], $_FILES['files']['error'], $_FILES['files']['size']);

    extract($_POST);
    $error=array();
    $extension=array("jpeg","jpg","png","gif");

    $dbo = $this->db->prepare($sqlInsertAttach);

    foreach($_FILES["files"]["tmp_name"] as $key=>$tmp_name) {
        $file_name=$_FILES["files"]["name"][$key];
        $file_tmp=$_FILES["files"]["tmp_name"][$key];
        $ext=pathinfo($file_name, PATHINFO_EXTENSION);
        $filenameNew = str_pad($counter, 3, '0', STR_PAD_LEFT). '.'.$ext;
        $filenameNewSmall = preg_replace('/(\.gif|\.jpg|\.png)/', '_small$1', $filenameNew);
        if(in_array($ext,$extension)) {
//            if(!file_exists($path.$file_name)) {
            $dbo->execute(array($args['workId'], $filenameNew));
            //do resize 68x100
            move_uploaded_file($file_tmp=$_FILES["files"]["tmp_name"][$key], $path.$filenameNew);
            smart_resize_image($path.$filenameNew, null, 68, 100, false, $path.$filenameNewSmall, false, false);
//            }
//            else {
//                $filename=basename($file_name,$ext);
//                $newFileName=$filename.time().".".$ext;
//                move_uploaded_file($file_tmp=$_FILES["files"]["tmp_name"][$key],$path.$newFileName);
//            }
        }
        else {
            array_push($error,"$file_name, ");
        }
        $counter++;
    }

    return $res->withRedirect('/attachments/' . $args['workId']);
});

$app->get('/delete-attachment/{attchId}/{workId}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    $sqlAttach =
        'SELECT '.
        ' Filename '.
        'FROM '.
        ' attachments '.
        'WHERE '.
        ' AttachmentID = ?';

    $sqlDelete =
        'DELETE '.
        'FROM '.
        ' attachments '.
        'WHERE '.
        ' AttachmentID = ?';

    $dbo = $this->db->prepare($sqlAttach);
    $dbo->execute(array($args['attchId']));

    $fileName = $dbo->fetch();

    $dbo = $this->db->prepare($sqlDelete);
    $dbo->execute(array($args['attchId']));

    $smallFilename = preg_replace('/(\.gif|\.jpg|\.png)/', '_small$1', $fileName['Filename']);
    unlink(__DIR__.'/../public/images/' . str_pad($args['workId'], 5, '0', STR_PAD_LEFT) . '/' . $fileName['Filename']);
    unlink(__DIR__.'/../public/images/' . str_pad($args['workId'], 5, '0', STR_PAD_LEFT) . '/' . $smallFilename);

    return $res->withRedirect('/attachments/' . $args['workId']);
});

$app->post('/update-attachment/{attchId}/{workId}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    $body = $req->getParsedBody();

    $sqlUpdate =
        'UPDATE '.
        ' attachments '.
        'SET '.
        ' Identifier = ? '.
        'WHERE '.
        ' AttachmentID = ?';

    $dbo = $this->db->prepare($sqlUpdate);
    $params = array();
    array_push($params, $body['identifier']);
    array_push($params, $args['attchId']);

    $dbo->execute($params);

    return $res->withRedirect('/attachments/' . $args['workId']);
});

$app->get('/text/{workId}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    $sqlWorks =
        'SELECT Title, Content '.
        'FROM '.
        ' works '.
        'WHERE '.
        ' WorkID = ?';

    $params = array();
    array_push($params, $args['workId']);

    $dbo = $this->db->prepare($sqlWorks);
    $dbo->execute($params);

    $work = $dbo->fetch();
    $work['WorkID'] = $args['workId'];
    
    return $this->view->render($res, 'text.twig', [
        'user' => $session->userEmail,
        'role' => $session->role,
        'work' => $work
    ]);
});


$app->get('/list-author-publisher', function (Request $req, Response $res) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    $sql =
        'SELECT DISTINCT '.
        ' au.*, (select count(c.AuthPubID) from author_work ) as number '.
        'FROM '.
        ' authors_publishers au LEFT JOIN author_work c ON c.AuthPubID = au.AuthPubID '.
	    'GROUP BY '.
        ' au.AuthPubID';

    $dbo = $this->db->prepare($sql);
    $dbo->execute();

    $elements = $dbo->fetchAll();

    return $this->view->render($res, 'listAuthorPublisher.twig', [
        'user' => $session->userEmail,
        'role' => $session->role,
        'elements' => $elements
    ]);
});

$app->post('/update-authors/{workId}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    $body = $req->getParsedBody();

    $sqlAllAuthors =
        'SELECT (COALESCE(au.Name, \'\') || \' \' || COALESCE(au.LastName, \'\') || \' \' || COALESCE(au.Corporation, \'\')) as \'name\' '.
        'FROM '.
        ' authors_publishers au '.
        'LEFT JOIN '.
        ' author_work c '.
        'ON '.
        ' au.AuthPubID = c.AuthPubID '.
        'WHERE '.
        ' c.Type = \'author\' '.
        'AND '.
        ' c.WorkID = ? ';

    $sqlGetAuthorId =
        'SELECT '.
        ' AuthPubID '.
        'FROM '.
        ' authors_publishers '.
        'WHERE '.
        ' (COALESCE(Name, \'\') || \' \' || COALESCE(LastName, \'\') || \' \' || COALESCE(Corporation, \'\')) = ? ';

    $sqlCreateConnection =
        'INSERT '.'INTO '.
        ' author_work '.
        ' (WorkID, AuthPubID, Type) '.
        'VALUES (?, ?, ?)';

    $sqlCancelConnection =
        'DELETE '.
        'FROM '.
        ' author_work '.
        'WHERE '.
        ' WorkID = ? AND AuthPubID = ? AND Type = \'author\'';

    $dbo = $this->db->prepare($sqlAllAuthors);
    $dbo->execute(array($args['workId']));

    $conAuthors = $dbo->fetchAll(PDO::FETCH_COLUMN);

    foreach ($body['authors'] as $el) {
        // add new connection
        if (!in_array($el, $conAuthors)) {
            $dbo = $this->db->prepare($sqlGetAuthorId);
            $dbo->execute(array($el));

            $id = $dbo->fetch()['AuthPubID'];

            $dbo = $this->db->prepare($sqlCreateConnection);
            $params = array();
            array_push($params, $args['workId']);
            array_push($params, $id);
            array_push($params, 'author');
            $dbo->execute($params);
        } else {
            unset($conAuthors[array_search($el, $conAuthors)]);
        }
    }

    foreach ($conAuthors as $el) {
        $dbo = $this->db->prepare($sqlGetAuthorId);
        $dbo->execute(array($el));

        $id = $dbo->fetch()['AuthPubID'];
        $params = array();
        array_push($params, $args['workId']);
        array_push($params, $id);

        $dbo = $this->db->prepare($sqlCancelConnection);
        $dbo->execute($params);
    }

    return $res->withRedirect('/metadata/' . $args['workId']);
});

$app->post('/update-publishers/{workId}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    $body = $req->getParsedBody();

    $sqlAllPublishers =
        'SELECT (COALESCE(au.Name, \'\') || \' \' || COALESCE(au.LastName, \'\') || \' \' || COALESCE(au.Corporation, \'\')) as \'name\' '.
        'FROM '.
        ' authors_publishers au '.
        'LEFT JOIN '.
        ' author_work c '.
        'ON '.
        ' au.AuthPubID = c.AuthPubID '.
        'WHERE '.
        ' c.Type = \'publisher\' '.
        'AND '.
        ' c.WorkID = ? ';

    $sqlGetPublisherId =
        'SELECT '.
        ' AuthPubID '.
        'FROM '.
        ' authors_publishers '.
        'WHERE '.
        ' (COALESCE(Name, \'\') || \' \' || COALESCE(LastName, \'\') || \' \' || COALESCE(Corporation, \'\')) = ? ';

    $sqlCreateConnection =
        'INSERT '.'INTO '.
        ' author_work '.
        ' (WorkID, AuthPubID, Type) '.
        'VALUES (?, ?, ?)';

    $sqlCancelConnection =
        'DELETE '.
        'FROM '.
        ' author_work '.
        'WHERE '.
        ' WorkID = ? AND AuthPubID = ? AND Type = \'publisher\'';

    $dbo = $this->db->prepare($sqlAllPublishers);
    $dbo->execute(array($args['workId']));

    $conPublishers = $dbo->fetchAll(PDO::FETCH_COLUMN);

    foreach ($body['pubs'] as $el) {
        // add new connection
        if (!in_array($el, $conPublishers)) {
            $dbo = $this->db->prepare($sqlGetPublisherId);
            $dbo->execute(array($el));

            $id = $dbo->fetch()['AuthPubID'];

            $dbo = $this->db->prepare($sqlCreateConnection);
            $params = array();
            array_push($params, $args['workId']);
            array_push($params, $id);
            array_push($params, 'publisher');
            $dbo->execute($params);
        } else {
            unset($conPublishers[array_search($el, $conPublishers)]);
        }
    }

    foreach ($conPublishers as $el) {
        $dbo = $this->db->prepare($sqlGetPublisherId);
        $dbo->execute(array($el));

        $id = $dbo->fetch()['AuthPubID'];
        $params = array();
        array_push($params, $args['workId']);
        array_push($params, $id);

        $dbo = $this->db->prepare($sqlCancelConnection);
        $dbo->execute($params);
    }


    return $res->withRedirect('/metadata/' . $args['workId']);
});

$app->get('/delete/{workId}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    $sqlDeleteConnections =
        'DELETE '.
        'FROM '.
        ' author_work '.
        'WHERE '.
        ' WorkID = ?';

    $sqlDeleteWork =
        'DELETE '.
        'FROM '.
        ' works '.
        'WHERE '.
        ' WorkID = ?';

    $dbo = $this->db->prepare($sqlDeleteConnections);
    $dbo->execute(array($args['workId']));

    $dbo = $this->db->prepare($sqlDeleteWork);
    $dbo->execute(array($args['workId']));

    return $res->withRedirect($this->router->pathFor('delAttach',['workId' => $args['workId']],['deletedWork' => true]));
});

$app->get('/delete-attachments/{workId}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    $sqlDeleteAttachments =
        'DELETE '.
        'FROM '.
        ' attachments '.
        'WHERE '.
        ' WorkID = ?';

    $dbo = $this->db->prepare($sqlDeleteAttachments);
    $dbo->execute(array($args['workId']));

    $path = __DIR__.'/../public/images/' . str_pad($args['workId'], 5, '0', STR_PAD_LEFT) . '/';
    
    deleteDirectory($path);

    $params = $req->getParams();

    if ($params['deletedWork']) {
        return $res->withRedirect('/content');
    } else {
        return $res->withRedirect('/attachments/'.$args['workId']);
    }
})->setName('delAttach');

$app->post('/text/{workId}', function (Request $req, Response $res, $args) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    $body = $req->getParsedBody();

    $sqlUpdate =
        'UPDATE '.
        ' works '.
        'SET '.
        'Content = ? '.
        'WHERE '.
        ' WorkID = ?';

    $dbo = $this->db->prepare($sqlUpdate);
    $dbo->execute(array($body['text'], $args['workId']));

    return $res->withRedirect('/text/'.$args['workId']);
});


$app->get('/add-user', function (Request $req, Response $res, array $args) use($app){
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login',[],$data));
    }

    return $this->view->render($res, 'addUser.twig', [
        'user' => $session->userEmail,
        'role' => $session->role
    ]);
});

$app->post('/add-user', function (Request $req, Response $res) {
    $session = $this->session;
    if (!$session->exists('userId')) {
        $data = ['sessionError' => true];
        return $res->withRedirect($this->router->pathFor('login', [], $data));
    }

    $body = $req->getParsedBody();

    $sql =
        'SELECT * ' .
        'FROM ' .
        ' users ' .
        'WHERE ' .
        ' UserEmail = ?';


    $dbo = $this->db->prepare($sql);
//
    $dbo->execute(array($body['email']));
    $user = $dbo->fetch();


    if (!empty($user)) {
        return $this->view->render($res, '/addUser.twig', [
            'emailExistError' => true
        ]);
    } elseif (empty($body['password1']) || $body['password1'] != $body['password2']) {
        return $this->view->render($res, '/addUser.twig', [
            'newPasswdError' => true
        ]);
    } else {
        $newPassword = $body['password1'];

        $salt =  substr(md5(rand()), 0, 7);

        $sql =
            'INSERT '.'INTO '.
            ' users '.
            ' (UserEmail, Password, Salt, Role) '.
            'VALUES '.
            ' (?, ?, ?, ?)';

        $dbo = $this->db->prepare($sql);
        $dbo->execute(array($body['email'], hash('sha256', $newPassword.$salt) , bin2hex($salt), 'editor'));

        return $res->withRedirect('/content');
    }

});

if (!function_exists('deleteDirectory')) {

    function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
    
        if (!is_dir($dir)) {
            return unlink($dir);
        }
    
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
    
            if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
    
        }
    
        return rmdir($dir);
    }
}

/**
 * easy image resize function
 * @param  $file - file name to resize
 * @param  $string - The image data, as a string
 * @param  $width - new image width
 * @param  $height - new image height
 * @param  $proportional - keep image proportional, default is no
 * @param  $output - name of the new file (include path if needed)
 * @param  $delete_original - if true the original image will be deleted
 * @param  $use_linux_commands - if set to true will use "rm" to delete the image, if false will use PHP unlink
 * @param  $quality - enter 1-100 (100 is best quality) default is 100
 * @return boolean|resource
 */
if (!function_exists('smart_resize_image')) {

    function smart_resize_image($file,
                                $string             = null,
                                $width              = 0,
                                $height             = 0,
                                $proportional       = false,
                                $output             = 'file',
                                $delete_original    = true,
                                $use_linux_commands = false,
                                $quality = 100
    ) {
    
        if ( $height <= 0 && $width <= 0 ) return false;
        if ( $file === null && $string === null ) return false;
    
        # Setting defaults and meta
        $info                         = $file !== null ? getimagesize($file) : getimagesizefromstring($string);
        $image                        = '';
        $final_width                  = 0;
        $final_height                 = 0;
        list($width_old, $height_old) = $info;
        $cropHeight = $cropWidth = 0;
    
        # Calculating proportionality
        if ($proportional) {
            if      ($width  == 0)  $factor = $height/$height_old;
            elseif  ($height == 0)  $factor = $width/$width_old;
            else                    $factor = min( $width / $width_old, $height / $height_old );
    
            $final_width  = round( $width_old * $factor );
            $final_height = round( $height_old * $factor );
        }
        else {
            $final_width = ( $width <= 0 ) ? $width_old : $width;
            $final_height = ( $height <= 0 ) ? $height_old : $height;
            $widthX = $width_old / $width;
            $heightX = $height_old / $height;
    
            $x = min($widthX, $heightX);
            $cropWidth = ($width_old - $width * $x) / 2;
            $cropHeight = ($height_old - $height * $x) / 2;
        }
    
        # Loading image to memory according to type
        switch ( $info[2] ) {
            case IMAGETYPE_JPEG:  $file !== null ? $image = imagecreatefromjpeg($file) : $image = imagecreatefromstring($string);  break;
            case IMAGETYPE_GIF:   $file !== null ? $image = imagecreatefromgif($file)  : $image = imagecreatefromstring($string);  break;
            case IMAGETYPE_PNG:   $file !== null ? $image = imagecreatefrompng($file)  : $image = imagecreatefromstring($string);  break;
            default: return false;
        }
    
    
        # This is the resizing/resampling/transparency-preserving magic
        $image_resized = imagecreatetruecolor( $final_width, $final_height );
        if ( ($info[2] == IMAGETYPE_GIF) || ($info[2] == IMAGETYPE_PNG) ) {
            $transparency = imagecolortransparent($image);
            $palletsize = imagecolorstotal($image);
    
            if ($transparency >= 0 && $transparency < $palletsize) {
                $transparent_color  = imagecolorsforindex($image, $transparency);
                $transparency       = imagecolorallocate($image_resized, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
                imagefill($image_resized, 0, 0, $transparency);
                imagecolortransparent($image_resized, $transparency);
            }
            elseif ($info[2] == IMAGETYPE_PNG) {
                imagealphablending($image_resized, false);
                $color = imagecolorallocatealpha($image_resized, 0, 0, 0, 127);
                imagefill($image_resized, 0, 0, $color);
                imagesavealpha($image_resized, true);
            }
        }
        imagecopyresampled($image_resized, $image, 0, 0, $cropWidth, $cropHeight, $final_width, $final_height, $width_old - 2 * $cropWidth, $height_old - 2 * $cropHeight);
    
    
        # Taking care of original, if needed
        if ( $delete_original ) {
            if ( $use_linux_commands ) exec('rm '.$file);
            else @unlink($file);
        }
    
        # Preparing a method of providing result
        switch ( strtolower($output) ) {
            case 'browser':
                $mime = image_type_to_mime_type($info[2]);
                header("Content-type: $mime");
                $output = NULL;
                break;
            case 'file':
                $output = $file;
                break;
            case 'return':
                return $image_resized;
                break;
            default:
                break;
        }
    
        # Writing image according to type to the output destination and image quality
        switch ( $info[2] ) {
            case IMAGETYPE_GIF:   imagegif($image_resized, $output);    break;
            case IMAGETYPE_JPEG:  imagejpeg($image_resized, $output, $quality);   break;
            case IMAGETYPE_PNG:
                $quality = 9 - (int)((0.9*$quality)/10.0);
                imagepng($image_resized, $output, $quality);
                break;
            default: return false;
        }
    
        return true;
    }
}
