<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\AuthorRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AuthorRepository::class)]
class Author
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getBooks", "getAuthors"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getBooks", "getAuthors"])]
    #[Assert\NotBlank(message: "Le prénom de l'auteur est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le prénom de l'auteur doit faire au moins {{ limit }} caractère", maxMessage: "Le prénom de l'auteur ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getBooks", "getAuthors"])]
    #[Assert\NotBlank(message: "Le nom de l'auteur est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le nom de l'auteur doit faire au moins {{ limit }} caractère", maxMessage: "Le nom de l'auteur ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $lastName = null;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Book::class)]
    #[Groups(["getAuthors"])]
    private Collection $books;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return Collection<int, Book>
     */
    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function addBook(Book $book): self
    {
        if (!$this->books->contains($book)) {
            $this->books->add($book);
            $book->setAuthor($this);
        }

        return $this;
    }

    public function removeBook(Book $book): self
    {
        if ($this->books->removeElement($book)) {
            // set the owning side to null (unless already changed)
            if ($book->getAuthor() === $this) {
                $book->setAuthor(null);
            }
        }

        return $this;
    }
}
