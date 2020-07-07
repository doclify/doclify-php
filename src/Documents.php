<?php

declare(strict_types=1);

namespace Doclify;

/**
* Doclify Documents
*/
class Documents
{
  /**
   * @var Client
   */
  private $client;

  /**
   * @var string
   */
  private $lang;

  /**
   * @var array
   */
  private $q = [];

  /**
   * @var array
   */
  private $includeQuery = [];

  /**
   * @var array
   */
  private $selectQuery = [];

  /**
   * @var array
   */
  private $orderQuery = [];

  public function __construct(Client $client)
  {
    $this->client = $client;
  }

  public function where (string $field, $operator, $value = null): self
  {
    if (func_num_args() === 2) {
      $value = $operator;
      $operator = 'eq';
    }

    $this->q[] = [$field, $operator, $value];

    return $this;
  }

  public function collection (?string $value): self
  {
    return $this->eq('sys.collection', $value);
  }

  public function contentType (string $value): self
  {
    return $this->eq('sys.contentType', $value);
  }

  public function id (int $value): self
  {
    return $this->eq('sys.id', $value);
  }

  public function uid (?string $value): self
  {
    return $this->eq('sys.uid', $value);
  }

  public function eq (string $field, $value): self
  {
    return $this->where($field, $value);
  }

  public function not (string $field, $value): self
  {
    return $this->where($field, 'not', $value);
  }

  public function in (string $field, $value): self
  {
    return $this->where($field, 'in', $value);
  }

  public function nin (string $field, $value): self
  {
    return $this->where($field, 'nin', $value);
  }

  public function gt (string $field, $value): self
  {
    return $this->where($field, 'gt', $value);
  }

  public function gte (string $field, $value): self
  {
    return $this->where($field, 'gte', $value);
  }

  public function lt (string $field, $value): self
  {
    return $this->where($field, 'lt', $value);
  }

  public function lte (string $field, $value): self
  {
    return $this->where($field, 'lte', $value);
  }

  public function fulltext (string $field, string $value): self
  {
    return $this->where($field, 'fulltext', $value);
  }

  public function match (string $field, string $value): self
  {
    return $this->where($field, 'match', $value);
  }

  public function include (...$fields): self
  {
    if (count($fields) > 0 && is_array($fields[0])) {
      $fields = $fields[0];
    }

    array_push($this->includeQuery, ...$fields);

    return $this;
  }

  public function select (...$fields): self
  {
    if (count($fields) > 0 && is_array($fields[0])) {
      $fields = $fields[0];
    }

    array_push($this->selectQuery, ...$fields);

    return $this;
  }

  public function orderBy (string $field, string $asc = 'asc'): self
  {
    $this->orderQuery[] = [$field, $asc];

    return $this;
  }

  private function getQuery(array $query = []): array
  {
    $defaults = [
      'q' => json_encode($this->q)
    ];

    if (count($this->includeQuery)) {
      $defaults['include'] = json_encode($this->includeQuery);
    }

    if (count($this->selectQuery)) {
      $defaults['select'] = json_encode($this->selectQuery);
    }

    if (count($this->orderQuery)) {
      $defaults['order'] = json_encode($this->orderQuery);
    }

    if ($this->lang) {
      $defaults['lang'] = $this->lang;
    }

    return array_merge($defaults, $query);
  }

  public function fetch (int $limit = 20) {
    return $this->client->requestWithCache('documents/search', [
      'query' => $this->getQuery([
        'limit' => $limit,
      ])
    ]);
  }

  public function paginate (int $page = 1, int $perPage = 20) {
    return $this->client->requestWithCache('documents/paginated', [
      'query' => $this->getQuery([
        'page' => $page,
        'perPage' => $perPage
      ])
    ]);
  }

  public function first () {
    return $this->client->requestWithCache('documents/single', [
      'query' => $this->getQuery()
    ]);
  }
}