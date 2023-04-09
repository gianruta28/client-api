<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ClientFixtures extends Fixture
{
    private $hasher;
    private $data;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->data = [
            'name' => [
                'Bob',
                'Mike',
                'Fred',
                'Jimmy',
                'Alex',
                'Leo',
            ],
            'lastName' => [
                'Smith',
                'Johnson',
                'Williams',
                'Miller',
                'Garcia',
            ],
            'city' => [
                'San Francisco',
                'Los Angeles',
                'New York',
                'Miami',
                'Las Vegas',
                'Orlando',
                'Boston',
                'Seattle'
            ],
            'category' => [
                'X',
                'Y',
                'Z'
            ]

        ];

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

        $this->data['client'] = [
            $client1, $client2, $client3
        ];

        for($i = 0; $i <15; $i++){

            $newUser = new User();
            $newUser
                ->setName($this->data['name'][rand(0, 5)])
                ->setLastName($this->data['lastName'][rand(0, 4)])
                ->setActive(!($i % 3 == 0))
                ->setAge(rand(22, 50))
                ->setCity($this->data['city'][rand(0, 7)])
                ->setCategory($this->data['category'][rand(0, 2)])
                ->setCreatedAt(new \DateTimeImmutable())
                ->setClient($this->data['client'][rand(0, 2)])
            ;
            $manager->persist($newUser);
        }
        $manager->flush();

    }
}
