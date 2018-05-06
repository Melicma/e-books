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
//        $this->assertContains('Hello name!', (string)$response->getBody());
    }

    public function testGetContent()
    {
        $response = $this->runApp('GET', '/content');

        $this->assertEquals(200, $response->getStatusCode());
//        $this->assertContains('Hello name!', (string)$response->getBody());
    }


//    public function testPostContent()
//    {
//        $response = $this->runApp('POST', '/login', ['email' => 'a@a.cz', 'password' => 'aaaa' ]);
//
//        $this->assertEquals(302, $response->getStatusCode());
////        $this->assertContains('Hello name!', (string)$response->getBody());
//    }
}