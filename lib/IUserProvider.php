<?php


namespace OCA\OJSXC;

interface IUserProvider {

	/**
	 * @brief Search all users for which the current users has access to.
	 * @return User[]
	 */
	public function getAllUsers();

	/**
	 * @brief Search all users for which the provided user has access to.
	 * @param User $user
	 * @return User[]
	 */
	public function getAllUsersForUser(User $user);

	/**
	 * @brief Search all users for which the provided user has access to.
	 * @param string $uid
	 * @return User[]
	 */
	public function getAllUsersForUserByUID($uid);

	/**
	 * @brief Checks if the current user can interact with the provided user
	 * @param User $user
	 * @return bool
	 */
	public function hasUser(User $user);

	/**
	 * @brief Checks if the current user can interact with the provided user identified by it's UID.
	 * @param string $uid the uid of the user
	 * @return bool
	 */
	public function hasUserByUID($uid);

	/**
	 * @brief Checks if user1 can interact with user2
	 * @param User $user1
	 * @param User $user2
	 * @return bool
	 */
	public function hasUserForUser(User $user1, User $user2);

	/**
	 * @brief Checks if user1 can interact with the user2 identified by it's UID.
	 * @param string $uid1
	 * @param string $uid2
	 * @return bool
	 */
	public function hasUserForUserByUID($uid1, $uid2);

}