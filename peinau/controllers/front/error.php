<?php

class peinauErrorModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $peinauerrors = array("El pago ha sido rechazado");
        $this->context->smarty->assign('peinauerrors', $peinauerrors);

        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            return $this->setTemplate('error.16.tpl');
        } else {
            return $this->setTemplate('module:peinau/views/templates/front/error.tpl');
        }
    }
}
