<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testPickLanguage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }
}
