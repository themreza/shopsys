<?php

namespace Shopsys\FrameworkBundle\Model\Security;

use Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageTrait;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class FrontLogoutHandler implements LogoutSuccessHandlerInterface, AuthenticationFailureHandlerInterface
{
    use FlashMessageTrait;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade
     */
    protected $orderFlowFacade;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser
     */
    protected $currentCustomerUser;

    /**
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade $orderFlowFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     */
    public function __construct(
        RouterInterface $router,
        OrderFlowFacade $orderFlowFacade,
        CurrentCustomerUser $currentCustomerUser
    ) {
        $this->router = $router;
        $this->orderFlowFacade = $orderFlowFacade;
        $this->currentCustomerUser = $currentCustomerUser;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function onLogoutSuccess(Request $request)
    {
        $this->orderFlowFacade->resetOrderForm();
        $url = $this->router->generate('front_homepage');
        $request->getSession()->remove(LoginAsUserFacade::SESSION_LOGIN_AS);
        $request->getSession()->migrate();

        return new RedirectResponse($url);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Security\Core\Exception\AuthenticationException $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        d('something');
        if ($this->currentCustomerUser->findCurrentCustomerUser() !== null) {
            $this->addErrorFlash(t('There was an error. If you really want to log out, please try it again.'));
        }

        $referer = $request->headers->get('referer');

        return new RedirectResponse($referer == null ? $this->router->generate('front_homepage') : $referer);
    }
}
