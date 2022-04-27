<?php

class customer_email_validationactivationfailureModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $this->setTemplate('module:customer_email_validation/views/templates/front/activation-failure.tpl');
    }
}
