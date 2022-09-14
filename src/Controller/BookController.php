<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BookController extends AbstractController
{
    //route pour get tout les livres sans distinction
    #[Route('/api/books', name:'book', methods:['GET'])]
    public function getAllBooks(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
    $bookList = $bookRepository->findAll();
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
}