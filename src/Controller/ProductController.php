<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Form\PayType;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Swift_Mailer;
use Swift_Message;

/**
 * @Route("/product")
 */
class ProductController extends AbstractController
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @Route("/", name="product_index", methods={"GET"})
     */
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    /**
     * @Route("/pay", name="pay", methods={"GET", "POST"})
     */
    public function pay(Swift_Mailer $mailer, Request $response)
    {
      $getCart = $this->session->get('cart', []);
      $message = (new Swift_Message())
          ->setSubject('Here should be a subject')
          ->setFrom(['support@mailtrap.io'])
          ->setTo(['newuser@example.com' => 'New Mailtrap user'])
          ->setBody(
            $this->renderView('product/added.html.twig',
            ['cart' => $getCart]),
            'text/html'
          );

      $mailer->send($message);
      $form = $this->createForm(PayType::class);
      return $this->render('product/pay.html.twig', [
        'pay_form' =>$form->createView(),
      ]);
    }

    /**
     * @Route("/new", name="product_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="product_show", methods={"GET"})
     */
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="product_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Product $product): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="product_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('product_index');
    }

    /**
     * @Route("/added/{id}", name="product_added", methods={"GET", "POST"})
     */
    public function addToCart(Product $product)
    {
      $getCart = $this->session->get('cart', []);

      if(isset($getCart[$product->getId()])) {
        $getCart[$product->getId()]['quantity']++;
      } else {
        $getCart[$product->getId()] = array(
          'quantity' => 1,
          'name' => $product->getName(),
          'price' => $product->getPrice(),
          'id' => $product->getId());
      }

      // DEZE CODE WORDT NIET MEER GEBRUIKT KIJK NAAR TWIG VOOR BEREKENING
      // foreach($getCart as $id => $details)
      // {
      //   $total = $total + ($getCart[$id]['quantity'] * $getCart[$id]['price']);
      // }

      $this->session->set('cart', $getCart);

      return $this->render('product/added.html.twig',[
        'product' => $getCart[$product->getId()]['name'],
        'quantity' => $getCart[$product->getId()]['quantity'],
        'cart' => $getCart
      ]);
    }
}
