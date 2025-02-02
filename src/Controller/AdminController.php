<?php

namespace App\Controller;

use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER', statusCode: 403, message: 'You must be logged in.')]
class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/test1', name: 'app_admin_test1')]
    public function index_test(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        // or add an optional message - seen by developers
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'User tried to access a page without having ROLE_ADMIN');
        return $this->render('admin/index.html.twig', [
        'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    #[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, message: 'You are not allowed to access the Super admin dashboard.')]
    public function admin_users(EntityManagerInterface $entityManager): Response
    {
        $users=$entityManager->getRepository(User::class)->findAll();
        return $this->render('admin/users.html.twig', [
        'controller_name' => 'AdminController','users' => $users
        ]);
    }

    #[Route('/admin/show/{id}', name: 'app_admin_show')]
    public function admin_show(int $id,EntityManagerInterface $entityManager): Response
    {
        $user=$entityManager->getRepository(User::class)->find($id);
        return $this->render('admin/show.html.twig', [
        'controller_name' => 'AdminController','user' => $user
        ]);
    }

    #[Route('/admin/delete/{id}', name: 'user_delete')]
    #[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, message: 'You are not allowed to access the Super admin dashboard.')]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    { 
        // Récupérer l'article par ID
        $user = $entityManager->getRepository(User::class)->find($id);
        // Si l'article n'existe pas, rediriger vers la liste des articles avec un message d'erreur
        if (!$user) {
            $this->addFlash('error', 'User non trouvé');
            return $this->redirectToRoute('app_admin_users');
        }
        // Vérifier si la confirmation a été envoyée
        if ($request->isMethod('POST')) {
            // Supprimer l'article de la base de données
            $entityManager->remove($user);
            $entityManager->flush();
            // Ajouter un message flash pour informer de la suppression
            $this->addFlash('success', 'User supprimé avec succès');
            // Rediriger vers la liste des articles
            return $this->redirectToRoute('app_admin_users');
        }
        // Afficher une page de confirmation
        return $this->render('admin/delete_confirm.html.twig', [
            'user' => $user 
        ]);
    }

    #[Route('/admin/edit/{id}', name: 'user_edit')]
    #[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, message: 'You are not allowed to access the Super admin dashboard.')]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        
        // Récupérer l'article par ID
        $user = $entityManager->getRepository(User::class)->find($id);
    
        // Si l'article n'existe pas, rediriger vers la liste des articles
        if (!$user) {
            $this->addFlash('error', 'User non trouvé');
            return $this->redirectToRoute('app_admin_users');
        }
        // Créer le formulaire
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        // Vérifier si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarde des modifications en base de données
            $entityManager->flush();
            // Ajouter un message flash pour confirmer l'édition
            $this->addFlash('success', 'User modifié avec succès');
            // Redirection vers la page de l'article modifié
            return $this->redirectToRoute('app_admin_show', ['id' => $user->getId()]);
        }
        // Affichage du formulaire d'édition
        return $this->render('admin/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }
}
