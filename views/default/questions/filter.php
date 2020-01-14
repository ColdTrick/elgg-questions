<?php

$tags = get_input('tags', []);
if (!is_array($tags)) {
	$tags = [$tags];
}

$options = elgg_extract('options', $vars);
$options['threshold'] = 2;
$options['limit'] = 10;

$options['wheres'] = [];
$options['wheres'][] = function(\Elgg\Database\QueryBuilder $qb, $main_alias) use ($tags) {
	$subquery = $qb->subquery('metadata', 'md_sub');
	$subquery->select('md_sub.entity_guid');
	
	foreach ($tags as $index => $tag) {

		$md = $subquery->joinMetadataTable('md_sub', 'entity_guid', null, 'inner', "mdf{$index}");
	
		$subquery->andWhere($qb->compare("{$md}.name", '=', 'tags', ELGG_VALUE_STRING));
		$subquery->andWhere($qb->compare("{$md}.value", '=', $tag, ELGG_VALUE_STRING));
	}
	
	return $qb->compare("{$main_alias}.entity_guid", 'in', $subquery->getSQL());
};

$menu_options = [
	'class' => ['elgg-menu-hz'],
];

$content_options = [
	'class' => ['questions-tags-filter'],
];

$items = [];
foreach ($tags as $tag) {
	$new_tags = $tags;
	unset($new_tags[array_search($tag, $new_tags)]);
	
	if (empty($new_tags)) {
		$new_tags = null;
	}
	
	$items[] = ElggMenuItem::factory([
		'name' => $tag,
		'text' => $tag,
		'class' => 'elgg-state-active',
		'icon_alt' => 'delete',
		'href' => elgg_http_add_url_query_elements(current_page_url(), ['tags' => $new_tags]),
	]);
}

if (count($tags) < 5) {
	$available_tags = elgg_get_tags($options);
	
	foreach ($available_tags as $tag) {
		if (in_array($tag->tag, $tags)) {
			continue;
		}
		
		$new_tags = $tags;
		$new_tags[] = $tag->tag;
		$new_tags = array_unique($new_tags);
		
		$items[] = ElggMenuItem::factory([
			'name' => $tag->tag,
			'text' => $tag->tag,
			'href' => elgg_http_add_url_query_elements(current_page_url(), ['tags' => $new_tags]),
		]);
	}
}

if (empty($tags) && !empty($items)) {
	// add show filter
	$content_options['class'][] = 'hidden';
	
	elgg_register_menu_item('filter:questions', [
		'name' => 'show_tags',
		'icon' => 'filter',
		'text' => elgg_echo('filter'),
		'href' => false,
		'priority' => 9999,
		'data-toggle-selector' => '.questions-tags-filter',
		'rel' => 'toggle',
	]);
}

$menu_options['items'] = $items;

$content = elgg_format_element('strong', [], elgg_echo('filter') . ': ');
$content .= elgg_view_menu('questions_tags', $menu_options);

echo elgg_format_element('div', $content_options, $content);
