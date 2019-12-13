<?php
class Pool {
  public $ID = '';
  public $animations = [];
  public $animationsByName = [];
  public $playlist = '';
  public $template = 'main';

  public function __construct() {}

  public function serialize() {
    $o = [
      'id' => $this->ID,
      'playlist' => $this->playlist,
      'template' => $this->template,
      'animations' => [],
    ];
    foreach($this->animations as $anim) {
      $o['animations'][$id] = $anim->serialize();
    }
    return $o;
  }

  public function deserialize($data) {
    if(array_key_exists('id', $data)) {
      $this->ID = $data['id'];
    }
    $this->playlist = $data['playlist'];
    $this->template = $data['template'];
    foreach($data['animations'] as $id=>$animdata) {
      $anim = new Animation();
      $anim->deserialize($animdata, $id);
      $this->animationsByID[$anim->ID] = $anim;
      $this->animations[] = $anim;
    }
  }

  public function pick() {
    $len = count($this->animations);
    switch($len) {
      case 0:
        return null;
      case 1:
        return $this->animations[0];
      default:
        return $this->animations[random_int(0, count($this->animations)-1)];
    }
  }
}
