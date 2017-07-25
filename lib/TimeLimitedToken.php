<?php

namespace OCA\OJSXC;

class TimeLimitedToken {
   public static function generateUser($node, $domain, $secret, $ttl = 60*60, $time = null) {
      if (!isset($time) || $time === null) {
         $time = time();
      }

      $jid =  $node. '@' . $domain;
      $expiry = $time + $ttl;

      $version = hex2bin('00');
      $secretID = substr(hash('sha256', $secret, true), 0, 2);
      $header = $secretID.pack('N', $expiry);
      $challenge = $version.$header.$jid;
      $hmac = hash_hmac('sha256', $challenge, $secret, true);
      $token = $version.substr($hmac, 0, 16).$header;

      // format as "user-friendly" base64
      $token = str_replace('=', '', strtr(base64_encode($token),
       'OIl', '-$%'));

      return $token;
   }

   public static function generateTURN($uid, $secret, $ttl = 3600 * 24, $time = null) {
      if (!isset($time) || $time === null) {
         $time = time();
      }

      $username = ($time + $ttl).':'.$uid;
      $credential = base64_encode(hash_hmac('sha1', $username, $secret, true));

      return [$username, $credential];
   }
}
