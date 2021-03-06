<?php

require_once SERVICE_PATH . 'User.php';

/**
 * Test class for UserService.
 * Generated by PHPUnit on 2012-07-28 at 07:07:58.
 */
class UserServiceTest extends DK_TestCase {

    /**
     * @var UserService
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp() {
        parent::setUp();
        $this->object = new UserService;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown() {
        
    }

    /**
     * @covers UserService::getUserInfo
     * @todo Implement testGetUserInfo().
     */
    public function testGetUserInfo() {
        $post = $this->object->getUserInfo('1000001000');
        $this->assertEquals(23, count($post));
    }

    /**
     * @covers UserService::getUserList
     * @todo Implement testGetUserList().
     */
    public function testGetUserList() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers UserService::getUserListByCode
     * @todo Implement testGetUserListByCode().
     */
    public function testGetUserListByCode() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers UserService::getUserInfoByUsername
     * @todo Implement testGetUserInfoByUsername().
     */
    public function testGetUserInfoByUsername() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers UserService::getShortInfoByIds
     * @todo Implement testGetShortInfoByIds().
     */
    public function testGetShortInfoByIds() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers UserService::setShortInfo
     * @todo Implement testSetShortInfo().
     */
    public function testSetShortInfo() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers UserService::deleteShortInfo
     * @todo Implement testDeleteShortInfo().
     */
    public function testDeleteShortInfo() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers UserService::getShortInfo
     * @todo Implement testGetShortInfo().
     */
    public function testGetShortInfo() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers UserService::getMultiShortInfo
     * @todo Implement testGetMultiShortInfo().
     */
    public function testGetMultiShortInfo() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers UserService::setAppMenuCover
     * @todo Implement testSetAppMenuCover().
     */
    public function testSetAppMenuCover() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

}

?>
