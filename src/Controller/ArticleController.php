<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ArticleType;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Security\Http\Attribute\IsGranted;


class ArticleController extends AbstractController
{
    #[Route('/article', name: 'app_article')]
    public function index(): Response
    {
        
        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController'
        ]);
    }


    #[Route('/article/generate', name: 'generate_article')]
    #[IsGranted('ROLE_USER', statusCode: 403, message: 'You are not allowed to access the Super admin dashboard.')]
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
    #[IsGranted('ROLE_USER', statusCode: 403, message: 'You are not allowed to access the Super admin dashboard.')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        
        $articles=$entityManager->getRepository(Article::class)->findAll();
        return $this->render('article/list.html.twig', [
            'articles' => $articles
        ]);
    }

    #[Route('/article/show/{id}', name: 'article_show')]
    #[IsGranted('ROLE_USER', statusCode: 403, message: 'You are not allowed to access the Super admin dashboard.')]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        
        $this->addFlash( 'success' ,'Article loaded ! ');
        $article=$entityManager->getRepository(Article::class)->find($id);
        return $this->render('article/show.html.twig', [
            'article' => $article
        ]);
    }

    #[Route('/article/new', name: 'article_new')]
    #[IsGranted('ROLE_USER', statusCode: 403, message: 'You are not allowed to access the Super admin dashboard.')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        
        // Création d'un nouvel article avec des valeurs par défaut
        $article = new Article();
        $article->setTitre('Which Title ?');
        $article->setTexte('And which content ?');
        $article->setDate(new \DateTimeImmutable());
        // Création du formulaire
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request); // Traite la soumission du formulaire
        // Vérifie si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarde en base de données
            $entityManager->persist($article);
            $entityManager->flush();
            // Redirection vers la liste des articles après la création
            return $this->redirectToRoute('list_article');
        }
        // Affichage du formulaire
        return $this->render('article/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/article/edit/{id}', name: 'article_edit')]
    #[IsGranted('ROLE_USER', statusCode: 403, message: 'You are not allowed to access the Super admin dashboard.')]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        
        // Récupérer l'article par ID
        $article = $entityManager->getRepository(Article::class)->find($id);
    
        // Si l'article n'existe pas, rediriger vers la liste des articles
        if (!$article) {
            $this->addFlash('error', 'Article non trouvé');
            return $this->redirectToRoute('list_article');
        }
        // Créer le formulaire
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        // Vérifier si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarde des modifications en base de données
            $entityManager->flush();
            // Ajouter un message flash pour confirmer l'édition
            $this->addFlash('success', 'Article modifié avec succès');
            // Redirection vers la page de l'article modifié
            return $this->redirectToRoute('article_show', ['id' => $article->getId()]);
        }
        // Affichage du formulaire d'édition
        return $this->render('article/edit.html.twig', [
            'form' => $form->createView(),
            'article' => $article
        ]);
    }

    #[Route('/article/delete/{id}', name: 'article_delete')]
    #[IsGranted('ROLE_ARTICLE_ADMIN', statusCode: 403, message: 'You are not allowed to access the Super admin dashboard.')]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        
        // Récupérer l'article par ID
        $article = $entityManager->getRepository(Article::class)->find($id);
        // Si l'article n'existe pas, rediriger vers la liste des articles avec un message d'erreur
        if (!$article) {
            $this->addFlash('error', 'Article non trouvé');
            return $this->redirectToRoute('list_article');
        }
        // Vérifier si la confirmation a été envoyée
        if ($request->isMethod('POST')) {
            // Supprimer l'article de la base de données
            $entityManager->remove($article);
            $entityManager->flush();
            // Ajouter un message flash pour informer de la suppression
            $this->addFlash('success', 'Article supprimé avec succès');
            // Rediriger vers la liste des articles
            return $this->redirectToRoute('list_article');
        }
        // Afficher une page de confirmation
        return $this->render('article/delete_confirm.html.twig', [
            'article' => $article 
        ]);
    }
    
}