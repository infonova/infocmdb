<?php

class Dao_AnnouncementTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    /**
     * @var Dao_Announcement
     */
    protected $dao;
    /**
     * @var Db_Announcement
     */
    protected $table;

    protected function _before()
    {
        $this->dao   = new Dao_Announcement();
        $this->table = new Db_Announcement();
    }

    protected function _after()
    {
    }

    // tests

    /**
     * @covers Dao_Announcement::getAllAnnouncements()
     */
    public function testGetAllAnnouncements()
    {
        $searchString = 'announcement1';
        $select       = $this->dao->getAllAnnouncements($searchString);
        $result       = $this->table->fetchAll($select)->toArray();

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey(0, $result);

        $row = $result[0];

        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('show_from_date', $row);
        $this->assertArrayHasKey('show_to_date', $row);
        $this->assertArrayHasKey('type', $row);
        $this->assertArrayHasKey('title_de', $row);
        $this->assertArrayHasKey('title_en', $row);
        $this->assertArrayHasKey('message_de', $row);
        $this->assertArrayHasKey('message_en', $row);
        $this->assertArrayHasKey('is_active', $row);
        $this->assertArrayHasKey('user_id', $row);
        $this->assertArrayHasKey('valid_from', $row);

    }

    public function testGetAnnouncementById()
    {
        $id = $this->tester->grabFromDatabase('announcement', 'id', array('name' => 'announcement1_forfiltering'));

        $row = $this->dao->getAnnouncementById($id);

        $this->assertNotEmpty($row);

        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('show_from_date', $row);
        $this->assertArrayHasKey('show_to_date', $row);
        $this->assertArrayHasKey('type', $row);
        $this->assertArrayHasKey('title_de', $row);
        $this->assertArrayHasKey('title_en', $row);
        $this->assertArrayHasKey('message_de', $row);
        $this->assertArrayHasKey('message_en', $row);
        $this->assertArrayHasKey('is_active', $row);
        $this->assertArrayHasKey('user_id', $row);
        $this->assertArrayHasKey('valid_from', $row);
    }


    /*
     * CRUD Tests
     */
    public function testInsertAnnouncement()
    {
        $announcement = array(
            'name'           => 'dao_test_title',
            'show_from_date' => '2018-02-05 14:15:23',
            'show_to_date'   => '2018-02-05 14:15:23',
            'type'           => 'question',
            'is_active'      => '1',
            'user_id'        => 1
        );

        $this->dao->insertAnnouncement($announcement);
        $this->tester->seeInDatabase('announcement', $announcement);
    }


    public function testUpdateAnnouncement()
    {
        $announcement = array(
            'name'           => 'dao_test_title_before_update',
            'show_from_date' => '2018-02-05 14:15:23',
            'show_to_date'   => '2018-02-05 14:15:23',
            'type'           => 'question',
            'is_active'      => '1',
            'user_id'        => 1
        );

        $id = $this->tester->haveInDatabase('announcement', $announcement);

        $announcement = array(
            'name'           => 'dao_test_title_after_update',
            'show_from_date' => '2019-02-05 14:15:23',
            'show_to_date'   => '2019-02-05 14:27:23',
            'type'           => 'information',
            'is_active'      => '0',
            'user_id'        => 2
        );

        $this->dao->updateAnnouncement($id, $announcement);

        $this->tester->seeInDatabase('announcement', $announcement);
    }


    public function testDeleteAnnouncement()
    {
        $announcement = array(
            'name'           => 'dao_test_title_delete',
            'show_from_date' => '2018-02-05 14:15:23',
            'show_to_date'   => '2018-02-05 14:15:23',
            'type'           => 'question',
            'is_active'      => '1',
            'user_id'        => 1
        );

        $id = $this->tester->haveInDatabase('announcement', $announcement);

        $this->dao->deleteAnnouncement($id);
        $this->tester->cantSeeInDatabase('announcement', $announcement);
    }


}