<?php


use OCA\OJSXC\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
	public function test()
	{
		$user1 = new User(" test @ 'abc", 'Test123', null);

		$this->assertEquals($user1->getFullName(), 'Test123');
		$this->assertEquals($user1->getUid(), '_ojsxc_esc_space_test_ojsxc_esc_space__ojsxc_esc_at__ojsxc_esc_space__ojsxc_squote_space_abc');
		$user1->setUid('test1');
		$user1->setFullName('test2');
		$this->assertEquals($user1->getUid(), 'test1');
		$this->assertEquals($user1->getFullName(), 'test2');
	}
}
