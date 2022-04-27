<?php

class customer_email_validationemailsentsuccessModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $this->setTemplate('module:customer_email_validation/views/templates/front/email-sent-success.tpl');
    }
}