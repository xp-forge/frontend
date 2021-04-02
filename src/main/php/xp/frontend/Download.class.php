<?php namespace xp\frontend;

class Download extends Response {
  private $transferred= 0;

  /** @return bool */
  public function cached() { return false; }

  /** @return void */
  public function close() {
    $this->progress['final']($this->transferred);
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
    $this->progress['update']($this->transferred);
    return $chunk;
  }
}
