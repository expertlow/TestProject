<?php

namespace Nermin\UserBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Nermin\UserBundle\Entity\User;

class AvatarCleanupListener implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'postUpdate',
        );
    }

    public function postUpdate(LifecycleEventArgs $event)
    {
        $user = $event->getObject();

        if (!$user instanceof User) {
            return;
        }

        /** @var EntityManager $em */
        $em        = $event->getObjectManager();
        $changeSet = $em->getUnitOfWork()->getEntityChangeSet($user);

        if (isset($changeSet['avatar'][0])) {
            $oldAvatar = $changeSet['avatar'][0];

            unlink($oldAvatar);
        }
    }
}
