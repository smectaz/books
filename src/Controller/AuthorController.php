<?php

namespace App\Controller;

use App\Entity\Author;
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
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;


class AuthorController extends AbstractController
{
    /**
     * Cette méthode permet de récupérer l'ensemble des auteurs.
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des auteurs",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Author::class, groups={"getAuthors"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="La page que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Le nombre d'auteurs que l'on souhaite récuperer par page",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Authors")
     */

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
            $context = SerializationContext :: create() -> setGroups(['getAuthors']);
            return $serializer->serialize($authorList, 'json', $context);
        });
      
        return new JsonResponse($jsonAuthorList, Response::HTTP_OK, [], true);
   }

    //route pour get un auteur par id avec paramconverter
    #[Route('/api/authors/{id}', name: 'detailAuthor', methods: ['GET'])]

    public function getDetailAuthor(Author $author, SerializerInterface $serializer):JsonResponse
    {
        $context = SerializationContext :: create() -> setGroups(['getAuthors']);
        $jsonAuthor = $serializer->serialize($author, 'json', $context);
        return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
    }

    //route pour effacer un auteur
    #[Route('/api/authors/{id}', name: 'deleteAuthor', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez les droits suffisants pour effacer un auteur")]

    public function deleteAuthor(Author $author, EntityManagerInterface $em, TagAwareCacheInterface $cache):JsonResponse
    {
        $em -> remove($author);
        $em -> flush();
        $cache -> invalidateTags(["authorsCache"]);

        dd($author->getBooks());

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    //route pour créer un auteur
    #[Route('/api/authors', name:"createAuthor", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez les droits suffisants pour créer un auteur")]

        public function createAuthor(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache):JsonResponse
        {
            $author = $serializer -> deserialize($request -> getContent(), Author::class, 'json');

            // On vérifie les erreurs
            $errors = $validator->validate($author);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, 'json'),    JsonResponse::HTTP_BAD_REQUEST, [], true);
            }

            $em -> persist($author);
            $em -> flush();
            $cache -> invalidateTags(["authorsCache"]);

            $context = SerializationContext :: create() -> setGroups(['getAuthors']);
            $jsonAuthor = $serializer -> serialize($author, 'json', $context);
            $location = $urlGenerator -> generate('detailAuthor', ['id' => $author -> getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ["location" => $location],true);
        }

    //route pour modifier un auteur
    #[Route('/api/authors/{id}', name:"updateAuthors", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez les droits suffisants pour modifier un auteur")]
    
    public function updateAuthor(Request $request, SerializerInterface $serializer,
        Author $currentAuthor, EntityManagerInterface $em, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse {
            
            // On vérifie les erreurs
            $errors = $validator->validate($currentAuthor);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }

            $newAuthor = $serializer -> deserialize($request -> getContent(), Author::class, 'json');
            $currentAuthor -> setFirstName($newAuthor -> getFirstName());
            $currentAuthor -> setLastName($newAuthor -> getLastName());
                    

        $em->persist($currentAuthor);
        $em->flush();

        $cache ->invalidateTags(["authorsCache"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);

    }
}
