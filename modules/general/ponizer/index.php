<?php

$altCfg = $ubillingConfig->getAlter();

if ($altCfg['PON_ENABLED']) {
    if (cfr('PON')) {

        $pon = new PONizer();

        //getting ONU json data for list
        if (wf_CheckGet(array('ajaxonu'))) {
            die($pon->ajaxOnuData());
        }

        //creating new ONU device
        if (wf_CheckPost(array('createnewonu', 'newoltid', 'newmac'))) {
            $onuCreateResult = $pon->onuCreate($_POST['newonumodelid'], $_POST['newoltid'], $_POST['newip'], $_POST['newmac'], $_POST['newserial'], $_POST['newlogin']);
            if ($onuCreateResult) {
                rcms_redirect('?module=ponizer');
            } else {
                show_error(__('This MAC have wrong format'));
            }
        }

        //edits existing ONU in database
        if (wf_CheckPost(array('editonu', 'editoltid', 'editmac'))) {
            $pon->onuSave($_POST['editonu'], $_POST['editonumodelid'], $_POST['editoltid'], $_POST['editip'], $_POST['editmac'], $_POST['editserial'], $_POST['editlogin']);
            rcms_redirect('?module=ponizer&editonu=' . $_POST['editonu']);
        }

        //deleting existing ONU
        if (wf_CheckGet(array('deleteonu'))) {
            $pon->onuDelete($_GET['deleteonu']);
            rcms_redirect('?module=ponizer');
        }

        //assigning ONU with some user
        if (wf_CheckPost(array('assignonulogin', 'assignonuid'))) {
            $pon->onuAssign($_POST['assignonuid'], $_POST['assignonulogin']);
            rcms_redirect('?module=ponizer&editonu=' . $_POST['assignonuid']);
        }



        if (!wf_CheckGet(array('editonu'))) {
            if (wf_CheckGet(array('username'))) {
                //try to detect ONU id by user login
                $login = $_GET['username'];
                $userOnuId = $pon->getOnuIdByUser($login);
                //redirecting to assigned ONU
                if ($userOnuId) {
                    rcms_redirect('?module=ponizer&editonu=' . $userOnuId);
                } else {
                    //rendering assign form
                    show_window(__('ONU assign'), $pon->onuAssignForm($login));
                }
            } else {
                //rendering availavle onu LIST
                show_window(__('ONU directory'), $pon->controls() . $pon->renderOnuList());
            }
        } else {
            //show ONU editing interface
            show_window(__('Edit'), $pon->onuEditForm($_GET['editonu']));
        }
    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module disabled'));
}
?>