<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\User;
use App\Entity\Author;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher) 
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        //création d'un user normal
        $user = new User();
        $user -> setEmail("user@bookapi.com");
        $user -> setRoles(["ROLE_USER"]);
        $user -> setPassword($this -> userPasswordHasher -> hashPassword($user, "password"));
        $manager -> persist($user);

        //création d'un user admin
        $userAdmin = new User();
        $userAdmin -> setEmail("admin@bookapi.com");
        $userAdmin -> setRoles(["ROLE_ADMIN"]);
        $userAdmin -> setPassword($this -> userPasswordHasher -> hashPassword($userAdmin, "password"));
        $manager -> persist($userAdmin);

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
            $book -> setComment('Commentaire du bibliothecaire ' . $i);
            $book -> setAuthor($listAuthor[array_rand($listAuthor)]);
            $manager->persist($book);
        }

        $manager->flush();
    }
}