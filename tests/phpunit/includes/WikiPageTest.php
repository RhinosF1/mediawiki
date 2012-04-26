<?php
/**
* @group Database
* ^--- important, causes temporary tables to be used instead of the real database
**/

class WikiPageTest extends MediaWikiTestCase {

	var $pages_to_delete;

	public function setUp() {
		$this->pages_to_delete = array();
	}

	public function tearDown() {
		foreach ( $this->pages_to_delete as $p ) {
			/* @var $p WikiPage */

			try {
				if ( $p->exists() ) {
					$p->doDeleteArticle( "testing done." );
				}
			} catch ( MWException $ex ) {
				// fail silently
			}
		}
	}

	protected function newPage( $title ) {
		if ( is_string( $title ) ) $title = Title::newFromText( $title );

		$p = new WikiPage( $title );

		$this->pages_to_delete[] = $p;

		return $p;
	}

	protected function createPage( $page, $text, $model = null ) {
		if ( is_string( $page ) ) $page = Title::newFromText( $page );
		if ( $page instanceof Title ) $page = $this->newPage( $page );

		$content = ContentHandler::makeContent( $text, $page->getTitle(), $model );
		$page->doEditContent( $content, "testing" );

		return $page;
	}

	public function testDoEditContent() {
		$title = Title::newFromText( "WikiPageTest_testDoEditContent" );

		$page = $this->newPage( $title );

		$content = ContentHandler::makeContent( "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam "
		                                        . " nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat.",
		                                        $title );

		$page->doEditContent( $content, "testing 1" );

		$this->assertTrue( $title->exists(), "Title object should indicate that the page now exists" );
		$this->assertTrue( $page->exists(), "WikiPage object should indicate that the page now exists" );

		# ------------------------
		$page = new WikiPage( $title );

		$retrieved = $page->getContent();
		$this->assertTrue( $content->equals( $retrieved ), 'retrieved content doesn\'t equal original' );

		# ------------------------
		$content = ContentHandler::makeContent( "At vero eos et accusam et justo duo dolores et ea rebum. "
		                                        . "Stet clita kasd gubergren, no sea takimata sanctus est.",
		                                        $title );

		$page->doEditContent( $content, "testing 2" );

		# ------------------------
		$page = new WikiPage( $title );

		$retrieved = $page->getContent();
		$this->assertTrue( $content->equals( $retrieved ), 'retrieved content doesn\'t equal original' );
	}

	public function testDoEdit() {
		$title = Title::newFromText( "WikiPageTest_testDoEdit" );

		$page = $this->newPage( $title );

		$text = "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam "
		       . " nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat.";

		$page->doEdit( $text, "testing 1" );

		$this->assertTrue( $title->exists(), "Title object should indicate that the page now exists" );
		$this->assertTrue( $page->exists(), "WikiPage object should indicate that the page now exists" );

		# ------------------------
		$page = new WikiPage( $title );

		$retrieved = $page->getText();
		$this->assertEquals( $text, $retrieved, 'retrieved text doesn\'t equal original' );

		# ------------------------
		$text = "At vero eos et accusam et justo duo dolores et ea rebum. "
		       . "Stet clita kasd gubergren, no sea takimata sanctus est.";

		$page->doEdit( $text, "testing 2" );

		# ------------------------
		$page = new WikiPage( $title );

		$retrieved = $page->getText();
		$this->assertEquals( $text, $retrieved, 'retrieved text doesn\'t equal original' );
	}

	public function testDoQuickEdit() {
		global $wgUser;

		$page = $this->createPage( "WikiPageTest_testDoQuickEdit", "original text" );

		$text = "quick text";
		$page->doQuickEdit( $text, $wgUser, "testing q" );

		# ---------------------
		$page = new WikiPage( $page->getTitle() );
		$this->assertEquals( $text, $page->getText() );
	}

	public function testDoQuickEditContent() {
		global $wgUser;

		$page = $this->createPage( "WikiPageTest_testDoQuickEditContent", "original text" );

		$content = ContentHandler::makeContent( "quick text", $page->getTitle() );
		$page->doQuickEditContent( $content, $wgUser, "testing q" );

		# ---------------------
		$page = new WikiPage( $page->getTitle() );
		$this->assertTrue( $content->equals( $page->getContent() ) );
	}

	public function testDoDeleteArticle() {
		$page = $this->createPage( "WikiPageTest_testDoDeleteArticle", "original text" );

		$page->doDeleteArticle( "testing deletion" );

		$this->assertFalse( $page->exists() );

		$this->assertNull( $page->getContent() );
		$this->assertFalse( $page->getText() );

		$t = Title::newFromText( $page->getTitle()->getPrefixedText() );
		$this->assertFalse( $t->exists() );
	}

	public function testGetRevision() {
		$page = $this->newPage( "WikiPageTest_testGetRevision" );

		$rev = $page->getRevision();
		$this->assertNull( $rev );

		# -----------------
		$this->createPage( $page, "some text" );

		$rev = $page->getRevision();

		$this->assertEquals( $page->getLatest(), $rev->getId() );
		$this->assertEquals( "some text", $rev->getContent()->getNativeData() );
	}

	public function testGetContent() {
		$page = $this->newPage( "WikiPageTest_testGetContent" );

		$content = $page->getContent();
		$this->assertNull( $content );

		# -----------------
		$this->createPage( $page, "some text" );

		$content = $page->getContent();
		$this->assertEquals( "some text", $content->getNativeData() );
	}

	public function testGetText() {
		$page = $this->newPage( "WikiPageTest_testGetText" );

		$text = $page->getText();
		$this->assertFalse( $text );

		# -----------------
		$this->createPage( $page, "some text" );

		$text = $page->getText();
		$this->assertEquals( "some text", $text );
	}

	public function testGetRawText() {
		$page = $this->newPage( "WikiPageTest_testGetRawText" );

		$text = $page->getRawText();
		$this->assertFalse( $text );

		# -----------------
		$this->createPage( $page, "some text" );

		$text = $page->getRawText();
		$this->assertEquals( "some text", $text );
	}

	public function testGetContentModelName() {
		$page = $this->createPage( "WikiPageTest_testGetContentModelName", "some text", CONTENT_MODEL_JAVASCRIPT );

		$page = new WikiPage( $page->getTitle() );
		$this->assertEquals( CONTENT_MODEL_JAVASCRIPT, $page->getContentModelName() );
	}

	public function testGetContentHandler() {
		$page = $this->createPage( "WikiPageTest_testGetContentHandler", "some text", CONTENT_MODEL_JAVASCRIPT );

		$page = new WikiPage( $page->getTitle() );
		$this->assertEquals( 'JavaScriptContentHandler', get_class( $page->getContentHandler() ) );
	}

	public function testExists() {
		$page = $this->newPage( "WikiPageTest_testExists" );
		$this->assertFalse( $page->exists() );

		# -----------------
		$this->createPage( $page, "some text" );
		$this->assertTrue( $page->exists() );

		$page = new WikiPage( $page->getTitle() );
		$this->assertTrue( $page->exists() );

		# -----------------
		$page->doDeleteArticle( "done testing" );
		$this->assertFalse( $page->exists() );

		$page = new WikiPage( $page->getTitle() );
		$this->assertFalse( $page->exists() );
	}

	public function dataHasViewableContent() {
		return array(
			array( 'WikiPageTest_testHasViewableContent', false, true ),
			array( 'Special:WikiPageTest_testHasViewableContent', false ),
			array( 'MediaWiki:WikiPageTest_testHasViewableContent', false ),
			array( 'Special:Userlogin', true ),
			array( 'MediaWiki:help', true ),
		);
	}

	/**
	 * @dataProvider dataHasViewableContent
	 */
	public function testHasViewableContent( $title, $viewable, $create = false ) {
		$page = $this->newPage( $title );
		$this->assertEquals( $viewable, $page->hasViewableContent() );

		if ( $create ) {
			$this->createPage( $page, "some text" );
			$this->assertTrue( $page->hasViewableContent() );

			$page = new WikiPage( $page->getTitle() );
			$this->assertTrue( $page->hasViewableContent() );
		}
	}

	public function dataGetRedirectTarget() {
		return array(
			array( 'WikiPageTest_testGetRedirectTarget_1', "hello world", null ),
			array( 'WikiPageTest_testGetRedirectTarget_2', "#REDIRECT [[hello world]]", "Hello world" ),
		);
	}

	/**
	 * @dataProvider dataGetRedirectTarget
	 */
	public function testGetRedirectTarget( $title, $text, $target ) {
		$page = $this->createPage( $title, $text );

		$t = $page->getRedirectTarget();
		$this->assertEquals( $target, is_null( $t ) ? null : $t->getPrefixedText() );
	}

	/**
	 * @dataProvider dataGetRedirectTarget
	 */
	public function testIsRedirect( $title, $text, $target ) {
		$page = $this->createPage( $title, $text );
		$this->assertEquals( !is_null( $target ), $page->isRedirect() );
	}

	public function dataIsCountable() {
		return array(

			// any
			array( 'WikiPageTest_testIsCountable',
			       '',
			       'any',
			       true
			),
			array( 'WikiPageTest_testIsCountable',
			       'Foo',
			       'any',
			       true
			),

			// comma
			array( 'WikiPageTest_testIsCountable',
			       'Foo',
			       'comma',
			       false
			),
			array( 'WikiPageTest_testIsCountable',
			       'Foo, bar',
			       'comma',
			       true
			),

			// link
			array( 'WikiPageTest_testIsCountable',
			       'Foo',
			       'link',
			       false
			),
			array( 'WikiPageTest_testIsCountable',
			       'Foo [[bar]]',
			       'link',
			       true
			),

			// redirects
			array( 'WikiPageTest_testIsCountable',
			       '#REDIRECT [[bar]]',
			       'any',
			       false
			),
			array( 'WikiPageTest_testIsCountable',
			       '#REDIRECT [[bar]]',
			       'comma',
			       false
			),
			array( 'WikiPageTest_testIsCountable',
			       '#REDIRECT [[bar]]',
			       'link',
			       false
			),

			// not a content namespace
			array( 'Talk:WikiPageTest_testIsCountable',
			       'Foo',
			       'any',
			       false
			),
			array( 'Talk:WikiPageTest_testIsCountable',
			       'Foo, bar',
			       'comma',
			       false
			),
			array( 'Talk:WikiPageTest_testIsCountable',
			       'Foo [[bar]]',
			       'link',
			       false
			),

			// not a content namespace, different model
			array( 'MediaWiki:WikiPageTest_testIsCountable.js',
			       'Foo',
			       'any',
			       false
			),
			array( 'MediaWiki:WikiPageTest_testIsCountable.js',
			       'Foo, bar',
			       'comma',
			       false
			),
			array( 'MediaWiki:WikiPageTest_testIsCountable.js',
			       'Foo [[bar]]',
			       'link',
			       false
			),
		);
	}


	/**
	 * @dataProvider dataIsCountable
	 */
	public function testIsCountable( $title, $text, $mode, $expected ) {
		global $wgArticleCountMethod;

		$old = $wgArticleCountMethod;
		$wgArticleCountMethod = $mode;

		$page = $this->createPage( $title, $text );
		$editInfo = $page->prepareContentForEdit( $page->getContent() );

		$v = $page->isCountable();
		$w = $page->isCountable( $editInfo );
		$wgArticleCountMethod = $old;

		$this->assertEquals( $expected, $v, "isCountable( null ) returned unexpected value " . var_export( $v, true )
		                                    . " instead of " . var_export( $expected, true ) . " in mode `$mode` for text \"$text\"" );

		$this->assertEquals( $expected, $w, "isCountable( \$editInfo ) returned unexpected value " . var_export( $v, true )
		                                    . " instead of " . var_export( $expected, true ) . " in mode `$mode` for text \"$text\"" );
	}

	public function dataGetParserOutput() {
		return array(
			array("hello ''world''\n", "<p>hello <i>world</i>\n</p>"),
			// @todo: more...?
		);
	}

	/**
	 * @dataProvider dataGetParserOutput
	 */
	public function testGetParserOutput( $text, $expectedHtml ) {
		$page = $this->createPage( 'WikiPageTest_testGetParserOutput', $text );

		$opt = new ParserOptions();
		$po = $page->getParserOutput( $opt );
		$text = $po->getText();

		$text = trim( preg_replace( '/<!--.*?-->/sm', '', $text ) ); # strip injected comments

		$this->assertEquals( $expectedHtml, $text );
		return $po;
	}

	static $sections =

		"Intro

== stuff ==
hello world

== test ==
just a test

== foo ==
more stuff
";


	public function dataReplaceSection() {
		return array(
			array( 'WikiPageTest_testReplaceSection',
			       WikiPageTest::$sections,
			       "0",
			       "No more",
			       null,
			       trim( preg_replace( '/^Intro/sm', 'No more', WikiPageTest::$sections ) )
			),
			array( 'WikiPageTest_testReplaceSection',
			       WikiPageTest::$sections,
			       "",
			       "No more",
			       null,
			       "No more"
			),
			array( 'WikiPageTest_testReplaceSection',
			       WikiPageTest::$sections,
			       "2",
			       "== TEST ==\nmore fun",
			       null,
			       trim( preg_replace( '/^== test ==.*== foo ==/sm', "== TEST ==\nmore fun\n\n== foo ==", WikiPageTest::$sections ) )
			),
			array( 'WikiPageTest_testReplaceSection',
			       WikiPageTest::$sections,
			       "8",
			       "No more",
			       null,
			       trim( WikiPageTest::$sections )
			),
			array( 'WikiPageTest_testReplaceSection',
			       WikiPageTest::$sections,
			       "new",
			       "No more",
			       "New",
			       trim( WikiPageTest::$sections ) . "\n\n== New ==\n\nNo more"
			),
		);
	}

	/**
	 * @dataProvider dataReplaceSection
	 */
	public function testReplaceSection( $title, $text, $section, $with, $sectionTitle, $expected ) {
		$page = $this->createPage( $title, $text );
		$text = $page->replaceSection( $section, $with, $sectionTitle );
		$text = trim( $text );

		$this->assertEquals( $expected, $text );
	}

	/**
	 * @dataProvider dataReplaceSection
	 */
	public function testReplaceSectionContent( $title, $text, $section, $with, $sectionTitle, $expected ) {
		$page = $this->createPage( $title, $text );

		$content = ContentHandler::makeContent( $with, $page->getTitle(), $page->getContentModelName() );
		$c = $page->replaceSectionContent( $section, $content, $sectionTitle );

		$this->assertEquals( $expected, is_null( $c ) ? null : trim( $c->getNativeData() ) );
	}

	/* @FIXME: fix this!
	public function testGetUndoText() {
		global $wgDiff3;

		wfSuppressWarnings();
		$haveDiff3 = $wgDiff3 && file_exists( $wgDiff3 );
		wfRestoreWarnings();

		if( !$haveDiff3 ) {
			$this->markTestSkipped( "diff3 not installed or not found" );
			return;
		}

		$text = "one";
		$page = $this->createPage( "WikiPageTest_testGetUndoText", $text );
		$rev1 = $page->getRevision();

		$text .= "\n\ntwo";
		$page->doEditContent( ContentHandler::makeContent( $text, $page->getTitle() ), "adding section two");
		$rev2 = $page->getRevision();

		$text .= "\n\nthree";
		$page->doEditContent( ContentHandler::makeContent( $text, $page->getTitle() ), "adding section three");
		$rev3 = $page->getRevision();

		$text .= "\n\nfour";
		$page->doEditContent( ContentHandler::makeContent( $text, $page->getTitle() ), "adding section four");
		$rev4 = $page->getRevision();

		$text .= "\n\nfive";
		$page->doEditContent( ContentHandler::makeContent( $text, $page->getTitle() ), "adding section five");
		$rev5 = $page->getRevision();

		$text .= "\n\nsix";
		$page->doEditContent( ContentHandler::makeContent( $text, $page->getTitle() ), "adding section six");
		$rev6 = $page->getRevision();

		$undo6 = $page->getUndoText( $rev6 );
		if ( $undo6 === false ) $this->fail( "getUndoText failed for rev6" );
		$this->assertEquals( "one\n\ntwo\n\nthree\n\nfour\n\nfive", $undo6 );

		$undo3 = $page->getUndoText( $rev4, $rev2 );
		if ( $undo3 === false ) $this->fail( "getUndoText failed for rev4..rev2" );
		$this->assertEquals( "one\n\ntwo\n\nfive", $undo3 );

		$undo2 = $page->getUndoText( $rev2 );
		if ( $undo2 === false ) $this->fail( "getUndoText failed for rev2" );
		$this->assertEquals( "one\n\nfive", $undo2 );
	}
	*/

	public function testDoRollback() {
		$admin = new User();
		$admin->setName("Admin");

		$text = "one";
		$page = $this->createPage( "WikiPageTest_testDoRollback", $text );

		$user1 = new User();
		$user1->setName( "127.0.1.11" );
		$text .= "\n\ntwo";
		$page->doEditContent( ContentHandler::makeContent( $text, $page->getTitle() ), "adding section two", 0, false, $user1 );

		$user2 = new User();
		$user2->setName( "127.0.2.13" );
		$text .= "\n\nthree";
		$page->doEditContent( ContentHandler::makeContent( $text, $page->getTitle() ), "adding section three", 0, false, $user2 );

		$admin->addGroup( "sysop" ); #XXX: make the test user a sysop...
		$token = $admin->getEditToken( array( $page->getTitle()->getPrefixedText(), $user2->getName() ), null );
		$errors = $page->doRollback( $user2->getName(), "testing revert", $token, false, $details, $admin );

		if ( $errors ) {
			$this->fail( "Rollback failed:\n" . print_r( $errors, true ) . ";\n" . print_r( $details, true ) );
		}

		$page = new WikiPage( $page->getTitle() );
		$this->assertEquals( "one\n\ntwo", $page->getContent()->getNativeData() );
	}

	public function dataGetAutosummary( ) {
		return array(
			array(
				'Hello there, world!',
				'#REDIRECT [[Foo]]',
				0,
				'/^Redirected page .*Foo/'
			),

			array(
				null,
				'Hello world!',
				EDIT_NEW,
				'/^Created page .*Hello/'
			),

			array(
				'Hello there, world!',
				'',
				0,
				'/^Blanked/'
			),

			array(
				'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut
				labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et
				ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.',
				'Hello world!',
				0,
				'/^Replaced .*Hello/'
			),

			array(
				'foo',
				'bar',
				0,
				'/^$/'
			),
		);
	}

	/**
	 * @dataProvider dataGetAutoSummary
	 */
	public function testGetAutosummary( $old, $new, $flags, $expected ) {
		$page = $this->newPage( "WikiPageTest_testGetAutosummary" );

		$summary = $page->getAutosummary( $old, $new, $flags );

		$this->assertTrue( (bool)preg_match( $expected, $summary ), "Autosummary didn't match expected pattern $expected: $summary" );
	}

	public function dataGetAutoDeleteReason( ) {
		return array(
			array(
				array(),
				false,
				false
			),

			array(
				array(
					array( "first edit", null ),
				),
				"/first edit.*only contributor/",
				false
			),

			array(
				array(
					array( "first edit", null ),
					array( "second edit", null ),
				),
				"/second edit.*only contributor/",
				true
			),

			array(
				array(
					array( "first edit", "127.0.2.22" ),
					array( "second edit", "127.0.3.33" ),
				),
				"/second edit/",
				true
			),

			array(
				array(
					array( "first edit: "
					     . "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam "
					     . " nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. "
					     . "At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea "
					     . "takimata sanctus est Lorem ipsum dolor sit amet.'", null ),
				),
				'/first edit:.*\.\.\."/',
				false
			),

			array(
				array(
					array( "first edit", "127.0.2.22" ),
					array( "", "127.0.3.33" ),
				),
				"/before blanking.*first edit/",
				true
			),

		);
	}

	/**
	 * @dataProvider dataGetAutoDeleteReason
	 */
	public function testGetAutoDeleteReason( $edits, $expectedResult, $expectedHistory ) {
		global $wgUser;

		$page = $this->newPage( "WikiPageTest_testGetAutoDeleteReason" );

		$c = 1;

		foreach ( $edits as $edit ) {
			$user = new User();

			if ( !empty( $edit[1] ) ) $user->setName( $edit[1] );
			else $user = $wgUser;

			$content = ContentHandler::makeContent( $edit[0], $page->getTitle(), $page->getContentModelName() );

			$page->doEditContent( $content, "test edit $c", $c < 2 ? EDIT_NEW : 0, false, $user );

			$c += 1;
		}

		$reason = $page->getAutoDeleteReason( $hasHistory );

		if ( is_bool( $expectedResult ) || is_null( $expectedResult ) ) $this->assertEquals( $expectedResult, $reason );
		else $this->assertTrue( (bool)preg_match( $expectedResult, $reason ), "Autosummary didn't match expected pattern $expectedResult: $reason" );

		$this->assertEquals( $expectedHistory, $hasHistory, "expected \$hasHistory to be " . var_export( $expectedHistory, true ) );
	}

	public function dataPreSaveTransform() {
		return array(
			array( 'hello this is ~~~',
			       "hello this is [[Special:Contributions/127.0.0.1|127.0.0.1]]",
			),
			array( 'hello \'\'this\'\' is <nowiki>~~~</nowiki>',
			       'hello \'\'this\'\' is <nowiki>~~~</nowiki>',
			),
		);
	}

	/**
	 * @dataProvider dataPreSaveTransform
	 */
	public function testPreSaveTransform( $text, $expected ) {
		$user = new User();
		$user->setName("127.0.0.1");

		$page = $this->newPage( "WikiPageTest_testPreloadTransform" );
		$text = $page->preSaveTransform( $text, $user );

		$this->assertEquals( $expected, $text );
	}

}

