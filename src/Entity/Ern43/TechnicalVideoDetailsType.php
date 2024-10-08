<?php

namespace DedexBundle\Entity\Ern43;

/**
 * Class representing TechnicalVideoDetailsType
 *
 * A Composite containing technical details of a Video.
 * XSD Type: TechnicalVideoDetails
 */
class TechnicalVideoDetailsType
{
    /**
     * The Language and script for the Elements of the TechnicalSoundRecordingDetails as defined in IETF RfC 5646. The default is the same as indicated for the containing composite. Language and Script are provided as lang[-script][-region][-variant]. This is represented in an XML schema as an XML Attribute.
     *
     * @var string $languageAndScriptCode
     */
    private $languageAndScriptCode = null;

    /**
     * A Territory to which the TechnicalVideoDetails apply. The use of ISO TerritoryCodes (or the term 'Worldwide') is strongly encouraged; TIS TerritoryCodes should only be used if both MessageSender and MessageRecipient are familiar with this standard.
     *
     * @var string $applicableTerritoryCode
     */
    private $applicableTerritoryCode = null;

    /**
     * The Flag indicating whether this Composite provides default values (=true). This Flag should not be set if this is not the case. This is represented in an XML schema as an XML Attribute.
     *
     * @var bool $isDefault
     */
    private $isDefault = null;

    /**
     * The Identifier (specific to the Message) of the TechnicalVideoDetails within the Release which contains it. This is a LocalTechnicalResourceDetailsAnchor starting with the letter T.
     *
     * @var string $technicalResourceDetailsReference
     */
    private $technicalResourceDetailsReference = null;

    /**
     * A Composite containing the overall BitRate and a UnitOfMeasure (the default is kbps).
     *
     * @var \DedexBundle\Entity\Ern43\BitRateType $overallBitRate
     */
    private $overallBitRate = null;

    /**
     * A Composite containing details of a delivery File.
     *
     * @var \DedexBundle\Entity\Ern43\VideoDeliveryFileType[] $deliveryFile
     */
    private $deliveryFile = [
        
    ];

    /**
     * The Flag indicating whether the Video is technically a clip of the parent Resource (=true) or not (=false). If the Flag is set to true, the Video described is a clip and the ClipDetails describe how the clip is generated from the full recording described in another TechnicalVideoDetails composite. If the Flag is set to false (or left out), the Video described is a 'full' recording and any ClipDetails describe how a clip is generated from said full recording. Note that nothing can be implied from this element as to the conditions under which the clip can be made available.
     *
     * @var bool $isClip
     */
    private $isClip = null;

    /**
     * A Composite containing details of a preview.
     *
     * @var \DedexBundle\Entity\Ern43\VideoClipDetailsType[] $clipDetails
     */
    private $clipDetails = [
        
    ];

    /**
     * Gets as languageAndScriptCode
     *
     * The Language and script for the Elements of the TechnicalSoundRecordingDetails as defined in IETF RfC 5646. The default is the same as indicated for the containing composite. Language and Script are provided as lang[-script][-region][-variant]. This is represented in an XML schema as an XML Attribute.
     *
     * @return string
     */
    public function getLanguageAndScriptCode()
    {
        return $this->languageAndScriptCode;
    }

    /**
     * Sets a new languageAndScriptCode
     *
     * The Language and script for the Elements of the TechnicalSoundRecordingDetails as defined in IETF RfC 5646. The default is the same as indicated for the containing composite. Language and Script are provided as lang[-script][-region][-variant]. This is represented in an XML schema as an XML Attribute.
     *
     * @param string $languageAndScriptCode
     * @return self
     */
    public function setLanguageAndScriptCode($languageAndScriptCode)
    {
        $this->languageAndScriptCode = $languageAndScriptCode;
        return $this;
    }

    /**
     * Gets as applicableTerritoryCode
     *
     * A Territory to which the TechnicalVideoDetails apply. The use of ISO TerritoryCodes (or the term 'Worldwide') is strongly encouraged; TIS TerritoryCodes should only be used if both MessageSender and MessageRecipient are familiar with this standard.
     *
     * @return string
     */
    public function getApplicableTerritoryCode()
    {
        return $this->applicableTerritoryCode;
    }

    /**
     * Sets a new applicableTerritoryCode
     *
     * A Territory to which the TechnicalVideoDetails apply. The use of ISO TerritoryCodes (or the term 'Worldwide') is strongly encouraged; TIS TerritoryCodes should only be used if both MessageSender and MessageRecipient are familiar with this standard.
     *
     * @param string $applicableTerritoryCode
     * @return self
     */
    public function setApplicableTerritoryCode($applicableTerritoryCode)
    {
        $this->applicableTerritoryCode = $applicableTerritoryCode;
        return $this;
    }

    /**
     * Gets as isDefault
     *
     * The Flag indicating whether this Composite provides default values (=true). This Flag should not be set if this is not the case. This is represented in an XML schema as an XML Attribute.
     *
     * @return bool
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * Sets a new isDefault
     *
     * The Flag indicating whether this Composite provides default values (=true). This Flag should not be set if this is not the case. This is represented in an XML schema as an XML Attribute.
     *
     * @param bool $isDefault
     * @return self
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    /**
     * Gets as technicalResourceDetailsReference
     *
     * The Identifier (specific to the Message) of the TechnicalVideoDetails within the Release which contains it. This is a LocalTechnicalResourceDetailsAnchor starting with the letter T.
     *
     * @return string
     */
    public function getTechnicalResourceDetailsReference()
    {
        return $this->technicalResourceDetailsReference;
    }

    /**
     * Sets a new technicalResourceDetailsReference
     *
     * The Identifier (specific to the Message) of the TechnicalVideoDetails within the Release which contains it. This is a LocalTechnicalResourceDetailsAnchor starting with the letter T.
     *
     * @param string $technicalResourceDetailsReference
     * @return self
     */
    public function setTechnicalResourceDetailsReference($technicalResourceDetailsReference)
    {
        $this->technicalResourceDetailsReference = $technicalResourceDetailsReference;
        return $this;
    }

    /**
     * Gets as overallBitRate
     *
     * A Composite containing the overall BitRate and a UnitOfMeasure (the default is kbps).
     *
     * @return \DedexBundle\Entity\Ern43\BitRateType
     */
    public function getOverallBitRate()
    {
        return $this->overallBitRate;
    }

    /**
     * Sets a new overallBitRate
     *
     * A Composite containing the overall BitRate and a UnitOfMeasure (the default is kbps).
     *
     * @param \DedexBundle\Entity\Ern43\BitRateType $overallBitRate
     * @return self
     */
    public function setOverallBitRate(?\DedexBundle\Entity\Ern43\BitRateType $overallBitRate = null)
    {
        $this->overallBitRate = $overallBitRate;
        return $this;
    }

    /**
     * Adds as deliveryFile
     *
     * A Composite containing details of a delivery File.
     *
     * @return self
     * @param \DedexBundle\Entity\Ern43\VideoDeliveryFileType $deliveryFile
     */
    public function addToDeliveryFile(\DedexBundle\Entity\Ern43\VideoDeliveryFileType $deliveryFile)
    {
        $this->deliveryFile[] = $deliveryFile;
        return $this;
    }

    /**
     * isset deliveryFile
     *
     * A Composite containing details of a delivery File.
     *
     * @param int|string $index
     * @return bool
     */
    public function issetDeliveryFile($index)
    {
        return isset($this->deliveryFile[$index]);
    }

    /**
     * unset deliveryFile
     *
     * A Composite containing details of a delivery File.
     *
     * @param int|string $index
     * @return void
     */
    public function unsetDeliveryFile($index)
    {
        unset($this->deliveryFile[$index]);
    }

    /**
     * Gets as deliveryFile
     *
     * A Composite containing details of a delivery File.
     *
     * @return \DedexBundle\Entity\Ern43\VideoDeliveryFileType[]
     */
    public function getDeliveryFile()
    {
        return $this->deliveryFile;
    }

    /**
     * Sets a new deliveryFile
     *
     * A Composite containing details of a delivery File.
     *
     * @param \DedexBundle\Entity\Ern43\VideoDeliveryFileType[] $deliveryFile
     * @return self
     */
    public function setDeliveryFile(array $deliveryFile = null)
    {
        $this->deliveryFile = $deliveryFile;
        return $this;
    }

    /**
     * Gets as isClip
     *
     * The Flag indicating whether the Video is technically a clip of the parent Resource (=true) or not (=false). If the Flag is set to true, the Video described is a clip and the ClipDetails describe how the clip is generated from the full recording described in another TechnicalVideoDetails composite. If the Flag is set to false (or left out), the Video described is a 'full' recording and any ClipDetails describe how a clip is generated from said full recording. Note that nothing can be implied from this element as to the conditions under which the clip can be made available.
     *
     * @return bool
     */
    public function getIsClip()
    {
        return $this->isClip;
    }

    /**
     * Sets a new isClip
     *
     * The Flag indicating whether the Video is technically a clip of the parent Resource (=true) or not (=false). If the Flag is set to true, the Video described is a clip and the ClipDetails describe how the clip is generated from the full recording described in another TechnicalVideoDetails composite. If the Flag is set to false (or left out), the Video described is a 'full' recording and any ClipDetails describe how a clip is generated from said full recording. Note that nothing can be implied from this element as to the conditions under which the clip can be made available.
     *
     * @param bool $isClip
     * @return self
     */
    public function setIsClip($isClip)
    {
        $this->isClip = $isClip;
        return $this;
    }

    /**
     * Adds as clipDetails
     *
     * A Composite containing details of a preview.
     *
     * @return self
     * @param \DedexBundle\Entity\Ern43\VideoClipDetailsType $clipDetails
     */
    public function addToClipDetails(\DedexBundle\Entity\Ern43\VideoClipDetailsType $clipDetails)
    {
        $this->clipDetails[] = $clipDetails;
        return $this;
    }

    /**
     * isset clipDetails
     *
     * A Composite containing details of a preview.
     *
     * @param int|string $index
     * @return bool
     */
    public function issetClipDetails($index)
    {
        return isset($this->clipDetails[$index]);
    }

    /**
     * unset clipDetails
     *
     * A Composite containing details of a preview.
     *
     * @param int|string $index
     * @return void
     */
    public function unsetClipDetails($index)
    {
        unset($this->clipDetails[$index]);
    }

    /**
     * Gets as clipDetails
     *
     * A Composite containing details of a preview.
     *
     * @return \DedexBundle\Entity\Ern43\VideoClipDetailsType[]
     */
    public function getClipDetails()
    {
        return $this->clipDetails;
    }

    /**
     * Sets a new clipDetails
     *
     * A Composite containing details of a preview.
     *
     * @param \DedexBundle\Entity\Ern43\VideoClipDetailsType[] $clipDetails
     * @return self
     */
    public function setClipDetails(array $clipDetails = null)
    {
        $this->clipDetails = $clipDetails;
        return $this;
    }
}

