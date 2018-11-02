<?php
/**
 * Add question page
 *
 * @package ElggQuestions
 */

$page_owner = elgg_get_page_owner_entity();

elgg_push_collection_breadcrumbs('object', ElggQuestion::SUBTYPE, $page_owner);

$title = elgg_echo('questions:add');

$form_vars = [];
if (questions_limited_to_groups()) {
	$form_vars['class'] = 'questions-validate-container';
}
$body_vars = questions_prepare_question_form_vars();
$content = elgg_view_form('object/question/save', $form_vars, $body_vars);

$body = elgg_view_layout('default', [
	'title' => $title,
	'content' => $content,
]);

echo elgg_view_page($title, $body);
