<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Service\VersioningService;
use App\Repository\AuthorRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BookController extends AbstractController
{
    //route pour get tout les livres sans distinction
    #[Route('/api/books', name: 'books', methods: ['GET'])]
    public function getAllBooks(BookRepository $bookRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllBook-" . $page . "-" . $limit;
        
        
        $jsonBookList = $cache->get($idCache, function (ItemInterface $item) use ($bookRepository, $page, $limit, $serializer) {
            $item->tag("booksCache");
            $bookList = $bookRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext :: create() -> setGroups(['getBooks']);
            return $serializer->serialize($bookList, 'json', $context);
        });
      
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

        public function getDetailBook(Book $book, SerializerInterface $serializer, VersioningService $versioningService):JsonResponse
        {
            $version = $versioningService -> getVersion();
            $context = SerializationContext :: create() -> setGroups(['getBooks']);
            $context -> setVersion($version);
            $jsonBook = $serializer->serialize($book, 'json', $context);
            return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
        }

//route pour effacer un livre
        #[Route('/api/books/{id}', name: 'deleteBook', methods: ['DELETE'])]
        #[IsGranted('ROLE_ADMIN', message: "Vous n'avez les droits suffisants pour effacer un livre")]

        public function deleteBook(Book $book, EntityManagerInterface $em, TagAwareCacheInterface $cache):JsonResponse
        {
            $em -> remove($book);
            $em -> flush();

            $cache -> invalidateTags(["booksCache"]);

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

//route pour créer un livre avec id de l'auteur
        #[Route('/api/books', name:"createBook", methods: ['POST'])]
        #[IsGranted('ROLE_ADMIN', message: "Vous n'avez les droits suffisants pour créer un livre")]

        public function createBook(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, AuthorRepository $authorRepository, ValidatorInterface $validator, TagAwareCacheInterface $cache):JsonResponse
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

            // On vide le cache
            $cache->invalidateTags(["booksCache"]);

            //récupération de l'ensemble des données
            $content = $request -> toArray();

            //récupération de l'id author si pas défini il est -1 par défaut
            $idAuthor = $content['idAuthor'] ?? -1;

            //recherche de l'auteur si rien trouvé egale a null
            $book -> setAuthor($authorRepository -> find($idAuthor));

            $context = SerializationContext :: create() -> setGroups(['getBooks']);

            $jsonBook = $serializer -> serialize($book, 'json', $context);
            
            $location = $urlGenerator -> generate('detailBook', ['id' => $book -> getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            return new JsonResponse($jsonBook, Response::HTTP_CREATED, ["location" => $location],true);
        }

        //route pour modifier un livre
        #[Route('/api/books/{id}', name:"updateBook", methods:["PUT"])]
        #[IsGranted('ROLE_ADMIN', message: "Vous n'avez les droits suffisants pour modifier un livre")]

        public function updateBook(Request $request, SerializerInterface $serializer, Book $currentBook, EntityManagerInterface $em, AuthorRepository $authorRepository, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
        {
            $newBook = $serializer -> deserialize($request -> getContent(), Book::class, 'json');
            $currentBook -> setTitle($newBook-> getTitle());
            $currentBook -> setCoverText($newBook -> getCoverText());

                //verif erreur
            $errors = $validator->validate($currentBook);

            if ($errors -> count() > 0) {
                return new JsonResponse($serializer -> serialize($errors,'json'), 
                JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            $content = $request -> toArray();
            $idAuthor = $content['idAuthor'] ?? -1;
            $currentBook -> setAuthor($authorRepository -> find($idAuthor));

            $em -> persist($currentBook);
            $em -> flush();

            $cache -> invalidateTags(["booksCache"]);

            return new JsonResponse (null, JsonResponse::HTTP_NO_CONTENT);
        }
}