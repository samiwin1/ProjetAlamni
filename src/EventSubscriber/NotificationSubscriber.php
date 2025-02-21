<?php
namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\ReponsereclamationRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Bundle\SecurityBundle\Security;

class NotificationSubscriber implements EventSubscriberInterface
{
    private ReponsereclamationRepository $reponsereclamationRepository;
    private RequestStack $requestStack;
    private Security $security;

    public function __construct(ReponsereclamationRepository $reponsereclamationRepository, RequestStack $requestStack, Security $security)
    {
        $this->reponsereclamationRepository = $reponsereclamationRepository;
        $this->requestStack = $requestStack;
        $this->security = $security;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $unreadResponsesCount = $this->reponsereclamationRepository->countUnreadResponsesForUser($user->getEmail());

        $request->attributes->set('unreadResponsesCount', $unreadResponsesCount);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ControllerEvent::class => 'onKernelController',
        ];
    }
}
