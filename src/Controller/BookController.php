<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class BookController extends AbstractController
{
    //route pour get tout les livres sans distinction
    #[Route('/api/books', name:'book', methods:['GET'])]
    public function getBookList(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
    $bookList = $bookRepository->findAll();
    $jsonBookList = $serializer->serialize($bookList, 'json');
    return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
}

//route pour get un livre par id
    #[Route('/api/books/{id}', name: 'detailBook', methods: ['GET'])]
    public function getDetailBook(int $id, SerializerInterface $serializer, BookRepository $bookRepository):JsonResponse{
        $book = $bookRepository->find($id);
        if ($book) {
            $jsonBook = $serializer->serialize($book, 'json');
            return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
}