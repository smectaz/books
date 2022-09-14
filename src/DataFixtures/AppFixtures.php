<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Author;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        //création des auteur
        $listAuthor = [];
        for ($i=1; $i <= 10 ; $i++) { 
            $author = new Author();
            $author -> setFirstName("Prénom " . $i);
            $author -> setLastName("Nom " . $i);
            $manager -> persist($author);
            //sauvegarde des auteurs dans un tableau
            $listAuthor[] = $author;
        }

        //création d'une vingtaine de livre

        for ($i = 1; $i <= 20; $i++) {
            $book = new Book;
            $book -> setTitle('livre ' . $i);
            $book -> setCoverText('quatrième de couverture numéro : ' . $i);
            $book -> setAuthor($listAuthor[array_rand($listAuthor)]);
            $manager->persist($book);
        }

        $manager->flush();
    }
}