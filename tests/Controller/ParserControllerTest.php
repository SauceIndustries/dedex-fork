<?php

namespace DedexBundle\Tests\Controller;

use DedexBundle\Controller\ErnParserController;
use DedexBundle\Entity\Ern382\GenreType;
use DedexBundle\Entity\Ern382\ImageDetailsByTerritoryType;
use DedexBundle\Entity\Ern382\ImageType;
use DedexBundle\Entity\Ern382\IndirectResourceContributor;
use DedexBundle\Entity\Ern382\NewReleaseMessage;
use DedexBundle\Entity\Ern382\ReleaseDetailsByTerritoryType;
use DedexBundle\Entity\Ern382\ReleaseType;
use DedexBundle\Entity\Ern382\ResourceContributor;
use DedexBundle\Entity\Ern382\SoundRecordingDetailsByTerritoryType;
use DedexBundle\Entity\Ern382\TechnicalSoundRecordingDetailsType;
use DedexBundle\Entity\Ern32\NewReleaseMessage as Ern32NewReleaseMessage;
use DedexBundle\Entity\Ern32\SoundRecordingType as Ern32SoundRecordingType;
use DedexBundle\Entity\Ern32\ReleaseType as Ern32ReleaseType;
use DedexBundle\Exception\FileNotFoundException;
use DedexBundle\Exception\XmlLoadException;
use DedexBundle\Exception\XmlParseException;
use PHPUnit\Framework\TestCase;

class ParserControllerTest extends TestCase {

  public function testCleanTag() {
    $parser_controller = new ErnParserController();
    $this->assertEquals("MYTAG", $parser_controller->cleanTag("MYTAG"));
    $this->assertEquals("MY_TAG", $parser_controller->cleanTag("MY:TAG"));
  }

  public function testSample001() {
    $xml_path = "tests/samples/001_audioalbum_complete.xml";
    $parser_controller = new ErnParserController();
    // Set this to true to see logs from the parser
    $parser_controller->setDisplayLog(false);
    /* @var $ddex NewReleaseMessage */
    $ddex = $parser_controller->parse($xml_path);

    // ERN attributes
    $this->assertEquals("ern/382", $ddex->getMessageSchemaVersionId());
    $this->assertEquals("CommonReleaseTypesTypes/14/AudioAlbumMusicOnly", $ddex->getReleaseProfileVersionId());
    $this->assertEquals("en", $ddex->getLanguageAndScriptCode());

    // Message header
    $this->assertEquals(null, $ddex->getMessageHeader()->getMessageThreadId());
    $this->assertEquals(null, $ddex->getMessageHeader()->getMessageId());
    $this->assertEquals("DPID_OF_THE_SENDER", $ddex->getMessageHeader()->getMessageSender()->getPartyId()[0]->value());
    $this->assertEquals("DPID_OF_THE_RECIPIENT", $ddex->getMessageHeader()->getMessageRecipient()[0]->getPartyId()[0]->value());
    $this->assertEquals("2012-12-11T15:50:00+00:00", $ddex->getMessageHeader()->getMessageCreatedDateTime()->format("Y-m-d\TH:i:sP"));

    // Is backfill
    $this->assertEquals("true", $ddex->getIsBackfill());

    // Resources
    
    // SoundRecording
    $this->assertCount(6, $ddex->getResourceList()->getSoundRecording());
    /* @var $resource_zero \DedexBundle\Entity\Ern382\Ern\SoundRecordingType */
    $resource_zero = $ddex->getResourceList()->getSoundRecording()[0];
    $this->assertEquals("MusicalWorkSoundRecording", $resource_zero->getSoundRecordingType()->value());
    $this->assertEquals("CASE00000001", $resource_zero->getSoundRecordingId()[0]->getIsrc());
    $this->assertEquals("T1234567890", $resource_zero->getIndirectSoundRecordingId()[0]->getIswc());
    $this->assertEquals("A1", $resource_zero->getResourceReference());
    $this->assertEquals("Can you feel ...the Monkey Claw!", $resource_zero->getReferenceTitle()->getTitleText());
    $this->assertEquals("PT13M31S", $resource_zero->getDuration()->format("PT%iM%sS"));
    /* @var $srdbt_zero SoundRecordingDetailsByTerritoryType */
    $srdbt_zero = $resource_zero->getSoundRecordingDetailsByTerritory()[0];
    $this->assertEquals("Worldwide", $srdbt_zero->getTerritoryCode()[0]);
    $this->assertCount(2, $srdbt_zero->getTitle());
    $this->assertEquals("FormalTitle", $srdbt_zero->getTitle()[0]->getTitleType());
    $this->assertEquals("Can you feel ...the Monkey Claw! formal", $srdbt_zero->getTitle()[0]->getTitleText()->value());
    $this->assertEquals("DisplayTitle", $srdbt_zero->getTitle()[1]->getTitleType());
    $this->assertEquals("Can you feel ...the Monkey Claw!", $srdbt_zero->getTitle()[1]->getTitleText());

    $this->assertCount(2, $srdbt_zero->getDisplayArtist());
    $this->assertEquals("1", $srdbt_zero->getDisplayArtist()[0]->getSequenceNumber());
    $this->assertEquals("Monkey Claw", $srdbt_zero->getDisplayArtist()[0]->getPartyName()[0]->getFullName());
    $this->assertEquals("MainArtist", $srdbt_zero->getDisplayArtist()[0]->getArtistRole()[0]);
    $this->assertEquals("2", $srdbt_zero->getDisplayArtist()[1]->getSequenceNumber());
    $this->assertEquals("Second Artist", $srdbt_zero->getDisplayArtist()[1]->getPartyName()[0]->getFullName());
    $this->assertEquals("MainArtist", $srdbt_zero->getDisplayArtist()[1]->getArtistRole()[0]);

    // Resource Contributors
    $this->assertCount(1, $srdbt_zero->getResourceContributor());
    /* @var $rescont_one ResourceContributor */
    $rescont_one = $srdbt_zero->getResourceContributor()[0];
    $this->assertEquals("1", $rescont_one->getSequenceNumber());
    $this->assertEquals("Steve Albino", $rescont_one->getPartyName()[0]->getFullName());
    $this->assertEquals("Producer", $rescont_one->getResourceContributorRole()[0]);

    // Indirect Resource Contributors
    $this->assertCount(1, $srdbt_zero->getIndirectResourceContributor());
    /* @var $resindcont_one IndirectResourceContributor */
    $resindcont_one = $srdbt_zero->getIndirectResourceContributor()[0];
    $this->assertEquals("1", $resindcont_one->getSequenceNumber());
    $this->assertEquals("Bob Black", $resindcont_one->getPartyName()[0]->getFullName());
    $this->assertEquals("Composer", $resindcont_one->getIndirectResourceContributorRole()[0]);
    
    // ResourceReleaseDate
    $this->assertEquals("2011", $srdbt_zero->getResourceReleaseDate());

    // PLine
    $pline = $srdbt_zero->getPLine()[0];
    $this->assertEquals("2010", $pline->getYear());
    $this->assertEquals("(P) 2010 Iron Crown Music", $pline->getPLineText());
    
    // Genres
    $this->assertCount(1, $srdbt_zero->getGenre());
    /* @var $genre GenreType */
    $genre = $srdbt_zero->getGenre()[0];
    $this->assertEquals("Metal", $genre->getGenreText());
    $this->assertEquals("Progressive Metal", $genre->getSubGenre());
    
    // ParentalWarningType
    $this->assertCount(1, $srdbt_zero->getParentalWarningType());
    $this->assertEquals("NotExplicit", $srdbt_zero->getParentalWarningType()[0]);
    
    // TechnicalSoundRecordingDetails
    $this->assertCount(1, $srdbt_zero->getTechnicalSoundRecordingDetails());
    /* @var $technicalSRD TechnicalSoundRecordingDetailsType */
    $technicalSRD = $srdbt_zero->getTechnicalSoundRecordingDetails()[0];
    $this->assertEquals("T1", $technicalSRD->getTechnicalResourceDetailsReference());
    $this->assertEquals("A1UCASE0000000401X_01_01.wav", $technicalSRD->getFile()[0]->getFileName());
    
    // Image
    $this->assertCount(1, $ddex->getResourceList()->getImage());
    /* @var $image ImageType */
    $image = $ddex->getResourceList()->getImage()[0];
    $this->assertEquals("FrontCoverImage", $image->getImageType());
    $this->assertCount(1, $image->getImageId());
    $this->assertCount(1, $image->getImageId()[0]->getProprietaryId());
    $this->assertEquals("DPID:PADPIDA0000000001A", $image->getImageId()[0]->getProprietaryId()[0]->getNamespace());
    $this->assertEquals("A7", $image->getResourceReference());
    
    // ImageDetailsByTerritory
    $this->assertCount(1, $image->getImageDetailsByTerritory());
    /* @var $image_dbt ImageDetailsByTerritoryType */
    $image_dbt = $image->getImageDetailsByTerritory()[0];
    $this->assertCount(1, $image_dbt->getTerritoryCode());
    $this->assertEquals("Worldwide", $image_dbt->getTerritoryCode()[0]);
    $this->assertCount(1, $image_dbt->getParentalWarningType());
    $this->assertEquals("NotExplicit", $image_dbt->getParentalWarningType()[0]->value());
    $this->assertCount(1, $image_dbt->getTechnicalImageDetails());
    $this->assertEquals("T7", $image_dbt->getTechnicalImageDetails()[0]->getTechnicalResourceDetailsReference());
    $this->assertCount(1, $image_dbt->getTechnicalImageDetails()[0]->getFile());
    $this->assertEquals("A1UCASE0000000401X.jpeg", $image_dbt->getTechnicalImageDetails()[0]->getFile()[0]->getFileName());
    
    // Releases
    $this->assertCount(7, $ddex->getReleaseList()->getRelease());
    
    // Main release
    /* @var $main_release ReleaseType */
    $main_release = $ddex->getReleaseList()->getRelease()[0];
    $this->assertEquals("true", $main_release->getIsMainRelease());
    $this->assertCount(1, $main_release->getReleaseId());
    $this->assertEquals("A1UCASE0000000401X", $main_release->getReleaseId()[0]->getGRid());
    $this->assertCount(1, $main_release->getReleaseReference());
    $this->assertEquals("R0", $main_release->getReleaseReference()[0]);
    $this->assertEquals("A Monkey Claw in a Velvet Glove", $main_release->getReferenceTitle()->getTitleText());
    
    // Check first and last reference in list
    $this->assertCount(7, $main_release->getReleaseResourceReferenceList());
    $this->assertEquals("PrimaryResource", $main_release->getReleaseResourceReferenceList()[0]->getReleaseResourceType());
    $this->assertEquals("A1", $main_release->getReleaseResourceReferenceList()[0]->value());
    $this->assertEquals("SecondaryResource", $main_release->getReleaseResourceReferenceList()[6]->getReleaseResourceType());
    $this->assertEquals("A7", $main_release->getReleaseResourceReferenceList()[6]->value());

    $this->assertCount(1, $main_release->getReleaseType());
    $this->assertEquals("Album", $main_release->getReleaseType()[0]);

    $this->assertCount(1, $main_release->getReleaseDetailsByTerritory());
    /* @var $release_dbt ReleaseDetailsByTerritoryType */
    $release_dbt = $main_release->getReleaseDetailsByTerritory()[0];
    $this->assertCount(1, $release_dbt->getTerritoryCode());
    $this->assertEquals("Worldwide", $release_dbt->getTerritoryCode()[0]);
    $this->assertCount(1, $release_dbt->getDisplayArtistName());
    $this->assertEquals("Monkey Claw", $release_dbt->getDisplayArtistName()[0]);
    $this->assertCount(1, $release_dbt->getLabelName());
    $this->assertEquals("Iron Crown Music", $release_dbt->getLabelName()[0]);
    
    $this->assertCount(2, $release_dbt->getTitle());
    $this->assertEquals("FormalTitle", $release_dbt->getTitle()[0]->getTitleType());  
    $this->assertEquals("A Monkey Claw in a Velvet Glove formal", $release_dbt->getTitle()[0]->getTitleText()->value());  
    $this->assertEquals("DisplayTitle", $release_dbt->getTitle()[1]->getTitleType());  
    $this->assertEquals("A Monkey Claw in a Velvet Glove", $release_dbt->getTitle()[1]->getTitleText()->value());
    
    $this->assertCount(1, $release_dbt->getDisplayArtist());
    $this->assertEquals("1", $release_dbt->getDisplayArtist()[0]->getSequenceNumber());
  }
  
  /**
   * Test that artists with UTF-8 names in it are parsed properly, such 
   * as Zvečansko kolo or Šumadijsko lagano kolo.
   * @see https://github.com/miqwit/dedex/issues/6
   */
  public function testSample016Utf8Artist() {
    $xml_path = "tests/samples/016_utf8_artists.xml";
    $parser_controller = new ErnParserController();
    // Set this to true to see logs from the parser
    $parser_controller->setDisplayLog(false);
    /* @var $ddex NewReleaseMessage */
    $ddex = $parser_controller->parse($xml_path);
    
    
    // In first sound recording, check that display artist is Mirko Kordić
    /* @var $resource_zero \DedexBundle\Entity\Ern382\Ern\SoundRecordingType */
    $resource_zero = $ddex->getResourceList()->getSoundRecording()[0];
    /* @var $srdbt_zero SoundRecordingDetailsByTerritoryType */
    $srdbt_zero = $resource_zero->getSoundRecordingDetailsByTerritory()[0];
    $this->assertEquals("Mirko Kordić", $srdbt_zero->getDisplayArtist()[0]->getPartyName()[0]->getFullName());
    $this->assertEquals("N. Áutor", $srdbt_zero->getResourceContributor()[1]->getPartyName()[0]->getFullName());
    
    // Check that Reference Title of Sound Recording 3 (idx 2) is Zvečansko kolo
    /* @var $resource_two \DedexBundle\Entity\Ern382\SoundRecordingType */
    $resource_two = $ddex->getResourceList()->getSoundRecording()[2];
    $this->assertEquals("Zvečansko kolo", $resource_two->getReferenceTitle()->getTitleText());
    
    // Check that Reference Title of Sound Recording 4 (idx 3) is Šumadijsko lagano kolo
    /* @var $resource_three \DedexBundle\Entity\Ern382\SoundRecordingType */
    $resource_three = $ddex->getResourceList()->getSoundRecording()[3];
    $this->assertEquals("Šumadijsko lagano kolo", $resource_three->getReferenceTitle()->getTitleText());
  }

  /**
   * Test ERN 411 is parsed correctly
   */
  public function testSample015Ern411() {
    $xml_path = "tests/samples/015_ern411.xml";
    $parser_controller = new ErnParserController();
    // Set this to true to see logs from the parser
    $parser_controller->setDisplayLog(false);
    /* @var $ddex NewReleaseMessage */
    $ddex = $parser_controller->parse($xml_path);

    // ERN version is now 411. It is using classes with namespace 411.
    // ERN 411 does not have a getMessageSchemaVersionId() function
    $this->assertEquals('DedexBundle\Entity\Ern411\NewReleaseMessage', get_class($ddex));
  }

  /**
   * Test ERN-Main 32 is parsed correctly
   */
  public function testSample018Ern32() {
    $xml_path = "tests/samples/018_ern32_single.xml";
    $parser_controller = new ErnParserController();
    // Set this to true to see logs from the parser
    $parser_controller->setDisplayLog(false);
    /* @var $ddex Ern32NewReleaseMessage */
    $ddex = $parser_controller->parse($xml_path);

    // ERN-Main 32 should use classes with namespace Ern32
    $this->assertEquals('DedexBundle\Entity\Ern32\NewReleaseMessage', get_class($ddex));

    // ERN attributes
    $this->assertEquals("2010/ern-main/32", $ddex->getMessageSchemaVersionId());
    $this->assertEquals("en", $ddex->getLanguageAndScriptCode());

    // Message header
    $this->assertNotNull($ddex->getMessageHeader());
    $this->assertEquals("Deprecated", $ddex->getMessageHeader()->getMessageThreadId());
    $this->assertEquals("PADPIDA200903020155894108416015858863643750032", $ddex->getMessageHeader()->getMessageId());
    $this->assertEquals("PADPIDA20090302015", $ddex->getMessageHeader()->getMessageSender()->getPartyId()[0]->value());
    $this->assertEquals("Consolidated Independent Ltd", $ddex->getMessageHeader()->getMessageSender()->getPartyName()->getFullName()->value());
    $this->assertEquals("PADPIDA2013020801K", $ddex->getMessageHeader()->getMessageRecipient()[0]->getPartyId()[0]->value());
    $this->assertEquals("Sauce Industries llc. dba Gray V", $ddex->getMessageHeader()->getMessageRecipient()[0]->getPartyName()->getFullName()->value());
    $this->assertEquals("2019-11-08T20:14:13+00:00", $ddex->getMessageHeader()->getMessageCreatedDateTime()->format("Y-m-d\TH:i:sP"));

    // UpdateIndicator
    $this->assertEquals("OriginalMessage", $ddex->getUpdateIndicator());

    // Resources - SoundRecording
    $this->assertNotNull($ddex->getResourceList());
    $this->assertCount(1, $ddex->getResourceList()->getSoundRecording());
    /* @var $sound_recording Ern32SoundRecordingType */
    $sound_recording = $ddex->getResourceList()->getSoundRecording()[0];
    $this->assertEquals("GBCVZ1900196", $sound_recording->getSoundRecordingId()[0]->getIsrc());
    $this->assertEquals("A58863644450088", $sound_recording->getResourceReference());
    $this->assertEquals("Bloodbuzz Ohio", $sound_recording->getReferenceTitle()->getTitleText());
    $this->assertEquals("PT00H04M39S", $sound_recording->getDuration()->format("PT%iH%iM%sS"));

    // SoundRecordingDetailsByTerritory
    $this->assertCount(1, $sound_recording->getSoundRecordingDetailsByTerritory());
    $srdbt = $sound_recording->getSoundRecordingDetailsByTerritory()[0];
    $this->assertEquals("Worldwide", $srdbt->getTerritoryCode()[0]);
    $this->assertEquals("SOAK", $srdbt->getDisplayArtist()[0]->getPartyName()[0]->getFullName()->value());
    $this->assertEquals("MainArtist", $srdbt->getDisplayArtist()[0]->getArtistRole()[0]);

    // Resources - Image
    $this->assertCount(1, $ddex->getResourceList()->getImage());
    $image = $ddex->getResourceList()->getImage()[0];
    $this->assertEquals("FrontCoverImage", $image->getImageType());
    $this->assertEquals("A58863643320054", $image->getResourceReference());

    // Releases
    $this->assertNotNull($ddex->getReleaseList());
    $this->assertCount(2, $ddex->getReleaseList()->getRelease());

    // First release (TrackRelease)
    /* @var $track_release Ern32ReleaseType */
    $track_release = $ddex->getReleaseList()->getRelease()[0];
    $this->assertEquals("GBCVZ1900196", $track_release->getReleaseId()[0]->getIsrc());
    $this->assertEquals("R58863644450088", $track_release->getReleaseReference()[0]);
    $this->assertEquals("TrackRelease", $track_release->getReleaseType());

    // Second release (Single/Album)
    /* @var $album_release Ern32ReleaseType */
    $album_release = $ddex->getReleaseList()->getRelease()[1];
    $this->assertEquals("191402011555", $album_release->getReleaseId()[0]->getIcpn());
    $this->assertEquals("R58863643750032", $album_release->getReleaseReference()[0]);
    $this->assertEquals("Single", $album_release->getReleaseType());
    $this->assertEquals("Bloodbuzz Ohio", $album_release->getReferenceTitle()->getTitleText());

    // DealList
    $this->assertNotNull($ddex->getDealList());
    $this->assertCount(3, $ddex->getDealList()->getReleaseDeal());
    
    // Check first deal
    $deal = $ddex->getDealList()->getReleaseDeal()[0];
    $this->assertEquals("R58863643750032", $deal->getDealReleaseReference());
    $this->assertEquals("Download", $deal->getDeal()->getDealTerms()->getUsage()[0]->getUseType());
    $this->assertGreaterThan(200, count($deal->getDeal()->getDealTerms()->getTerritoryCode())); // Worldwide territories
  }

  /**
   * Test ERN-Main 32 EP with multiple tracks is parsed correctly
   */
  public function testSample019Ern32Ep() {
    $xml_path = "tests/samples/019_ern32_ep.xml";
    $parser_controller = new ErnParserController();
    $parser_controller->setDisplayLog(false);
    /* @var $ddex Ern32NewReleaseMessage */
    $ddex = $parser_controller->parse($xml_path);

    // ERN-Main 32 should use classes with namespace Ern32
    $this->assertEquals('DedexBundle\Entity\Ern32\NewReleaseMessage', get_class($ddex));

    // ERN attributes
    $this->assertEquals("2010/ern-main/32", $ddex->getMessageSchemaVersionId());

    // Resources - should have 3 sound recordings
    $this->assertNotNull($ddex->getResourceList());
    $this->assertCount(3, $ddex->getResourceList()->getSoundRecording());

    // Check first sound recording
    $sound_recording = $ddex->getResourceList()->getSoundRecording()[0];
    $this->assertEquals("GBCVZ1900192", $sound_recording->getSoundRecordingId()[0]->getIsrc());
    $this->assertEquals("John Joe Reilly", $sound_recording->getReferenceTitle()->getTitleText());

    // Check second sound recording
    $sound_recording2 = $ddex->getResourceList()->getSoundRecording()[1];
    $this->assertEquals("GBCVZ1900198", $sound_recording2->getSoundRecordingId()[0]->getIsrc());
    $this->assertEquals("Come Back Paddy Reilly to Ballyjamesduff", $sound_recording2->getReferenceTitle()->getTitleText());

    // Check third sound recording
    $sound_recording3 = $ddex->getResourceList()->getSoundRecording()[2];
    $this->assertEquals("GBCVZ1900193", $sound_recording3->getSoundRecordingId()[0]->getIsrc());
    $this->assertEquals("The Wren, The Wren", $sound_recording3->getReferenceTitle()->getTitleText());

    // Releases - should have 4 releases (3 track releases + 1 album release)
    $this->assertNotNull($ddex->getReleaseList());
    $this->assertCount(4, $ddex->getReleaseList()->getRelease());

    // Check album release (last one)
    $album_release = $ddex->getReleaseList()->getRelease()[3];
    $this->assertEquals("191402800869", $album_release->getReleaseId()[0]->getIcpn());
    $this->assertEquals("The Wren, The Wren", $album_release->getReferenceTitle()->getTitleText());
    $this->assertEquals("Album", $album_release->getReleaseType());
    
    // Album should reference all 3 sound recordings + image
    $this->assertCount(4, $album_release->getReleaseResourceReferenceList());

  }

  /**   
   * Test ERN 411 with incorrectly nested Extent elements in ImageHeight/ImageWidth
   * This tests the fix for handling malformed XML like <ImageHeight><Extent>300</Extent></ImageHeight>
   */
  public function testSample018Ern411NestedExtent() {
    $xml_path = "tests/samples/018_ern411_nested_extent.xml";
    $parser_controller = new ErnParserController();
    $parser_controller->setDisplayLog(false);
    /* @var $ddex \DedexBundle\Entity\Ern411\NewReleaseMessage */
    $ddex = $parser_controller->parse($xml_path);

    // Verify it's ERN 411
    $this->assertEquals('DedexBundle\Entity\Ern411\NewReleaseMessage', get_class($ddex));

    // Get the Image resource
    $this->assertCount(1, $ddex->getResourceList()->getImage());
    $image = $ddex->getResourceList()->getImage()[0];
    $this->assertEquals("IMG1", $image->getResourceReference());

    // Get TechnicalDetails
    $this->assertCount(1, $image->getTechnicalDetails());
    $technicalDetails = $image->getTechnicalDetails()[0];

    // Verify ImageHeight was parsed correctly from nested Extent
    $this->assertNotNull($technicalDetails->getImageHeight());
    $imageHeight = $technicalDetails->getImageHeight();
    $this->assertEquals(300.0, $imageHeight->value());

    // Verify ImageWidth was parsed correctly from nested Extent with UnitOfMeasure
    $this->assertNotNull($technicalDetails->getImageWidth());
    $imageWidth = $technicalDetails->getImageWidth();
    $this->assertEquals(300.0, $imageWidth->value());
    $this->assertEquals("Pixel", $imageWidth->getUnitOfMeasure());
  }
}
