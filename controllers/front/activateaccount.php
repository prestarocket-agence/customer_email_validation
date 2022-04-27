<?php

class customer_email_validationactivateaccountModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        if ($this->checkEmailLinkAndActivateCustomerAccount() === true){
            header("Location: ".$this->context->link->getModuleLink('customer_email_validation', 'activation-success'));
            exit;
        }else{
            $this->setTemplate('module:customer_email_validation/views/templates/front/activation-failure.tpl');
        }
    }

    private function checkEmailLinkAndActivateCustomerAccount()
    {
        $token = Tools::getValue('token');
        $id_customer = Db::getInstance()->getRow('SELECT id_customer FROM '._DB_PREFIX_.'customer_email_confirmation_token WHERE token = \''. $token.'\'');
        
        if(false === $id_customer){
            return false;
        }

        $customer = new Customer($id_customer['id_customer']);

        if($customer->id == 0){
            return false;
        }

        $customer->active = 1;
        $customer->deleted = 0;

        $customer->update();

        return true;
    }
}