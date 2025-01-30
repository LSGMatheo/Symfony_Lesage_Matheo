<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;


class ArticleController extends AbstractController
{
    #[Route('/article', name: 'app_article')]
    public function index(): Response
    {
        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
        ]);
    }


    #[Route('/article/generate', name: 'generate_article')]
     public function generateArticlet(EntityManagerInterface $entityManager): Response 
     {
        $article = new Article();
        $str_now = date('Y-m-d H:i:s', time());
        $article->setTitre('Titre aleatoire #'.$str_now);
        $content = file_get_contents('http://loripsum.net/api');
        $article->setTexte($content);
        $article->setPublie(true);
        $article->setDate(\DateTimeImmutable::createFromFormat('Y-m-d H:i:s',$str_now));
        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $entityManager->persist($article);
        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();
        return new Response('Saved new article with id '.$article->getId());
     }

     #[Route('/article/list', name: 'list_article')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $articles=$entityManager->getRepository(Article::class)->findAll();
        return $this->render('article/list.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/article/show/{{article.id}}', name: 'id_show_article')]
    public function show(): Response
    {
        return $this->render('article/show.html.twig', [
            'controller_name' => 'ArticleController',
        ]);
    }
}