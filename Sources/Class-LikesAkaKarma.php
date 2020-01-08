<?php

/**
 * Class-LikesAkaKarma.php
 *
 * @package Likes aka Karma
 * @link https://dragomano.ru/mods/likes-aka-karma
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2018-2020 Bugo
 * @license https://opensource.org/licenses/MIT The MIT License
 *
 * @version 0.3
 */

if (!defined('SMF'))
	die('Hacking attempt...');

class LikesAkaKarma
{
	/**
	 * Подключаем хук перед добавлением нового лайка
	 *
	 * @return void
	 */
	public static function hooks()
	{
		add_integration_function('integrate_issue_like_before', 'LikesAkaKarma::likesToKarma', false, __FILE__);
		add_integration_function('integrate_credits', 'LikesAkaKarma::credits', false, __FILE__);
	}

	/**
	 * Принимаем данные лайка и пересчитываем количество репутации
	 *
	 * @param string $type — тип лайка (как правило, "msg")
	 * @param int $content — id сообщения
	 * @return void
	 */
	public static function likesToKarma(&$type, &$content)
	{
		global $smcFunc;

		// Ищем ID автора сообщения
		$request = $smcFunc['db_query']('', '
			SELECT id_member
			FROM {db_prefix}messages
			WHERE id_msg = {int:id_msg}
			LIMIT 1',
			array(
				'id_msg' => $content
			)
		);

		list ($author) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		if (empty($author))
			return;

		// Считаем общее количество всех лайкнутых сообщений автора
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(l.content_id)
			FROM {db_prefix}user_likes AS l
			LEFT JOIN {db_prefix}messages AS m ON (m.id_msg = l.content_id)
			WHERE l.content_type = {string:type}
				AND m.id_member = {int:author}',
			array(
				'type'   => $type,
				'author' => $author
			)
		);

		list ($num_likes) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		// Ищем столбец karma_good от SMF 2.0
		$request = $smcFunc['db_query']('', '
			SHOW COLUMNS FROM {db_prefix}members LIKE \'karma_good\'',
			array()
		);

		if ($smcFunc['db_num_rows']($request)) {
			$request = $smcFunc['db_query']('', '
				SELECT karma_good
				FROM {db_prefix}members
				WHERE id_member = {int:author}
				LIMIT 1',
				array(
					'author' => $author
				)
			);

			list ($karma_good) = $smcFunc['db_fetch_row']($request);
			$smcFunc['db_free_result']($request);
		}

		// Если такой столбец есть, то суммируем значения karma_good и num_likes
		if (!empty($karma_good))
			$num_likes = $num_likes + $karma_good;

		// Обновляем количество репутации
		$result = $smcFunc['db_insert']('replace',
			'{db_prefix}themes',
			array(
				'id_member' => 'int',
				'id_theme'  => 'int',
				'variable'  => 'string',
				'value'     => 'string'
			),
			array(
				(int) $author,
				1,
				'cust_likes',
				(string) $num_likes + 1
			),
			array('id_member', 'id_theme', 'variable')
		);
	}

	/**
	 * Добавляем информацию об авторских правах на страницу action=credits
	 *
	 * @return void
	 */
	public static function credits()
	{
		global $context;

		$context['credits_modifications'][] = '<a href="https://dragomano.ru/mods/likes-aka-karma" target="_blank" rel="noopener">Likes aka Karma</a> &copy; 2018&ndash;2020, Bugo';
	}
}
