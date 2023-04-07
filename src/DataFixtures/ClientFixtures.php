<?php

namespace App\DataFixtures;

use App\Entity\Client;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ClientFixtures extends Fixture
{
    private $hasher;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager): void
    {
        $client1 = new Client();
        $client1
            ->setName('Client 1')
            ->setUsername('client1');

        $client1->setPassword($this->encoder->encodePassword($client1, 'pa$$w0rd'));
        $manager->persist($client1);

        $client2 = new Client();
        $client2
            ->setName('Client 2')
            ->setUsername('client2');

        $client2->setPassword($this->encoder->encodePassword($client2, 'pa$$w0rd2'));
        $manager->persist($client2);

        $client3 = new Client();
        $client3
            ->setName('Client 3')
            ->setUsername('client3');

        $client3->setPassword($this->encoder->encodePassword($client3, 'pa$$w0rd3'));
        $manager->persist($client3);

        $manager->flush();
    }
}
