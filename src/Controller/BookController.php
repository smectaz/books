<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BookController extends AbstractController
{
    //route pour get tout les livres sans distinction
    #[Route('/api/books', name:'book', methods:['GET'])]

    public function getAllBooks(BookRepository $bookRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $page = $request -> get('page', 1);
        $limit = $request -> get('limit', 3);

        $idCache = "getAllBook-" . $page . "-" . $limit;

        $bookList = $cachePool -> get($idCache, function (ItemInterface $item) use ($bookRepository, $page, $limit) {
            $item -> tag("booksCache");
            return $bookRepository->findAllWithPagination($page, $limit);
        });
        
        $jsonBookList = $serializer->serialize($bookList, 'json', ['groups' => 'getBooks']);
        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

//ancienne route pour get un livre par id
    // #[Route('/api/books/{id}', name: 'detailBook', methods: ['GET'])]
    // public function getDetailBook(int $id, SerializerInterface $serializer, BookRepository $bookRepository):JsonResponse{
    //     $book = $bookRepository->find($id);
    //     if ($book) {
    //         $jsonBook = $serializer->serialize($book, 'json');
    //         return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
    //     }
    //     return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    // }

//route pour get un livre par id avec paramconverter
        #[Route('/api/books/{id}', name: 'detailBook', methods: ['GET'])]

        public function getDetailBook(Book $book, SerializerInterface $serializer):JsonResponse
        {
            $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);
            return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
        }

//route pour effacer un livre
        #[Route('/api/books/{id}', name: 'deleteBook', methods: ['DELETE'])]
        #[IsGranted('ROLE_ADMIN', message: "Vous n'avez les droits suffisants pour effacer un livre")]

        public function deleteBook(Book $book, EntityManagerInterface $em):JsonResponse
        {
            $em -> remove($book);
            $em -> flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

//route pour créer un livre avec id de l'auteur
        #[Route('/api/books', name:"createBook", methods: ['POST'])]
        #[IsGranted('ROLE_ADMIN', message: "Vous n'avez les droits suffisants pour créer un livre")]

        public function createBook(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, AuthorRepository $authorRepository, ValidatorInterface $validator):JsonResponse
        {
            $book = $serializer -> deserialize($request -> getContent(), book::class, 'json');

            //verif erreur
            $errors = $validator->validate($book);

            if ($errors -> count() > 0) {
                return new JsonResponse($serializer -> serialize($errors,'json'), 
                JsonResponse::HTTP_BAD_REQUEST, [], true);
            }

            $em -> persist($book);
            $em -> flush();

            //récupération de l'ensemble des données
            $content = $request -> toArray();

            //récupération de l'id author si pas défini il est -1 par défaut
            $idAuthor = $content['idAuthor'] ?? -1;

            //recherche de l'auteur si rien trouvé egale a null
            $book -> setAuthor($authorRepository -> find($idAuthor));

            $jsonBook = $serializer -> serialize($book, 'json', ['groups' => 'getBooks']);
            
            $location = $urlGenerator -> generate('detailBook', ['id' => $book -> getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            return new JsonResponse($jsonBook, Response::HTTP_CREATED, ["location" => $location],true);
        }

        //route pour modifier un livre
        #[Route('/api/books/{id}', name:"updateBook", methods:["PUT"])]
        #[IsGranted('ROLE_ADMIN', message: "Vous n'avez les droits suffisants pour modifier un livre")]

        public function updateBook(Request $request, SerializerInterface $serializer, Book $currentBook, EntityManagerInterface $em, AuthorRepository $authorRepository, ValidatorInterface $validator): JsonResponse
        {
            $updatedBook = $serializer -> deserialize($request ->getContent(),
                Book::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]);

                //verif erreur
            $errors = $validator->validate($updatedBook);

            if ($errors -> count() > 0) {
                return new JsonResponse($serializer -> serialize($errors,'json'), 
                JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            $content = $request -> toArray();
            $idAuthor = $content['idAuthor'] ?? -1;
            $updatedBook -> setAuthor($authorRepository -> find($idAuthor));

            $em -> persist($updatedBook);
            $em -> flush();
            return new JsonResponse (null, JsonResponse::HTTP_NO_CONTENT);
        }
}