<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

use tool_lifecycle\entity\step_subplugin;
use tool_lifecycle\manager\step_manager;
use tool_lifecycle\manager\settings_manager;
use tool_lifecycle\manager\subplugin_manager;
use tool_lifecycle\manager\workflow_manager;

/**
 * Tests the settings manager.
 * @package    tool_lifecycle
 * @category   test
 * @group      tool_lifecycle
 * @copyright  2017 Tobias Reischmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_lifecycle_settings_manager_testcase extends \advanced_testcase {

    /** step_subplugin */
    private $step;
    private $trigger;
    private $workflow;

    const EMAIL_VALUE = 'value';
    const STARTDELAY_VALUE = 100;

    public function setUp() {
        $this->resetAfterTest(false);
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_lifecycle');

        $this->workflow = $generator->create_workflow();
        $this->step = new step_subplugin('instancename', 'email', $this->workflow->id);
        step_manager::insert_or_update($this->step);
        $this->trigger = \tool_lifecycle\manager\trigger_manager::get_triggers_for_workflow($this->workflow->id)[0];
    }

    /**
     * Test setting and getting settings data for steps.
     */
    public function test_set_get_step_settings() {
        $data = new stdClass();
        $data->subject = self::EMAIL_VALUE;
        settings_manager::save_settings($this->step->id, SETTINGS_TYPE_STEP, $this->step->subpluginname, $data);
        $settings = settings_manager::get_settings($this->step->id, SETTINGS_TYPE_STEP);
        $this->assertArrayHasKey('subject', $settings, 'No key \'subject\' in returned settings array');
        $this->assertEquals(self::EMAIL_VALUE, $settings['subject']);
    }

    /**
     * Test setting and getting settings data for triggers.
     */
    public function test_set_get_trigger_settings() {
        $data = new stdClass();
        $data->delay = self::STARTDELAY_VALUE;
        settings_manager::save_settings($this->trigger->id, SETTINGS_TYPE_TRIGGER, $this->trigger->subpluginname, $data);
        $settings = settings_manager::get_settings($this->trigger->id, SETTINGS_TYPE_TRIGGER);
        $this->assertArrayHasKey('delay', $settings, 'No key \'delay\' in returned settings array');
        $this->assertEquals(self::STARTDELAY_VALUE, $settings['delay']);
    }

    /**
     * Test correct removal of setting, if steps, triggers or workflows are deleted.
     */
    public function test_remove_workflow() {
        global $DB;
        $data = new stdClass();
        $data->subject = self::EMAIL_VALUE;
        settings_manager::save_settings($this->step->id, SETTINGS_TYPE_STEP, $this->step->subpluginname, $data);
        $data = new stdClass();
        $data->delay = 100;
        settings_manager::save_settings($this->trigger->id, SETTINGS_TYPE_TRIGGER, $this->trigger->subpluginname, $data);
        $settingsstep = $DB->get_records('tool_lifecycle_settings', array('instanceid' => $this->step->id,
            'type' => SETTINGS_TYPE_STEP));
        $this->assertNotEmpty($settingsstep);
        $settingstrigger = $DB->get_records('tool_lifecycle_settings', array('instanceid' => $this->trigger->id,
            'type' => SETTINGS_TYPE_TRIGGER));
        $this->assertNotEmpty($settingstrigger);
        workflow_manager::remove($this->workflow->id);
        $settingsstep = $DB->get_records('tool_lifecycle_settings', array('instanceid' => $this->step->id,
            'type' => SETTINGS_TYPE_STEP));
        $this->assertEmpty($settingsstep);
        $settingstrigger = $DB->get_records('tool_lifecycle_settings', array('instanceid' => $this->trigger->id,
            'type' => SETTINGS_TYPE_TRIGGER));
        $this->assertEmpty($settingstrigger);
    }

}