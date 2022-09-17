<?php

namespace App\Controller;

use App\Entity\Author;
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

class AuthorController extends AbstractController
{
      //route pour get tout les auteurs sans distinction
    #[Route('/api/authors', name:'author', methods:['GET'])]

    public function getAllAuthors(AuthorRepository $authorRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllAuthor-" . $page . "-" . $limit;
        
        $jsonAuthorList = $cache->get($idCache, function (ItemInterface $item) use ($authorRepository, $page, $limit, $serializer) {
            $item->tag("authorsCache");
            $authorList = $authorRepository->findAllWithPagination($page, $limit);
            return $serializer->serialize($authorList, 'json', ['groups' => 'getAuthors']);
        });
      
        return new JsonResponse($jsonAuthorList, Response::HTTP_OK, [], true);
   }

    //route pour get un auteur par id avec paramconverter
    #[Route('/api/authors/{id}', name: 'detailAuthor', methods: ['GET'])]

    public function getDetailAuthor(Author $author, SerializerInterface $serializer):JsonResponse
    {
        $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => 'getAuthors']);
        return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
    }

    //route pour effacer un auteur
    #[Route('/api/authors/{id}', name: 'deleteAuthor', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez les droits suffisants pour effacer un auteur")]

    public function deleteAuthor(Author $author, EntityManagerInterface $em, TagAwareCacheInterface $cachePool):JsonResponse
    {
        $cachePool -> invalidateTags(["authorsCache"]);
        $em -> remove($author);
        $em -> flush();
        dd($author->getBooks());

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    //route pour créer un auteur
    #[Route('/api/authors', name:"createAuthor", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez les droits suffisants pour créer un auteur")]

        public function createAuthor(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator):JsonResponse
        {
            $author = $serializer -> deserialize($request -> getContent(), Author::class, 'json');

            // On vérifie les erreurs
            $errors = $validator->validate($author);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, 'json'),    JsonResponse::HTTP_BAD_REQUEST, [], true);
            }

            $em -> persist($author);
            $em -> flush();

            $jsonAuthor = $serializer -> serialize($author, 'json', ['groups' => 'getAuthors']);
            $location = $urlGenerator -> generate('detailAuthor', ['id' => $author -> getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ["location" => $location],true);
        }

    //route pour modifier un auteur
    #[Route('/api/authors/{id}', name:"updateAuthors", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez les droits suffisants pour modifier un auteur")]
    
    public function updateAuthor(Request $request, SerializerInterface $serializer,
        Author $currentAuthor, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse {



        $updatedAuthor = $serializer->deserialize($request->getContent(), Author::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAuthor]);
                    // On vérifie les erreurs
                    $errors = $validator->validate($updatedAuthor);
                    if ($errors->count() > 0) {
                        return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
                    }

        $em->persist($updatedAuthor);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);

    }
}
