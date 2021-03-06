<?php

/*
 * Capabilities directory base class
 */

class CapabilitiesDirectory {

    protected $allcapab = array();
    protected $capabstates = array();
    protected $employees = array();
    protected $availids = array();

    const NO_ID = 'NO_SUCH_CAPABILITY_ID';
    const PER_PAGE = 100;
    const DEFAULT_ORDER = 'ORDER BY `stateid` ASC';
    const URL_CREATE = '?module=capabilities';

    /**
     * @param bool $noloaders Do not protess load subroutines at object creation
     * 
     * @return void
     */
    public function __construct($noloaders = false) {
        if (!$noloaders) {
            //load existing capabilities
            $this->loadCapabilities();
            //load they ids
            $this->loadAllIds();
            //load existing states
            $this->loadCapabStates();
            //load employees
            $this->loadEmployees();
        }
    }

    /**
     * stores all available capab ids into protected prop - used in pagination
     * 
     * @return void
     */
    protected function loadAllIds() {
        $query = "SELECT `id` from `capab`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->availids[$each['id']] = $each['id'];
            }
        }
    }

    /**
     * loads all of available capabilities as protected prop allcapab
     * 
     * @return void
     */
    protected function loadCapabilities() {

        if (!wf_CheckGet(array('page'))) {
            $currPage = 1;
        } else {
            $currPage = vf($_GET['page'], 3);
        }

        $offset = self::PER_PAGE * ($currPage - 1);

        $query = "SELECT * from `capab` " . self::DEFAULT_ORDER . " LIMIT " . $offset . "," . self::PER_PAGE . ";";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allcapab[$each['id']]['id'] = $each['id'];
                $this->allcapab[$each['id']]['date'] = $each['date'];
                $this->allcapab[$each['id']]['address'] = $each['address'];
                $this->allcapab[$each['id']]['phone'] = $each['phone'];
                $this->allcapab[$each['id']]['stateid'] = $each['stateid'];
                $this->allcapab[$each['id']]['notes'] = $each['notes'];
                $this->allcapab[$each['id']]['price'] = $each['price'];
                $this->allcapab[$each['id']]['employeeid'] = $each['employeeid'];
                $this->allcapab[$each['id']]['donedate'] = $each['donedate'];
            }
        }
    }

    /**
     * loads available capability states into protected prop capabstates
     * 
     * @return void
     */
    protected function loadCapabStates() {
        $query = "SELECT * from `capabstates`";
        $all = simple_queryall($query);

        $this->capabstates[0]['id'] = 0;
        $this->capabstates[0]['state'] = __('Was not processed');
        $this->capabstates[0]['color'] = 'FF0000';

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->capabstates[$each['id']]['id'] = $each['id'];
                $this->capabstates[$each['id']]['state'] = $each['state'];
                $this->capabstates[$each['id']]['color'] = $each['color'];
            }
        }
    }

    /**
     * Loads all existing employees into protected employees prop
     * 
     * @return void
     */
    protected function loadEmployees() {
        $query = "SELECT * from `employee`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->employees[$each['id']]['id'] = $each['id'];
                $this->employees[$each['id']]['name'] = $each['name'];
                $this->employees[$each['id']]['active'] = $each['active'];
            }
        }
    }

    /**
     * Renders base capabilities grid
     * 
     * @rerturn string
     */
    public function render() {

        //pagination processing
        $totalcount = sizeof($this->availids);
        if (!wf_CheckGet(array('page'))) {
            $currPage = 1;
        } else {
            $currPage = vf($_GET['page'], 3);
        }

        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Address'));
        $cells.= wf_TableCell(__('Phone'));
        $cells.= wf_TableCell(__('Status'));
        $cells.= wf_TableCell(__('Notes'));
        $cells.= wf_TableCell(__('Price'));
        $cells.= wf_TableCell(__('Employee'));
        $cells.= wf_TableCell(__('Changed'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        $panel = $this->panel();
        $styles = wf_tag('style');
        //making some custom styles
        if (!empty($this->capabstates)) {
            foreach ($this->capabstates as $ia => $eachstate) {
                $styles.='.capab_' . $eachstate['id'] . ' { background-color:#' . $eachstate['color'] . '; color: #FFFFFF; } ';
            }
        }
        $styles.=wf_tag('style', true);

        if (!empty($this->allcapab)) {
            foreach ($this->allcapab as $io => $each) {
                $stateName = @$this->capabstates[$each['stateid']]['state'];
                $employeeName = @$this->employees[$each['employeeid']]['name'];

                $actions = '';
                if (cfr('ROOT')) {
                    $actions.= wf_JSAlert("?module=capabilities&delete=" . $each['id'], web_delete_icon(), __('Removing this may lead to irreparable results')) . ' ';
                }
                $actions.= wf_JSAlert("?module=capabilities&edit=" . $each['id'] . '&page=' . $currPage, web_edit_icon(), __('Are you serious'));

                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['date']);
                $cells.= wf_TableCell($each['address']);
                $cells.= wf_TableCell($each['phone']);
                $cells.= wf_TableCell($stateName, '', 'capab_' . $each['stateid']);
                $cells.= wf_TableCell($each['notes']);
                $cells.= wf_TableCell($each['price']);
                $cells.= wf_TableCell($employeeName);
                $cells.= wf_TableCell($each['donedate']);
                $cells.= wf_TableCell($actions);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }



        if ($totalcount > self::PER_PAGE) {
            $paginator = wf_pagination($totalcount, self::PER_PAGE, $currPage, "?module=capabilities", 'ubButton');
        } else {
            $paginator = '';
        }

        $result = $panel;
        $result.= $styles;
        $result.= wf_TableBody($rows, '100%', '0', 'sortable');
        $result.= $paginator;

        return ($result);
    }

    /**
     * delete some capability from database
     * 
     * @param $id - capability id
     * 
     * @return void
     */
    public function deleteCapability($id) {
        $id = vf($id, 3);
        if (isset($this->availids[$id])) {
            $query = "DELETE from `capab` WHERE `id`='" . $id . "'";
            nr_query($query);
            log_register("CAPABILITY DELETE [" . $id . "]");
        } else {
            throw new Exception(self::NO_ID);
        }
    }

    /**
     * creates new capability in database
     * 
     * @param $address - users address
     * @param $phone - users phone
     * @param $notes - text notes to task 
     * 
     * @return integer
     */
    public function addCapability($address, $phone, $notes) {
        $date = curdatetime();
        $address = mysql_real_escape_string($address);
        $phone = mysql_real_escape_string($phone);
        $notes = mysql_real_escape_string($notes);

        $query = "INSERT INTO `capab` (`id` , `date` , `address` , `phone` ,`stateid` ,`notes` ,`price` ,`employeeid` ,`donedate`) 
             VALUES ( NULL , '" . $date . "', '" . $address . "', '" . $phone . "', '0', '" . $notes . "', NULL , NULL , NULL);";

        nr_query($query);
        $lastId = simple_get_lastid('capab');
        log_register("CAPABILITY ADD [" . $lastId . "] `" . $address . "`");
    }

    /**
     * Generates random HTML color
     * 
     * @return string
     */
    protected function genRandomColor() {
        $result = strtoupper(dechex(rand(0, 10000000)));
        return ($result);
    }

    /**
     * returns capability creation form
     * 
     * @param string $address Pre set address fo form
     * @param string $phone  Pre set phone
     * @param string $notes Pre set notes
     * 
     * @return string
     */
    public function createForm($address = '', $phone = '', $notes = '') {
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);

        $inputs = wf_TextInput('newaddress', __('Full address') . $sup, $address, true);
        $inputs.= wf_TextInput('newphone', __('Phone') . $sup, $phone, true);
        $inputs.= __('Notes') . wf_tag('br');
        $inputs.= wf_TextArea('newnotes', '', $notes, true, '40x5');
        $inputs.= wf_Submit(__('Create'));

        $result = wf_Form(self::URL_CREATE, 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * returns capability editing form by existing cap id
     * 
     * @return string
     */
    public function editForm($id) {
        $id = vf($id, 3);
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $curpage = (wf_CheckGet(array('page'))) ? vf($_GET['page'], 3) : 1;
        $result = wf_Link('?module=capabilities&page=' . $curpage, __('Back'), true, 'ubButton');
        $stateSelector = array();
        $employeeSelector = array();
        $employeeSelector['NULL'] = '-';

        if (isset($this->availids[$id])) {
            //states preprocessing
            if (!empty($this->capabstates)) {
                foreach ($this->capabstates as $io => $eachcap) {
                    $stateSelector[$eachcap['id']] = $eachcap['state'];
                }
            }
            //employee preprocessing
            if (!empty($this->employees)) {
                foreach ($this->employees as $ia => $eachemp) {
                    if ($eachemp['active']) {
                        $employeeSelector[$eachemp['id']] = $eachemp['name'];
                    }
                }
            }


            $inputs = wf_TextInput('editaddress', __('Full address') . $sup, $this->allcapab[$id]['address'], true);
            $inputs.= wf_TextInput('editphone', __('Phone') . $sup, $this->allcapab[$id]['phone'], true);
            $inputs.= __('Notes') . wf_tag('br');
            $inputs.= wf_TextArea('editnotes', '', $this->allcapab[$id]['notes'], true, '40x5');
            $inputs.= wf_TextInput('editprice', __('Price'), $this->allcapab[$id]['price'], true);
            $inputs.= wf_Selector('editstateid', $stateSelector, __('Status'), $this->allcapab[$id]['stateid'], true);
            $inputs.= wf_Selector('editemployeeid', $employeeSelector, __('Worker'), $this->allcapab[$id]['employeeid'], true);
            $inputs.= wf_delimiter();
            $inputs.= wf_Submit(__('Save'));

            $result.= wf_Form("", 'POST', $inputs, 'glamour');
        } else {
            throw new Exception(self::NO_ID);
        }
        return ($result);
    }

    /**
     * update capability into database by its id
     * 
     * @param int $id
     * @param int $address
     * @param string $phone
     * @param int $stateid
     * @param string $notes
     * @param string $price
     * @param int $employeeid
     * @throws Exception
     * 
     * @return void
     */
    public function editCapability($id, $address, $phone, $stateid, $notes, $price, $employeeid) {
        $id = vf($id, 3);
        $address = mysql_real_escape_string($address);
        $phone = mysql_real_escape_string($phone);
        $stateid = vf($stateid, 3);
        $price = mysql_real_escape_string($price);
        $employeeid = vf($employeeid, 3);
        $curdate = curdatetime();
        if (isset($this->availids[$id])) {
            simple_update_field('capab', 'donedate', $curdate, "WHERE `id`='" . $id . "';");
            simple_update_field('capab', 'address', $address, "WHERE `id`='" . $id . "';");
            simple_update_field('capab', 'phone', $phone, "WHERE `id`='" . $id . "';");
            simple_update_field('capab', 'stateid', $stateid, "WHERE `id`='" . $id . "';");
            simple_update_field('capab', 'notes', $notes, "WHERE `id`='" . $id . "';");
            simple_update_field('capab', 'price', $price, "WHERE `id`='" . $id . "';");
            simple_update_field('capab', 'employeeid', $employeeid, "WHERE `id`='" . $id . "';");
            log_register("CAPABILITY EDIT [" . $id . "] `" . $address . "`");
        } else {
            throw new Exception(self::NO_ID);
        }
    }

    /**
     * shows currently available capability states in table grid
     * 
     * @return string
     */
    public function statesList() {

        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Status'));
        $cells.= wf_TableCell(__('Color'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');


        if (!empty($this->capabstates)) {
            foreach ($this->capabstates as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['state']);
                $color = wf_tag('font', false, '', 'color="#' . $each['color'] . '"') . $each['color'] . wf_tag('font', true);
                $cells.= wf_TableCell($color);
                if ($each['id'] != 0) {
                    $actions = wf_JSAlert("?module=capabilities&states=true&deletestate=" . $each['id'], web_delete_icon(), __('Removing this may lead to irreparable results'));
                    $actions.= wf_JSAlert("?module=capabilities&states=true&editstate=" . $each['id'], web_edit_icon(), __('Are you serious'));
                } else {
                    $actions = '';
                }
                $cells.= wf_TableCell($actions);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        return ($result);
    }

    /**
     * returns capability states adding form
     * 
     * @return string
     */
    public function statesAddForm() {
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $result = wf_Link('?module=capabilities', __('Back'), true, 'ubButton');
        $inputs = wf_TextInput('createstate', __('New status') . $sup, '', true, '20');
        $inputs.= wf_ColPicker('createstatecolor', __('New status color') . $sup, '#' . $this->genRandomColor(), true, '10');
        $inputs.= wf_Submit(__('Create'));
        $result.= wf_Form("", 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * returns capability states adding form
     * 
     * @param int $id
     * @return string
     */
    public function statesEditForm($id) {

        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $result = wf_Link('?module=capabilities&states=true', __('Back'), true, 'ubButton');
        $inputs = wf_TextInput('editstate', __('New status') . $sup, $this->capabstates[$id]['state'], true, '20');
        $inputs.= wf_ColPicker('editstatecolor', __('New status color') . $sup, '#' . $this->capabstates[$id]['color'], true, '10');
        $inputs.= wf_Submit(__('Save'));
        $result.= wf_Form("", 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * creates new capability state
     * 
     * @param string $state new state label
     * @param string $color new state color
     * 
     * @return void
     */
    public function statesCreate($state, $color) {
        $state = mysql_real_escape_string($state);
        $color = mysql_real_escape_string($color);
        $color = str_replace('#', '', $color);
        $query = "INSERT INTO `capabstates` (`id` , `state` , `color`) 
             VALUES ( NULL , '" . $state . "', '" . $color . "');";
        nr_query($query);
        log_register("CAPABILITY STATE ADD `" . $state . "`");
    }

    /**
     * delete state by its id
     * 
     * @param int $id - state id in database
     * 
     * @return void
     */
    public function statesDelete($id) {
        $id = vf($id, 3);
        if (!empty($id)) {
            $query = "DELETE FROM `capabstates` WHERE `id`='" . $id . "'";
            nr_query($query);
            log_register("CAPABILITY STATE DELETE [" . $id . "]");
        }
    }

    /**
     * updates state into database
     * 
     * @param int    $id    - existing state id
     * @param int    $state - new state title
     * @param string $color - new state color
     * 
     * @return void
     */
    public function statesChange($id, $state, $color) {
        $id = vf($id, 3);
        $state = mysql_real_escape_string($state);
        $color = mysql_real_escape_string($color);
        $color = str_replace('#', '', $color);
        if (!empty($id)) {
            simple_update_field('capabstates', 'state', $state, "WHERE `id`='" . $id . "'");
            simple_update_field('capabstates', 'color', $color, "WHERE `id`='" . $id . "'");
            log_register("CAPABILITY STATE EDIT [" . $id . "] ON `" . $state . "`");
        }
    }

    /**
     * returns capabilities directory control panel
     * 
     * @return string
     */
    protected function panel() {
        $result = '';
        if (cfr('ROOT')) {
            $result.= wf_Link("?module=capabilities&states=true", wf_img('skins/settings.png', __('Modify states')), false, '') . '&nbsp;';
        }
        $result.= wf_modal(__('Create'), __('Create'), $this->createForm(), 'ubButton', '400', '300');

        return ($result);
    }

}

?>