<?php

namespace ColdTrick\Questions;

use Elgg\Menu\MenuItems;
use Elgg\Router\Route;

class Menus {
	
	/**
	 * Add menu items to the owner_block menu
	 *
	 * @param \Elgg\Hook $hook 'register', 'menu:owner_block'
	 *
	 * @return void|MenuItems
	 */
	public static function registerOwnerBlock(\Elgg\Hook $hook) {
		
		$items = $hook->getValue();
		
		$entity = $hook->getEntityParam();
		if ($entity instanceof \ElggGroup && $entity->isToolEnabled('questions')) {
			$items[] = \ElggMenuItem::factory([
				'name' => 'questions',
				'href' => elgg_generate_url('collection:object:question:group', [
					'guid' => $entity->guid,
				]),
				'text' => elgg_echo('questions:group'),
			]);
		} elseif ($entity instanceof \ElggUser) {
			$items[] = \ElggMenuItem::factory([
				'name' => 'questions',
				'href' => elgg_generate_url('collection:object:question:owner', [
					'username' => $entity->username,
				]),
				'text' => elgg_echo('questions'),
			]);
		}
		
		return $items;
	}
	
	/**
	 * Add menu items to the entity menu
	 *
	 * @param \Elgg\Hook $hook hook
	 *
	 * @return void|MenuItems
	 */
	public static function registerEntity(\Elgg\Hook $hook) {
			
		$entity = $hook->getEntityParam();
		if (!$entity instanceof \ElggAnswer) {
			return;
		}
						
		if (!$entity->canMarkAnswer()) {
			return;
		}
		
		$result = $hook->getValue();
		
		$result[] = \ElggMenuItem::factory([
			'name' => 'questions-mark',
			'text' => elgg_echo('questions:menu:entity:answer:mark'),
			'href' => elgg_generate_action_url('answers/toggle_mark', [
				'guid' => $entity->guid,
			]),
			'icon' => 'check',
			'item_class' => $entity->isCorrectAnswer() ? 'hidden' : '',
			'data-toggle' => 'questions-unmark',
		]);

		$result[] = \ElggMenuItem::factory([
			'name' => 'questions-unmark',
			'text' => elgg_echo('questions:menu:entity:answer:unmark'),
			'href' => elgg_generate_action_url('answers/toggle_mark', [
				'guid' => $entity->guid,
			]),
			'icon' => 'undo',
			'item_class' => $entity->isCorrectAnswer() ? '' : 'hidden',
			'data-toggle' => 'questions-mark',
		]);

		return $result;
	}
	
	/**
	 * Add menu items to the filter menu
	 *
	 * @param \Elgg\Hook $hook 'register', 'menu:filter'
	 *
	 * @return void|MenuItems
	 */
	public static function registerFilter(\Elgg\Hook $hook) {
		
		if (!elgg_in_context('questions')) {
			return;
		}
		
		/* @var $items MenuItems */
		$items = $hook->getValue();
		
		$page_owner = elgg_get_page_owner_entity();
		
		// remove friends
		$items->remove('friend');
		
		if ($page_owner instanceof \ElggGroup) {
			$items->remove('mine');
			
			$all = $items->get('all');
			if ($all instanceof \ElggMenuItem) {
				$all->setHref(elgg_generate_url('collection:object:question:group', [
					'guid' => $page_owner->guid,
				]));
				
				$route = _elgg_services()->request->getRoute();
				if ($route instanceof Route && !get_input('tags')) {
					if ($route->getName() === 'collection:object:question:group') {
						$all->setSelected(true);
					}
				}
				
				$items->add($all);
			}
		}
		
		// add tags search
		$session = elgg_get_session();
		$route = false;
		$route_params = [];
		$tags = get_input('tags');
		if (!empty($tags)) {
			$route = 'collection:object:question:all';
			if ($page_owner instanceof \ElggUser) {
				$route = 'collection:object:question:owner';
				$route_params['username'] = $page_owner->username;
			} elseif ($page_owner instanceof \ElggGroup) {
				$route = 'collection:object:question:group';
				$route_params['guid'] = $page_owner->guid;
			}
			
			$session->set("questions_tags_{$page_owner->guid}", [
				'tags' => $tags,
				'route' => $route,
				'route_params' => $route_params,
			]);
		} elseif ($session->has("questions_tags_{$page_owner->guid}")) {
			$settings = $session->get("questions_tags_{$page_owner->guid}");
			
			$tags = elgg_extract('tags', $settings);
			$route = elgg_extract('route', $settings);
			$route_params = (array) elgg_extract('route_params', $settings, []);
		}
		
		if (!empty($tags) && !empty($route)) {
			$tags_string = $tags;
			if (is_array($tags_string)) {
				$tags_string = implode(', ', $tags_string);
			}
			
			$route_params['tags'] = $tags;
			
			$items[] = \ElggMenuItem::factory([
				'name' => 'questions_tags',
				'text' => elgg_echo('questions:menu:filter:tags', [$tags_string]),
				'href' => elgg_generate_url($route, $route_params),
				'is_trusted' => true,
				'priority' => 600,
			]);
		}
		
		if (questions_is_expert()) {
			$items[] = \ElggMenuItem::factory([
				'name' => 'todo',
				'text' => elgg_echo('questions:menu:filter:todo'),
				'href' => elgg_generate_url('collection:object:question:todo'),
				'priority' => 700,
			]);
	
			if ($page_owner instanceof \ElggGroup && questions_is_expert($page_owner)) {
				$items[] = \ElggMenuItem::factory([
					'name' => 'todo_group',
					'text' => elgg_echo('questions:menu:filter:todo_group'),
					'href' => elgg_generate_url('collection:object:question:todo', [
						'group_guid' => $page_owner->guid,
					]),
					'priority' => 710,
				]);
			}
		}
	
		if (questions_experts_enabled()) {
			$route_params = [];
			if ($page_owner instanceof \ElggGroup) {
				$route_params['group_guid'] = $page_owner->guid;
			}
	
			$items[] = \ElggMenuItem::factory([
				'name' => 'experts',
				'text' => elgg_echo('questions:menu:filter:experts'),
				'href' => elgg_generate_url('collection:object:question:experts', $route_params),
				'priority' => 800,
			]);
		}
	
		return $items;
	}
	
	/**
	 * Add menu items to the user_hover menu
	 *
	 * @param \Elgg\Hook $hook 'register', 'menu:user_hover'
	 *
	 * @return void|MenuItems
	 */
	public static function registerUserHover(\Elgg\Hook $hook) {
		
		// are experts enabled
		if (!questions_experts_enabled()) {
			return;
		}
		
		// get the user for this menu
		$user = $hook->getEntityParam();
		if (!$user instanceof \ElggUser) {
			return;
		}
		
		// get page owner
		$page_owner = elgg_get_page_owner_entity();
		if (!$page_owner instanceof \ElggGroup) {
			$page_owner = elgg_get_site_entity();
		}
		
		// can the current person edit the page owner, to assign the role
		// and is the current user not the owner of this page owner
		if (!$page_owner->canEdit()) {
			return;
		}
		
		$items = $hook->getValue();
		
		$is_expert = check_entity_relationship($user->guid, QUESTIONS_EXPERT_ROLE, $page_owner->guid);
		
		$items[] = \ElggMenuItem::factory([
			'name' => 'questions_expert',
			'icon' => 'level-up-alt',
			'text' => elgg_echo('questions:menu:user_hover:make_expert'),
			'href' => elgg_generate_action_url('questions/toggle_expert', [
				'user_guid' => $user->guid,
				'guid' => $page_owner->guid,
			]),
			'section' => ($page_owner instanceof \ElggSite) ? 'admin' : 'default',
			'data-toggle' => 'questions-expert-undo',
			'item_class' => $is_expert ? 'hidden' : null,
		]);
		
		$items[] = \ElggMenuItem::factory([
			'name' => 'questions_expert_undo',
			'icon' => 'level-down-alt',
			'text' => elgg_echo('questions:menu:user_hover:remove_expert'),
			'href' => elgg_generate_action_url('questions/toggle_expert', [
				'user_guid' => $user->guid,
				'guid' => $page_owner->guid,
			]),
			'section' => ($page_owner instanceof \ElggSite) ? 'admin' : 'default',
			'data-toggle' => 'questions-expert',
			'item_class' => $is_expert ? null : 'hidden',
		]);
		
		return $items;
	}
}
