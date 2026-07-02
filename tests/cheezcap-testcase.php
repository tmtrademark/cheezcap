<?php

/**
 * Base unit test class for Cheez CAP
 */
class CheezeCap_TestCase extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		global $cheezecap;
		$this->_ccap = $cheezecap;
	}
}
