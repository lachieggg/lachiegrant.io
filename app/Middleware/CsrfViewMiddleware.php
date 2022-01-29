<?php

namespace LoginApp\Middleware;

class CsrfViewMiddleware extends Middleware {
  /**
   * @param $request
   * @param $response
   * @param $next
   */
  public function __invokeold($request, $response, $next=null) {
    $this->container->get('view')->getEnvironment()->addGlobal('csrf', [
      'field' => '
        <input id="csrf-token-name-elem"  type="hidden" name="' . $this->container->get('csrf')->getTokenNameKey() . '" value="' . $this->container->get('csrf')->getTokenName() . '">
        <input id="csrf-token-value-elem" type="hidden" name="' . $this->container->get('csrf')->getTokenValueKey() . '" value="' . $this->container->get('csrf')->getTokenValue() . '">
      ',
      'tokenNameKey' => $this->container->get('csrf')->getTokenNameKey(),
      'tokenName' => $this->container->get('csrf')->getTokenName(),
      'tokenValueKey' => $this->container->get('csrf')->getTokenValueKey(),
      'tokenValue' => $this->container->get('csrf')->getTokenValue()
    ]);

    if(!isset($next)) {
      return $response;
    }
    return $next($request, $response);
  }

  /**
    * Example middleware invokable class
    *
    * @param  ServerRequest  $request PSR-7 request
    * @param  RequestHandler $handler PSR-15 request handler
    *
    * @return Response
    */
  public function __invoke(Request $request, RequestHandler $handler): Response
  {
    $response = $handler->handle($request);
    $existingContent = (string) $response->getBody();
    
    $response = new Response();
    $response->getBody()->write('BEFORE' . $existingContent);
    
    return $response;
  }
}
