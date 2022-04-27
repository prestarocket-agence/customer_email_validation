<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Customer_email_validation extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'customer_email_validation';
        $this->tab = 'administration';
        $this->version = '0.1.0';
        $this->author = '@CastoGraziano';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Customer Email Validation');
        $this->description = $this->l('PrestaShop module to validate customer email after account registration');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('CUSTOMER_EMAIL_VALIDATION_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') && 
            $this->registerHook('actionCustomerAccountAdd')  &&
            Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'customer_email_confirmation_token` (
                     `id_customer_email_confirmation_token` int(11) unsigned NOT NULL AUTO_INCREMENT,
                     `id_customer` INT( 11 ) UNSIGNED NOT NULL,
                     `token` CHAR(32) NOT NULL,
                     `issued_on` CHAR(32) NOT NULL,
                     PRIMARY KEY (`id_customer_email_confirmation_token`)
                     ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CUSTOMER_EMAIL_VALIDATION_LIVE_MODE');

        return parent::uninstall() && Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'customer_email_confirmation_token`;');
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitCustomer_email_validationModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCustomer_email_validationModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'CUSTOMER_EMAIL_VALIDATION_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'CUSTOMER_EMAIL_VALIDATION_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'CUSTOMER_EMAIL_VALIDATION_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'CUSTOMER_EMAIL_VALIDATION_LIVE_MODE' => Configuration::get('CUSTOMER_EMAIL_VALIDATION_LIVE_MODE', true),
            'CUSTOMER_EMAIL_VALIDATION_ACCOUNT_EMAIL' => Configuration::get('CUSTOMER_EMAIL_VALIDATION_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'CUSTOMER_EMAIL_VALIDATION_ACCOUNT_PASSWORD' => Configuration::get('CUSTOMER_EMAIL_VALIDATION_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookActionCustomerAccountAdd($params)
    {
        $disable_customer_result = self::disableAndLogoutCustomer($params['newCustomer']->id);

        if (!$disable_customer_result) {
            Tools::redirect($this->context->link->getModuleLink($this->name, 'emailsenterror'));
        }

        if(!self::sendConfirmationEmail($params['newCustomer']->id)) {
            Tools::redirect($this->context->link->getModuleLink($this->name, 'emailsenterror'));
        }else{
            Tools::redirect($this->context->link->getModuleLink($this->name, 'emailsentsuccess'));
        }
    }

    private static function generateTokenUrl($id_customer){
        $token = md5(uniqid(rand(), true));

        $result = Db::getInstance()->insert(
            'customer_email_confirmation_token',
            array(
                'id_customer' => (int) $id_customer,
                'token' => $token,
                'issued_on' => date('Y-m-d H:i:s')
            )
        );

        if(false === $result){
            return false;
        }
        
        $token_url = $this->context->link->getModuleLink($this->name, 'activateaccount') . '?token=' . $token;
        
        return $token_url;
    }

    private static function sendConfirmationEmail($id_customer){

        $token_url = self::generateTokenUrl($id_customer);

        if(false === $token_url){
            return false;
        }

        $customer = new Customer($id_customer);
        $customer->getFields();

        Mail::Send($this->context->customer->id_lang,
                   'confirm_customer_email',
                   $this->l('Email Confirmation'),
                   array('{firstname}' => $customer->firstname,
                         '{lastname}' => $customer->lastname,
                         '{email}' => $customer->email,
                         '{link}' => $token_url),
                   $customer->email,
                   NULL,
                   NULL,
                   NULL,
                   NULL,
                   NULL,
            _PS_MODULE_DIR_ . 'customer_email_validation/mails');
        
        return true;
    }

    private static function disableAndLogoutCustomer($id_customer){
        $customer = new Customer($id_customer);
        $customer->active = 0;
        $customer->update();

        $customer->logout();

        return true;
    }
}
