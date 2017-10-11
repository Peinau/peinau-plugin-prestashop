<?php
if (!defined('_PS_VERSION_')) {
  exit;
}
 
class Peinau extends PaymentModule
{
  public function __construct()
  {
    $this->name = 'Peinau';
    $this->tab = 'payments_gateways';
    $this->version = '1.0.0';
    $this->author = 'Peinau';
    $this->need_instance = 0;
    $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_); 
    $this->bootstrap = true;
 
    parent::__construct();
 
    $this->displayName = $this->l('Peinau');
    $this->description = $this->l('Haz tus pagos con Peinau!');
 
    $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
 
    if (!Configuration::get('MYMODULE_NAME')) {
      $this->warning = $this->l('No name provided');
    }
  }

  public function install()
  {
    if (!parent::install()) {
      return false;
    }
    return true;
  }

  public function uninstall()
  {
    if (!parent::uninstall()) {
      return false;
    }
   
    return true;
  }
}
