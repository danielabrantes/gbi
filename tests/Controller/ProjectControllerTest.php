<?php

namespace App\Tests\Controller;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Publisher;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

ini_set('error_reporting', E_ALL); // or error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
/*
 *
 * By using a TestCase (from PHPUnit) you can easily mock dependencies (instanciate fake objects by extending them) and even make expectations on their method calls:
 *
 * Using KernelTestCase (from Symfony), you have access to services from the service container. This is very useful when you really need a service and not a mock object that does not really do anything.
    For example, you could request the serializer to transform some JSON input into DTOs
 * */

#class ProjectControllerTest extends TestCase
class ProductRepositoryTest extends KernelTestCase
{
    private \Doctrine\ORM\EntityManager $entityManager;

    /*
     *
     * Initial setup for test
     *
     * */
    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        /*
         *
         *Delete all books
         *
         * */
        $books = $this->entityManager->getRepository(Book::class)->findAll();
        foreach ($books as $book) {
            $this->entityManager->remove($book);
        }
        $this->entityManager->flush();
        $count = $books = $this->entityManager->getRepository(Book::class)->findAll();
        $this->assertEquals(0, count($count));
        /*
         *
         *Delete all authors
         *
         * */
        $books = $this->entityManager->getRepository(Author::class)->findAll();
        foreach ($books as $book) {
            $this->entityManager->remove($book);
        }
        $this->entityManager->flush();
        $count = $books = $this->entityManager->getRepository(Author::class)->findAll();
        $this->assertEquals(0, count($count));
        /*
         *
         *Delete all publishers
         *
         * */
        $books = $this->entityManager->getRepository(Publisher::class)->findAll();
        foreach ($books as $book) {
            $this->entityManager->remove($book);
        }
        $this->entityManager->flush();
        $count = $books = $this->entityManager->getRepository(Publisher::class)->findAll();
        $this->assertEquals(0, count($count));
        /*
         *
         *All Two Authors
         *
         * */
        $author1 = new Author();
        $author1->setName('author1');
        $author2 = new Author();
        $author2->setName('author2');
        $this->entityManager->persist($author1);
        $this->entityManager->persist($author2);
        $this->entityManager->flush();
        /*
         *
         *All Two publishers
         *
         * */
        $publisher1 = new Publisher();
        $publisher1->setName('publisher1');
        $publishers2 = new Publisher();
        $publishers2->setName('publishers2');
        $this->entityManager->persist($publisher1);
        $this->entityManager->persist($publishers2);
        $this->entityManager->flush();
        /*
         *
         *All Two Books
         *
         * */
        $book1 = new Book();
        $book1->setName('book1');
        $book1->setDescription('description1');
        $book1->setISBN('isbnbook1');
        $book1->addPublisher($publisher1);
        $book1->addAuthor($author1);
        $book2 = new Book();
        $book2->setName('book2');
        $book2->setDescription('description2');
        $book2->setISBN('isbnbook2');
        $book2->addPublisher($publishers2);
        $book2->addAuthor($author2);
        $this->entityManager->persist($book1);
        $this->entityManager->persist($book2);
        $this->entityManager->flush();
    }

    /*
     *
     * close testing
     *
     * */
    protected function tearDown(): void
    {
        parent::tearDown();
        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        //$this->entityManager = null;
    }

    /*
     *
     *  Test list all books
     *
     * */
    public function testIndex()
    {
        $client = new Client();
        $res = $client->request('GET', 'http://127.0.0.1:8000/api/books');
        //echo $res->getStatusCode();
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/json', $res->getHeader('content-type')[0]);
        /*
         *
         * 2 books
         *
         * */
        $this->assertSame(count(json_decode($res->getBody(), true)), 2);
    }

    /*
     *
     *  Test create one book
     *
     * */
    public function testCreate()
    {
        $client = new Client();
        $author = new Author();
        $author_id = $book = $this->entityManager->getRepository(Author::class)
            ->findOneBy(['name' => 'author1']);
        $publisher_id = $book = $this->entityManager->getRepository(Publisher::class)
            ->findOneBy(['name' => 'publisher1']);
        $json = '{
        "name":"book3",
        "description":"descriptionbook3",
        "isbn":"isbnbook3",
        "author":[ {"id":"' . $author_id->getId() . '"}],
        "publisher":[ {"id":"' . $publisher_id->getId() . '"}]
        }';
        //dd(json_decode($json,true));
        $res = $client->request('POST', 'http://127.0.0.1:8000/api/books',
            ['json' => json_decode($json, true)]
        );
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/json', $res->getHeader('content-type')[0]);
        //dd($res->getBody()->getContents());
        //TODO check all the fields
        $string = '{"id":441,"name":"book3","description":"descriptionbook3","isbn":"isbnbook3","author":[{"id":465,"name":"author1"}],"publisher":[{"id":461,"name":"publisher1"}]}';
        $res = $client->request('GET', 'http://127.0.0.1:8000/api/books');
        //echo $res->getStatusCode();
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/json', $res->getHeader('content-type')[0]);
        /*
         *
         * 2 books
         *
         * */
        $this->assertSame(count(json_decode($res->getBody(), true)), 3);
    }

    /*
     *
     *  Test search book by name
     *
     * */
    public function testShow()
    {
        $client = new Client();
        $json = '{"name":"book1"}';
        $res = $client->request('POST', 'http://127.0.0.1:8000/api/books/search',
            ['json' => json_decode($json, true)]
        );
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/json', $res->getHeader('content-type')[0]);
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/json', $res->getHeader('content-type')[0]);
        /*
         *
         * 1 books, 2 results because of indice
         *
         * */
        //dd(json_decode($res->getBody(), true));
        $this->assertSame(count(json_decode($res->getBody(), true)), 2);
    }

    /*
     *
     *  Test delete book, by id
     *
     * */
    public function testDelete()
    {
        $client = new Client();
        $book1_id = $book = $this->entityManager->getRepository(Book::class)
            ->findOneBy(['name' => 'book1']);
        //dd($book1_id->getId());
        $res = $client->request('DELETE', 'http://127.0.0.1:8000/api/books/' . $book1_id->getId());
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/json', $res->getHeader('content-type')[0]);
// 'application/json; charset=utf8'
        $res = $client->request('GET', 'http://127.0.0.1:8000/api/books');
        //echo $res->getStatusCode();
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/json', $res->getHeader('content-type')[0]);
        /*
         *
         * 1 books left
         *
         * */
        //dd(json_decode($res->getBody(), true));
        $this->assertSame(count(json_decode($res->getBody(), true)), 1);
    }

    /*
     *
     *  Test update book metadata
     *
     * */
    public function testUpdate()
    {
        $client = new Client();
        $book1_id = $book = $this->entityManager->getRepository(Book::class)
            ->findOneBy(['name' => 'book1']);
        //dd($book1_id);
        $author_id = $book = $this->entityManager->getRepository(Author::class)
            ->findOneBy(['name' => 'author1']);
        //dd($author_id);
        $publisher_id = $book = $this->entityManager->getRepository(Publisher::class)
            ->findOneBy(['name' => 'publisher1']);
        //dd($publisher_id);
        $json = '{
        "name":"book1",
        "description":"descriptionbook1_patched",
        "isbn":"isbnbook3_patched",
        "author":[ {"id":"' . $author_id->getId() . '"}],
        "publisher":[ {"id":"' . $publisher_id->getId() . '"}]
        }';
        //dd(json_decode($json,true));
        $res = $client->request('PATCH', 'http://127.0.0.1:8000/api/books/' . $book1_id->getId(),
            ['json' => json_decode($json, true)]
        );
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/json', $res->getHeader('content-type')[0]);
        $res = $client->request('GET', 'http://127.0.0.1:8000/api/books/' . $book1_id->getId());
        //echo $res->getStatusCode();
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/json', $res->getHeader('content-type')[0]);
        /*
         *
         * 2 books
         *
         * */
        //dd(json_decode($res->getBody(), true)['description']);
        $description = json_decode($res->getBody(), true)['description'];
        $this->assertSame($description, 'descriptionbook1_patched');
    }
}
