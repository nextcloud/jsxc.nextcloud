<?php

namespace OCA\OJSXC;

class RawRequest {
   public function get() {
      return file_get_contents('php://input');
   }
}
