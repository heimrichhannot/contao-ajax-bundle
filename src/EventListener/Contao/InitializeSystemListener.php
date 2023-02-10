<?php

namespace HeimrichHannot\AjaxBundle\EventListener\Contao;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use HeimrichHannot\AjaxBundle\Manager\AjaxManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @Hook("initializeSystem")
 */
class InitializeSystemListener
{

    private RequestStack              $requestStack;
    private ScopeMatcher              $scopeMatcher;
    private CsrfTokenManagerInterface $tokenManager;
    private ParameterBagInterface     $parameterBag;
    private AjaxManager               $ajaxManager;

    public function __construct(
        RequestStack $requestStack,
        ScopeMatcher $scopeMatcher,
        CsrfTokenManagerInterface $tokenManager,
        ParameterBagInterface $parameterBag,
        AjaxManager $ajaxManager
    ) {
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->tokenManager = $tokenManager;
        $this->parameterBag = $parameterBag;
        $this->ajaxManager  = $ajaxManager;
    }

    public function __invoke(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || $this->scopeMatcher->isBackendRequest($request)) {
            return;
        }

        if (!$request->isXmlHttpRequest()) {
            return;
        }

        // improved REQUEST_TOKEN handling within front end mode
        if ($request->isMethod('POST')
            && !$this->tokenManager->isTokenValid(new CsrfToken(
                $this->parameterBag->get('contao.csrf_token_name'),
                $request->request->get('REQUEST_TOKEN')
            ))
        ) {
            $this->ajaxManager->setRequestTokenExpired();
        }
    }
}