<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class PostController extends Controller
{
    /**
     * @Route("/posts", name="post_list")
     */
    public function indexAction()
    {
        $posts = $this->getDoctrine()
            ->getRepository('AppBundle:Post')
            ->findAll();

        return $this->render('Post/index.html.twig', array(
            'posts' => $posts,
        ));
    }

    /**
     * @Route("/posts/edit/{id}", name="post_edit")
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $post = $em->getRepository('AppBundle:Post')
            ->find($id);

        if (!$post) {
            throw $this->createNotFoundException();
        }

        $form = $this->createFormBuilder($post)
            ->add('title')
            ->add('contents')
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($post);
            $em->flush();

            $url = $this->generateUrl('post_list');

            return $this->redirect($url);
        }

        return $this->render('Post/edit.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
