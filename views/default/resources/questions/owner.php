<?php
/**
 * Elgg questions plugin owner page
 *
 * @package Questions
 */

use Elgg\EntityNotFoundException;

$page_owner = elgg_get_page_owner_entity();
if (!$page_owner instanceof ElggUser) {
	throw new EntityNotFoundException();
}

elgg_push_collection_breadcrumbs('object', ElggQuestion::SUBTYPE, $page_owner);

elgg_register_title_button('questions', 'add', 'object', ElggQuestion::SUBTYPE);

// prepare options
$options = [
	'type' => 'object',
	'subtype' => 'question',
	'owner_guid' => $page_owner->guid,
	'full_view' => false,
	'list_type_toggle' => false,
	'no_results' => elgg_echo('questions:none'),
	'wheres' => [],
];

$tags = get_input('tags');
if (!empty($tags)) {
	if (is_string($tags)) {
		$tags = string_to_tag_array($tags);
	}
	
	$options['wheres'][] = function(\Elgg\Database\QueryBuilder $qb, $main_alias) use ($tags) {
		$ands = [];
		foreach ($tags as $index => $tag) {
			$md = $qb->joinMetadataTable($main_alias, 'guid', 'tags', 'inner', "md{$index}");
		
			$ands[] = $qb->compare("{$md}.value", '=', $tag, ELGG_VALUE_STRING);
		}
		
		return $qb->merge($ands);
	};
}

// build page elements
$title = elgg_echo('questions:owner', [$page_owner->getDisplayName()]);

$filter = elgg_view('questions/filter', ['options' => $options]);

$content = elgg_list_entities($options);

// build page
$body = elgg_view_layout('content', [
	'title' => $title,
	'content' => $filter . $content,
	'filter_context' => ($page_owner->guid === elgg_get_logged_in_user_guid()) ? 'mine' : '',
]);

// draw page
echo elgg_view_page($title, $body);
