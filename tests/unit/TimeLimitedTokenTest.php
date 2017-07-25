<?php

namespace OCA\OJSXC;

use PHPUnit\Framework\TestCase;

class TimeLimitedTokenTest extends TestCase {
   public function testGenerateUser() {
      $this->assertEquals(
         'AJP4Mvv5P8qZZJcENQhzfH$ruF%1458',
         TimeLimitedToken::generateUser('foo', 'bar', 'secret', 60*60, 1500894607)
      );

      $this->assertEquals(
         'AELcjDTQjxEJptXaWb29gkt+LF%2Yi8',
         TimeLimitedToken::generateUser('foo-bar', 'localhost.xyz', 'AJP4Mvv5P8', 60*60*10, 1500894607)
      );

      $this->assertEquals(
         'AEU+Upmh-jRtoHQ2Um1cYMcMV1%2Yi8',
         TimeLimitedToken::generateUser('foo.bar', 'local.host.xyz', 'iiGTp+LF%2', 60*60*10, 1500894607)
      );
   }

   public function testGenerateTURN() {
      $this->assertEquals(
         [(60*60 + 1500894607).':foobar', 'u66TdvZP9USnoCeOBFtVQa4DCkw='],
         TimeLimitedToken::generateTURN('foobar', 'secret', 60*60, 1500894607)
      );

      $this->assertEquals(
         [(3600 * 24 + 1500894607).':foo.bar', 'zfLkyJlJPx+KnLo5eLEUwJXDbGo='],
         TimeLimitedToken::generateTURN('foo.bar', 'CeOBFtVQa', 3600 * 24, 1500894607)
      );

      $this->assertEquals(
         [(3600 * 24 + 1500894607).':foo:bar', 'e+dKdn0JtGWccYCJ3NKaDUD6JZk='],
         TimeLimitedToken::generateTURN('foo:bar', 'nLo5eLEUwJXD', 3600 * 24, 1500894607)
      );

      $this->assertEquals(
         [(3600 * 24 + 1500894607).':foobar', 'q01XUfO0p37h5dGDd5R2PO2RhpM='],
         TimeLimitedToken::generateTURN('foobar', 'nLo5eLEUwJXD', 3600 * 24, 1500894607)
      );
   }
}
