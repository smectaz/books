<?php

namespace App\DataFixtures;

use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        //création d'une vingtaine de livre

        for ($i = 0; $i < 20; $i++) {
            $livre = new Book;
            $livre->setTitle('livre ' . $i);
            $livre->setCoverText('quatrième de couverture numéro : ' . $i);
            $manager->persist($livre);
        }

        $manager->flush();
    }
}