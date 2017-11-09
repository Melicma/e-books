<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 31.10.17
 * Time: 20:29
 */

$app->get('/api/users', function() {
    $users = $this->db->query('SELECT * FROM users');
    $users->execute();
    $data = $users->fetchAll();
    echo json_encode($data);
});