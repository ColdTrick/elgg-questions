<?php

namespace ColdTrick\Questions\Notifications;

use Elgg\Notifications\NotificationEventHandler;

class CreateAnswerNotificationEventHandler extends NotificationEventHandler {
	
	/**
	 * {@inheritDoc}
	 */
	protected function getNotificationSubject(\ElggUser $recipient, string $method): string {
		return elgg_echo('questions:notifications:answer:create:subject', [$this->getQuestion()->getDisplayName()], $recipient->getLanguage());
	}
	
	/**
	 * {@inheritDoc}
	 */
	protected function getNotificationSummary(\ElggUser $recipient, string $method): string {
		return elgg_echo('questions:notifications:answer:create:summary', [$this->getQuestion()->getDisplayName()], $recipient->getLanguage());
	}
	
	/**
	 * {@inheritDoc}
	 */
	protected function getNotificationBody(\ElggUser $recipient, string $method): string {
		return elgg_echo('questions:notifications:answer:create:message', [
			$this->event->getActor()->getDisplayName(),
			$this->getQuestion()->getDisplayName(),
			$this->event->getObject()->description,
			$this->event->getObject()->getURL(),
		], $recipient->getLanguage());
	}
	
	/**
	 * Get the question for this answer
	 *
	 * @return \ElggQuestion
	 */
	protected function getQuestion(): \ElggQuestion {
		return $this->event->getObject()->getContainerEntity();
	}
}
