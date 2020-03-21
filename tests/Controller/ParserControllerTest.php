<?php

namespace DedexBundle\Tests\Controller;

use DedexBundle\Controller\ParserController;
use DedexBundle\Entity\IndirectResourceContributor;
use DedexBundle\Entity\ResourceContributor;
use DedexBundle\Entity\RightsController;
use DedexBundle\Entity\SoundRecording;
use DedexBundle\Entity\SoundRecordingDetailsByTerritory;
use PHPUnit\Framework\TestCase;

class ParserControllerTest extends TestCase {

	public function testCleanTag() {
		$parser_controller = new ParserController();
		$this->assertEquals("MYTAG", $parser_controller->cleanTag("MYTAG"));
		$this->assertEquals("MY_TAG", $parser_controller->cleanTag("MY:TAG"));
	}
	
	public function testSample001() {
		$xml_path = "tests/samples/001_audioalbum_complete.xml";
		$parser_controller = new ParserController();
		$ddex = $parser_controller->parse($xml_path);
		
		// ERN attributes
		$this->assertEquals("http://ddex.net/xml/ern/382", $ddex->getXmlns_Ern());
		$this->assertEquals("http://www.w3.org/2001/XMLSchema-instance", $ddex->getXmlns_Xs());
		$this->assertEquals("http://ddex.net/xml/ern/382 ", $ddex->getXs_SchemaLocation());
		$this->assertEquals("ern/382", $ddex->getMessageSchemaVersionId());
		$this->assertEquals("CommonReleaseTypesTypes/14/AudioAlbum", $ddex->getReleaseProfileVersionId());
		$this->assertEquals("en", $ddex->getLanguageAndScriptCode());

		// Message header
		$this->assertEquals("20170503144647-10", $ddex->getMessageHeader()->getMessageThreadId());
		$this->assertEquals("20170503144647-10", $ddex->getMessageHeader()->getMessageId());
		$this->assertEquals("PADPIDA2007061301U", $ddex->getMessageHeader()->getMessageSender()->getPartyId());
		$this->assertEquals("SCPP", $ddex->getMessageHeader()->getMessageSender()->getPartyName()->getFullName());
		$this->assertEquals("PADPIDA2007061302U", $ddex->getMessageHeader()->getMessageRecipient()->getPartyId());
		$this->assertEquals("SCPP2", $ddex->getMessageHeader()->getMessageRecipient()->getPartyName()->getFullName());

		// Is backfill
		$this->assertTrue($ddex->getIsBackfill());

		// Update indicator
		$this->assertEquals("OriginalMessage", $ddex->getUpdateIndicator());
		
		// Resources
		$this->assertCount(8, $ddex->getResourceList()->getResources());
		/* @var $resource_zero SoundRecording */
		$resource_zero = $ddex->getResourceList()->getResources()[0];
		$this->assertEquals("true", $resource_zero->getIsUpdated());
		$this->assertEquals("en", $resource_zero->getLanguageAndScriptCode());
		$this->assertEquals("PADPIDA2007061301U", $resource_zero->getSoundRecordingType()->getNamespace());
		$this->assertEquals("PADPIDA2007061301U", $resource_zero->getSoundRecordingType()->getNamespace());
		$this->assertEquals("MusicalWorkSoundRecording", $resource_zero->getSoundRecordingType()->getData());
		$this->assertEquals("true", $resource_zero->getIsArtistRelated());
		$this->assertEquals("false", $resource_zero->getSoundRecordingId()->getIsReplaced());
		$this->assertEquals("CASE00000001", $resource_zero->getSoundRecordingId()->getIsrc());
		$this->assertEquals("PADPIDA2007061301U", $resource_zero->getSoundRecordingId()->getCatalogNumber()->getNamespace());
		$this->assertEquals("123456", $resource_zero->getSoundRecordingId()->getCatalogNumber()->getData());
		$this->assertEquals("PADPIDA2007061301U", $resource_zero->getSoundRecordingId()->getProprietaryId()->getNamespace());
		$this->assertEquals("00010075760152", $resource_zero->getSoundRecordingId()->getProprietaryId()->getData());
		$this->assertEquals("A1", $resource_zero->getResourceReference());
		$this->assertEquals("Can you feel ...the Monkey Claw!", $resource_zero->getReferenceTitle()->getTitleText());
		$this->assertEquals("false", $resource_zero->getIsMedley());
		$this->assertEquals("false", $resource_zero->getIsPotpourri());
		$this->assertEquals("false", $resource_zero->getIsInstrumental());
		$this->assertEquals("false", $resource_zero->getIsHiddenResource());
		$this->assertEquals("false", $resource_zero->getIsBonusResource());
		$this->assertEquals("false", $resource_zero->getIsComputerGenerated());
		$this->assertEquals("true", $resource_zero->getNoSilenceBefore());
		$this->assertEquals("true", $resource_zero->getNoSilenceAfter());
		$this->assertEquals("PT13M31S", $resource_zero->getDuration());
		$this->assertEquals("false", $resource_zero->getCreationDate()->getIsApproximate());
		$this->assertEquals("false", $resource_zero->getCreationDate()->getIsBefore());
		$this->assertEquals("false", $resource_zero->getCreationDate()->getIsAfter());
		$this->assertEquals("FR", $resource_zero->getCreationDate()->getTerritoryCode());
		$this->assertEquals("2008-09-26", $resource_zero->getCreationDate()->getData());
		$this->assertEquals("false", $resource_zero->getMasteredDate()->getIsApproximate());
		$this->assertEquals("false", $resource_zero->getMasteredDate()->getIsBefore());
		$this->assertEquals("false", $resource_zero->getMasteredDate()->getIsAfter());
		$this->assertEquals("2008-09-27", $resource_zero->getMasteredDate()->getData());
		/* @var $srdbt_zero SoundRecordingDetailsByTerritory */
		$srdbt_zero = $resource_zero->getSoundRecordingDetailsByTerritorys()[0];
		$this->assertEquals("Worldwide", $srdbt_zero->getTerritoryCode());
		$this->assertCount(2, $srdbt_zero->getTitles());
		$this->assertEquals("FormalTitle", $srdbt_zero->getTitles()[0]->getTitleType());
		$this->assertEquals("Can you feel ...the Monkey Claw! formal", $srdbt_zero->getTitles()[0]->getTitleText());
		$this->assertEquals("DisplayTitle", $srdbt_zero->getTitles()[1]->getTitleType());
		$this->assertEquals("Can you feel ...the Monkey Claw!", $srdbt_zero->getTitles()[1]->getTitleText());
		
		$this->assertCount(2, $srdbt_zero->getDisplayArtists());
		$this->assertEquals("1", $srdbt_zero->getDisplayArtists()[0]->getSequenceNumber());
		$this->assertEquals("Monkey Claw", $srdbt_zero->getDisplayArtists()[0]->getPartyName()->getFullName());
		$this->assertEquals("PADPIDA2007061301U", $srdbt_zero->getDisplayArtists()[0]->getPartyId()->getProprietaryId()->getNamespace());
		$this->assertEquals("6687769", $srdbt_zero->getDisplayArtists()[0]->getPartyId()->getProprietaryId()->getData());
		$this->assertEquals("MainArtist", $srdbt_zero->getDisplayArtists()[0]->getArtistRole());
		$this->assertEquals("2", $srdbt_zero->getDisplayArtists()[1]->getSequenceNumber());
		$this->assertEquals("Second Artist", $srdbt_zero->getDisplayArtists()[1]->getPartyName()->getFullName());
		$this->assertEquals("PADPIDA2007061301U", $srdbt_zero->getDisplayArtists()[1]->getPartyId()->getProprietaryId()->getNamespace());
		$this->assertEquals("6687759", $srdbt_zero->getDisplayArtists()[1]->getPartyId()->getProprietaryId()->getData());
		$this->assertEquals("MainArtist", $srdbt_zero->getDisplayArtists()[1]->getArtistRole());
		
		// Display composers
		$this->assertCount(2, $srdbt_zero->getDisplayComposers());
		$this->assertEquals("0", $srdbt_zero->getDisplayComposers()[0]->getSequenceNumber());
		$this->assertEquals("BONDU PIERRE", $srdbt_zero->getDisplayComposers()[0]->getPartyName()->getFullName());
		$this->assertEquals("PADPIDA2007061301U", $srdbt_zero->getDisplayComposers()[0]->getPartyId()->getProprietaryId()->getNamespace());
		$this->assertEquals("71166", $srdbt_zero->getDisplayComposers()[0]->getPartyId()->getProprietaryId()->getData());
		$this->assertEquals("1", $srdbt_zero->getDisplayComposers()[1]->getSequenceNumber());
		$this->assertEquals("Frederic Chopin", $srdbt_zero->getDisplayComposers()[1]->getPartyName()->getFullName());
		$this->assertEquals("PADPIDA2007061301U", $srdbt_zero->getDisplayComposers()[1]->getPartyId()->getProprietaryId()->getNamespace());
		$this->assertEquals("71167", $srdbt_zero->getDisplayComposers()[1]->getPartyId()->getProprietaryId()->getData());

		// Initial producer
		$this->assertEquals("UNIVERSAL MUSIC FRANCE", $srdbt_zero->getInitialProducer()->getPartyName()->getFullName());
		$this->assertEquals("PADPIDA2007061301U", $srdbt_zero->getInitialProducer()->getPartyId()->getProprietaryId()->getNamespace());
		$this->assertEquals("1", $srdbt_zero->getInitialProducer()->getPartyId()->getProprietaryId()->getData());
		$this->assertEquals("FR", $srdbt_zero->getInitialProducer()->getTerritoryCode());
		
		// Rights controllers
		$this->assertCount(2, $srdbt_zero->getRightsControllers());
		/* @var $rctrl_one RightsController */
		$rctrl_one = $srdbt_zero->getRightsControllers()[0];
		$this->assertEquals("LeftRight", $rctrl_one->getPartyName()->getFullName());
		$this->assertEquals("10", $rctrl_one->getRightSharePercentage());
		$this->assertEquals("OriginalCopyrightOwner", $rctrl_one->getRightsControllerRole());
		$this->assertCount(3, $rctrl_one->getDelegatedUsageRights()->getUseTypes());
		$this->assertEquals("Broadcast", $rctrl_one->getDelegatedUsageRights()->getUseTypes()[0]);
		$this->assertEquals("Simulcast", $rctrl_one->getDelegatedUsageRights()->getUseTypes()[1]);
		$this->assertEquals("PerformInPublic", $rctrl_one->getDelegatedUsageRights()->getUseTypes()[2]);
		$this->assertEquals("2008-09-26", $rctrl_one->getDelegatedUsageRights()->getPeriodOfRightsDelegation()->getStartDate());
		$this->assertEquals("FR", $rctrl_one->getDelegatedUsageRights()->getTerritoryOfRightsDelegation());
		/* @var $rctrl_two RightsController */
		$rctrl_two = $srdbt_zero->getRightsControllers()[1];
		$this->assertEquals("LeftRight", $rctrl_two->getPartyName()->getFullName());
		$this->assertEquals("50", $rctrl_two->getRightSharePercentage());
		$this->assertEquals("JP", $rctrl_two->getDelegatedUsageRights()->getTerritoryOfRightsDelegation());
		
		// Resource Contributors
		$this->assertCount(2, $srdbt_zero->getResourceContributors());
		/* @var $rescont_one ResourceContributor */
		$rescont_one = $srdbt_zero->getResourceContributors()[0];
		$this->assertEquals("1", $rescont_one->getSequenceNumber());
		$this->assertEquals("Chris Martin", $rescont_one->getPartyName()->getFullName());
		$this->assertEquals("true", $rescont_one->getPartyId()->getIsISNI());
		$this->assertEquals("0000000114707136", $rescont_one->getPartyId()->getData());
		$this->assertEquals("Member", $rescont_one->getResourceContributorRole());
		/* @var $rescont_two ResourceContributor */
		$rescont_two = $srdbt_zero->getResourceContributors()[1];
		$this->assertEquals("2", $rescont_two->getSequenceNumber());
		$this->assertEquals("Markus Dravs", $rescont_two->getPartyName()->getFullName());
		$this->assertEquals("Producer", $rescont_two->getResourceContributorRole());
		
		// Indirect Resource Contributors
		$this->assertCount(2, $srdbt_zero->getIndirectResourceContributors());
		/* @var $resindcont_one IndirectResourceContributor */
		$resindcont_one = $srdbt_zero->getIndirectResourceContributors()[0];
		$this->assertEquals("1", $resindcont_one->getSequenceNumber());
		$this->assertEquals("Chris Martin", $resindcont_one->getPartyName()->getFullName());
		$this->assertEquals("true", $resindcont_one->getPartyId()->getIsISNI());
		$this->assertEquals("0000000114707136", $resindcont_one->getPartyId()->getData());
		$this->assertEquals("ComposerLyricist", $resindcont_one->getIndirectResourceContributorRole());
		/* @var $resindcont_two IndirectResourceContributor */
		$resindcont_two = $srdbt_zero->getIndirectResourceContributors()[1];
		$this->assertEquals("2", $resindcont_two->getSequenceNumber());
		$this->assertEquals("Chris Martin 2", $resindcont_two->getPartyName()->getFullName());
		$this->assertEquals("true", $resindcont_two->getPartyId()->getIsISNI());
		$this->assertEquals("0000000114707136", $resindcont_two->getPartyId()->getData());
		$this->assertEquals("ComposerLyricist", $resindcont_two->getIndirectResourceContributorRole());
		
	}

}