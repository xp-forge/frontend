<?php namespace xp\frontend;

class Download extends Response {
  private $transferred= 0;

  /** @return bool */
  public function cached() { return false; }

  /** @return void */
  public function close() {
    if ($f= $this->progress['final'] ?? null) $f($this->transferred);
    $this->in->close();
  }

  /**
   * Reads from this response
   *
   * @param  int $limit
   * @return string
   */
  public function read($limit= 8192) {
    $chunk= $this->in->read($limit);
    $this->transferred+= strlen($chunk);
    if ($f= $this->progress['update'] ?? null) $f($this->transferred);
    return $chunk;
  }
}
