<?php


namespace OCA\OJSXC;

use OCA\OJSXC\Exceptions\Exception;
use OCP\IUserManager;

interface IUserProvider {

	/**
	 * @brief Search all users for which the current users has access to.
	 * @return User[]
	 */
	public function getAllUsers();

	/**
	 * @brief Checks if the current user can interact with the provided user
	 * @param User $user
	 * @return bool
	 */
	public function hasUser(User $user);

}