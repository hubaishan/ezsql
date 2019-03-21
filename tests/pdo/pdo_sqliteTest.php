<?php

namespace ezsql\Tests;

use ezsql\Database;
use ezsql\Tests\EZTestCase;

class pdo_sqliteTest extends EZTestCase 
{
    /**
     * constant string database port
     */
    const TEST_DB_PORT = '5432';
    /**
     * constant string path and file name of the SQLite test database
     */
    const TEST_SQLITE_DB = './tests/pdo/ez_test.sqlite';

    /**
     * @var ezSQL_pdo
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
	{
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped(
              'The pdo_sqlite Lib is not available.'
            );
        }

        $this->object = Database::initialize('pdo', ['sqlite:' . self::TEST_SQLITE_DB, '', '', array(), true]);
        $this->object->setPrepare();
    } // setUp

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        $this->object = null;
    } // tearDown
     
    /**
     * Here starts the SQLite PDO unit test
     */

    /**
     * @covers ezSQL_pdo::connect
     */
    public function testSQLiteConnect() { 
        //$this->errors = array();
        //set_error_handler(array($this, 'errorHandler'));        
        $this->assertFalse($this->object->connect());
        
        $this->assertTrue($this->object->connect('sqlite:' . self::TEST_SQLITE_DB, '', '', array(), true));
        $this->assertFalse($this->object->connect(null, '', '',array(), false));
        $this->assertFalse($this->object->connect('', '', '',array(), false));
        $this->assertFalse($this->object->connect('null:', '', '',array(), true));
        $this->assertFalse($this->object->connect('', '', '',array(), true));
    } // testSQLiteConnect

    /**
     * @covers ezSQL_pdo::quick_connect
     */
    public function testSQLiteQuick_connect() {
        $this->assertTrue($this->object->quick_connect('sqlite:' . self::TEST_SQLITE_DB, '', '', array(), true));
    } // testSQLiteQuick_connect

    /**
     * @covers ezSQL_pdo::escape
     */
    public function testSQLiteEscape() {
        $this->assertTrue($this->object->connect('sqlite:' . self::TEST_SQLITE_DB, '', '', array(), true));

        $result = $this->object->escape("This is'nt escaped.");

        $this->assertEquals("This is''nt escaped.", $result);
         
        $this->object->disconnect();
        $result = $this->object->escape("Is'nt escaped.");
        $this->assertEquals("Is''nt escaped.", $result);
    } // testSQLiteEscape

    /**
     * @covers ezSQL_pdo::sysdate
     */
    public function testSQLiteSysdate() {
        $this->assertEquals("datetime('now')", $this->object->sysdate());
    } // testSQLiteSysdate

    /**
     * @covers ezSQL_pdo::catch_error
     */
    public function testSQLiteCatch_error() {
        $this->assertTrue($this->object->connect('sqlite:' . self::TEST_SQLITE_DB, '', '', array(), true));
        $this->object->query('DROP TABLE unit_test2');
        $this->assertTrue($this->object->catch_error());
    } // testSQLiteCatch_error

    /**
     * @covers ezSQL_pdo::query
     */
    public function testSQLiteQuery() {
        $this->assertTrue($this->object->connect('sqlite:' . self::TEST_SQLITE_DB, '', '', array(), true));

        $this->assertEquals(0, $this->object->query('CREATE TABLE unit_test(id integer, test_key varchar(50), PRIMARY KEY (ID))'));

        $result = $this->object->query('INSERT INTO unit_test (id, test_key) VALUES (1, \'test 1\');' );
        $this->assertEquals(1, $result);
        $this->assertNull($this->object->catch_error());
        
        $this->object->query('INSERT INTO unit_test (id, test_key2) VALUES (1, \'test 1\');' );
        $this->assertTrue($this->object->catch_error());   
        
        $this->object->disconnect();
        $result = $this->object->query('INSERT INTO unit_test (id, test_key) VALUES (5, \'test 5\');' );
        $this->assertEquals(1, $result);        
        $this->assertNull($this->object->catch_error());   
        
        $this->object->use_trace_log = true;
        $this->assertNotNull($this->object->query('SELECT * FROM unit_test ;')); 
        $this->assertNotNull($this->object->trace_log);
        
        $this->assertFalse($this->object->query('SELECT id2 FROM unit_test ;'));   
        $this->assertTrue($this->object->catch_error());  
        
        $this->assertEquals(1, $this->object->query('DROP TABLE unit_test'));
    } // testSQLiteQuery
    
    /**
     * @covers ezQuery::insert
     */
    public function testInsert()
    {
        $this->assertTrue($this->object->connect('sqlite:' . self::TEST_SQLITE_DB, '', '', array(), true));
        $this->object->query('CREATE TABLE unit_test(id integer, test_key varchar(50), PRIMARY KEY (ID))');

        $result = $this->object->insert('unit_test', array('test_key'=>'test 1' ));
        $this->assertEquals(1, $result);
        $this->assertEquals(1, $this->object->query('DROP TABLE unit_test'));
    }
       
    /**
     * @covers ezQuery::update
     */
    public function testUpdate()
    {
        $this->assertTrue($this->object->connect('sqlite:' . self::TEST_SQLITE_DB, '', '', array(), true));
        $this->object->query('CREATE TABLE unit_test(id integer, test_key varchar(50), test_value varchar(50), PRIMARY KEY (ID))');
        $this->object->insert('unit_test', array('test_key'=>'test 1', 'test_value'=>'testing string 1' ));
        $this->object->insert('unit_test', array('test_key'=>'test 2', 'test_value'=>'testing string 2' ));
        $result = $this->object->insert('unit_test', array('test_key'=>'test 3', 'test_value'=>'testing string 3' ));
        $this->assertEquals($result, 3);
        $unit_test['test_key'] = 'the key string';
        $where="test_key  =  test 1";
        $this->assertEquals(1, $this->object->update('unit_test', $unit_test, $where));
        $this->assertEquals(1, $this->object->update('unit_test', $unit_test, eq('test_key','test 3', _AND),
                                                                            eq('test_value','testing string 3')));
        $where=eq('test_value','testing string 4');
        $this->assertEquals(0, $this->object->update('unit_test', $unit_test, $where));
        $this->assertEquals(1, $this->object->update('unit_test', $unit_test, "test_key  =  test 2"));
        $this->assertEquals(1, $this->object->query('DROP TABLE unit_test'));
    }
    
    /**
     * @covers ezQuery::delete
     */
    public function testDelete()
    {
        $this->assertTrue($this->object->connect('sqlite:' . self::TEST_SQLITE_DB, '', '', array(), true));
        $this->object->query('CREATE TABLE unit_test(id integer, test_key varchar(50), test_value varchar(50), PRIMARY KEY (ID))');
        $this->object->insert('unit_test', array('test_key'=>'test 1', 'test_value'=>'testing string 1' ));
        $this->object->insert('unit_test', array('test_key'=>'test 2', 'test_value'=>'testing string 2' ));
        $this->object->insert('unit_test', array('test_key'=>'test 3', 'test_value'=>'testing string 3' ));   

        $where=array('test_key','=','test 1');
        $this->assertEquals($this->object->delete('unit_test', $where), 1);
        
        $this->assertEquals($this->object->delete('unit_test', 
            array('test_key','=','test 3'),
            array('test_value','=','testing string 3')), 1);
        $where=array('test_value','=','testing 2');
        $this->assertEquals(0, $this->object->delete('unit_test', $where));
        $where="test_key  =  test 2";
        $this->assertEquals(1, $this->object->delete('unit_test', $where));
        $this->assertEquals(1, $this->object->query('DROP TABLE unit_test'));
    }  

    /**
     * @covers ezQuery::selecting
     */
    public function testSelecting()
    {
        $this->assertTrue($this->object->connect('sqlite:' . self::TEST_SQLITE_DB, '', '', array(), true));
        $this->object->query('CREATE TABLE unit_test(id integer, test_key varchar(50), test_value varchar(50), PRIMARY KEY (ID))');
        $this->object->insert('unit_test', array('test_key'=>'test 1', 'test_value'=>'testing string 1' ));
        $this->object->insert('unit_test', array('test_key'=>'test 2', 'test_value'=>'testing string 2' ));
        $this->object->insert('unit_test', array('test_key'=>'test 3', 'test_value'=>'testing string 3' ));   
        
        $result = $this->object->selecting('unit_test');        
        $i = 1;
        foreach ($result as $row) {
            $this->assertEquals($i, $row->id);
            $this->assertEquals('testing string ' . $i, $row->test_value);
            $this->assertEquals('test ' . $i, $row->test_key);
            ++$i;
        }
        
        $where = eq('id','2');
        $result = $this->object->selecting('unit_test', 'id', $this->object->where($where));
        foreach ($result as $row) {
            $this->assertEquals(2, $row->id);
        }
        
        $where = [eq('test_value','testing string 3', _AND), eq('id','3')];
        $result = $this->object->selecting('unit_test', 'test_key', $this->object->where($where));
        foreach ($result as $row) {
            $this->assertEquals('test 3', $row->test_key);
        }      
        
        $result = $this->object->selecting('unit_test', 'test_value', $this->object->where(eq( 'test_key','test 1' )));
        foreach ($result as $row) {
            $this->assertEquals('testing string 1', $row->test_value);
        }
        $this->assertEquals(1, $this->object->query('DROP TABLE unit_test'));
    } 
    
    /**
     * @covers ezSQL_pdo::disconnect
     */
    public function testSQLiteDisconnect() {
        $this->assertTrue($this->object->connect('sqlite:' . self::TEST_SQLITE_DB, '', '', array(), true));

        $this->object->disconnect();

        $this->assertFalse($this->object->isConnected());
    } // testSQLiteDisconnect

    /**
     * @covers ezSQLcore::get_set
     */
    public function testGet_set() {
        $expected = "test_var1 = '1', test_var2 = 'ezSQL test', test_var3 = 'This is''nt escaped.'";
        
        $params = array(
            'test_var1' => 1,
            'test_var2' => 'ezSQL test',
            'test_var3' => "This is'nt escaped."
        );
        
        $this->assertTrue($this->object->connect('sqlite:' . self::TEST_SQLITE_DB, '', '', array(), true));

        $this->assertequals($expected, $this->object->get_set($params)); 
        $this->assertContains('NOW()',$this->object->get_set(array('test_var1' => 1,'test_var2'=>'NOW()')));
        $this->assertContains("test_var2 = 0", $this->object->get_set(array('test_var2'=>'false')));
        $this->assertContains("test_var2 = '1'", $this->object->get_set(array('test_var2'=>'true')));
    } // testSQLiteGet_set
    
    /**
     * @covers ezSQL_pdo::__construct
     */
    public function test__Construct() {         
        $this->errors = array();
        set_error_handler(array($this, 'errorHandler'));    
        
        $pdo = $this->getMockBuilder(ezSQL_pdo::class)
        ->setMethods(null)
        ->disableOriginalConstructor()
        ->getMock();
        
        $this->assertNull($pdo->__construct('sqlite:' . self::TEST_SQLITE_DB, '', '', array(), true));  
    } 
     
} // ezSQL_pdoTest