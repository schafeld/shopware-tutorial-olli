<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Learning\Bundle\Event\CustomWelcomeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WelcomeMessageSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CustomWelcomeEvent::class => 'onWelcomeMessage',
        ];
    }

    public function onWelcomeMessage(CustomWelcomeEvent $event): void
    {
        // Modify the message, add timestamp
        $originalMessage = $event->getMessage();
        $timestamp = (new \DateTime())->format('Y-m-d H:i:s');

        $modifiedMessage = sprintf('%s [Generated at %s]', $originalMessage, $timestamp);
        $event->setMessage($modifiedMessage);
    }
}