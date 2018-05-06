<?php

namespace Tests\Functional;

class HomepageTest extends BaseTestCase
{

    protected $backupGlobalsBlacklist = array('_SESSION');
    /**
     * Test that the index route returns a rendered response containing the text 'SlimFramework' but not a greeting
     */
    public function testOne() {
        $_SESSION['userId'] = '2';
        $_SESSION['userEmail'] = 'a@a.cz';
        $_SESSION['role'] = 'admin';
    }
    
    public function testTwo() {
        $this->assertEquals('a@a.cz', $_SESSION['userEmail']);
        $this->assertEquals('2', $_SESSION['userId']);
        $this->assertEquals('admin', $_SESSION['role']);
    }

    public function testGetHomepageWithoutName()
    {
        $response = $this->runApp('GET', '/');

//        $this->assertEquals(200, $response->getStatusCode());
//        $this->assertContains('SlimFramework', (string)$response->getBody());
//        $this->assertNotContains('Hello', (string)$response->getBody());
    }

    /**
     * Test that the index route won't accept a post request
     */
    public function testPostHomepageNotAllowed()
    {
        $response = $this->runApp('POST', '/', ['test']);

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertContains('Method not allowed', (string)$response->getBody());
    }

    /**
     * Test that the index route with optional name argument returns a rendered greeting
     */
    public function testGetLogin()
    {
        $response = $this->runApp('GET', '/login');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Login', (string)$response->getBody());
    }

    public function testPostLogin()
    {
        $response = $this->runApp('POST', '/login', ['email' => 'a@a.cz', 'password' => 'aaaa' ]);

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testGetContent()
    {
        $response = $this->runApp('GET', '/content');

        $this->assertEquals(200, $response->getStatusCode());
//        $this->assertContains('Hello name!', (string)$response->getBody());
    }


    public function testPostContent()
    {
        $response = $this->runApp('POST', '/content', array("yearFrom" => '1950', "yearTo" => '2000', 'fulltext' => 'nebo' ));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Rukopisy', (string)$response->getBody());
    }

    public function testGetAuthors()
    {
        $response = $this->runApp('GET', '/list-author-publisher' );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Seznam autorů a vydavatelů', (string)$response->getBody());
    }

    public function testGetAddUser()
    {
        $response = $this->runApp('GET', '/add-user' );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Email uživatele', (string)$response->getBody());
    }

    public function testPostAddUser()
    {
        $response = $this->runApp('POST', '/add-user', array('password1' => 'test', 'password2' => 'test', 'email' => 'test@test.cz' ));

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testGetMetadata()
    {
        $response = $this->runApp('GET', '/metadata/52' );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Alois Lapáček', (string)$response->getBody());
    }

    public function testPostMetadata()
    {
        $response = $this->runApp('POST', '/metadata/52', array('title' => 'test' ));

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testGetText()
    {
        $response = $this->runApp('GET', '/text/56' );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('CYKLUS BÁSNÍ', (string)$response->getBody());
    }

    public function testGetAttachment()
    {
        $response = $this->runApp('GET', '/attachments/56' );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('test title', (string)$response->getBody());
    }

    public function testGetAddNewAuthor()
    {
        $response = $this->runApp('GET', '/new-author-publisher' );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Nový záznam', (string)$response->getBody());
    }

    public function testPostNewAuthor()
    {
        $response = $this->runApp('POST', '/new-author-publisher', array('name' => 'test', 'lastName' => 'test', 'pseudonym' => 0 ));

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testGetUpdateAuthor()
    {
        $response = $this->runApp('GET', '/author-publisher/176' );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('czc.cz', (string)$response->getBody());
    }

    public function testPostUpdateAuthor()
    {
        $response = $this->runApp('POST', '/author-publisher/176', array('name' => 'test', 'lastName' => 'test', 'pseudonym' => 0 ));

        $this->assertEquals(302, $response->getStatusCode());
    }
}