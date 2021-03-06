<?php

namespace LoginApp\Controllers;

use Slim\Views\Twig as View;
use LoginApp\Controllers\Controller;
use LoginApp\Models\Comment;
use Ramsey\Uuid\Uuid;

class ContactController extends Controller
{
  /**
   * @param $container
   */
  public function __construct($container) 
  {
    parent::__construct($container);
    $this->privacy_mode = False;
  }

  /**
   * @param $request
   * @param $response
   */
  public function contact($request, $response)
  {
    return $this->privacy_mode ? $this->view->render($response, 'auth/private.twig') : $this->view->render($response, 'home/contact.twig');
  }

  /**
   * @param $request
   * @param $response
   */
  public function email($request, $response)
  {
    return $this->privacy_mode ? $this->view->render($response, 'auth/private.twig') : $this->view->render($response, 'home/contact.twig');
  }

  /**
   * @param $request
   * @param $response
   */
  public function getContactPosts($request, $response) {
    if($this->auth->admin()) {
      $posts = Comment::select('user_comments.uuid', 'user_comments.comment_text', 'user_comments.created_at')
        ->orderBy('created_at', 'DESC')
        ->get(['user_comments.*', 'users.*']);

      return $response->withJson($posts);
    } else {
      $c = new \Illuminate\Database\Eloquent\Collection;
      return $response->withJson($c);
    }
  }

  /**
   * @param $request
   * @param $response
   */
  public function submitContactPost($request, $response)
  {
    // XSS protection
    $comment = htmlspecialchars($request->getParam('comment'));
    $mobile = htmlspecialchars($request->getParam('mobile'));
    $name = htmlspecialchars($request->getParam('email'));
   
    $comment = Comment::create([
      'uuid' => Uuid::uuid4(),
      'user_uuid' => $this->auth->user->uuid,
      'comment_text' => $comment 
    ]);

    return $response->withRedirect($this->router->pathFor('contact'));
  }

  /**
   * @param $request
   * @param $response
   */
  public function deleteContactPost($request, $response)
  {
    if($this->auth->admin()) {
      $uuid = $request->getParam('uuid');
      $comment = Comment::where('uuid', $uuid)->first();
      $comment->delete();

      return $response->withRedirect($this->router->pathFor('contact'));
    }
  }
}
