<?php

namespace Doclify;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\json_decode as guzzle_json_decode;

/**
* Doclify Exception
*/
class Exception extends \RuntimeException {
  /**
   * @var RequestException|null
   */
  private $previous;

  /**
   * @var RequestInterface
   */
  private $request;

  /**
   * @var ResponseInterface|null
   */
  private $response;

  /**
   * @var array|null
   */
  private $data;

  public function __construct(RequestException $previous) {
    $this->previous = $previous;
    $this->request = $previous->getRequest();
    $this->response = $previous->getResponse();

    if ($this->response) {
      $this->data = \json_decode($this->response->getBody()->getContents());
    }

    $message = $previous->getMessage();

    if ($this->data) {
      if (isset($this->data->error) && isset($this->data->error->message)) {
        $message = $this->data->error->message;
      } else {
        $message = 'Invalid request parameters.';
      }
    }

    parent::__construct($message, 0, $previous);
  }

  public function getRequest(): RequestInterface
  {
      return $this->request;
  }

  public function getResponse(): ?ResponseInterface
  {
      return $this->response;
  }

  public function hasResponse(): bool
  {
    return null !== $this->response;
  }

  public function getData(): ?array
  {
      return $this->data;
  }
}