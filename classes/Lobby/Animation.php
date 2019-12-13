<?php

class Animation {
  public $ID = '';
  public $image = '';
  public $scripts = null;
  public $data = null;
  public $overridePlaylist = null;
  public $overrideTemplate = null;

  public function serialize() {
    $o = [];
    $o['image'] = $this->image;
    if($this->scripts != null)
      $o['scripts'] = $this->scripts;
    if($this->data != null)
      $o['data'] = $this->data;
    if($this->overridePlaylist != null)
      $o['playlist'] = $this->overridePlaylist;
    if($this->overrideTemplate != null)
      $o['template'] = $this->overrideTemplate;
    return $o;
  }

  public function deserialize($data, $id = null) {
    $this->image = $data['image'];
    if($id != null)
      $this->ID = $id;
    if(array_key_exists('id', $data))
      $this->ID = $data['id'];
    if(array_key_exists('scripts', $data))
      $this->scripts = $data['scripts'];
    if(array_key_exists('data', $data))
      $this->data = $data['data'];
    if(array_key_exists('playlist', $data))
      $this->overridePlaylist = $data['playlist'];
    if(array_key_exists('template', $data))
      $this->overrideTemplate = $data['template'];
  }
}
