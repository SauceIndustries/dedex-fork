<?php

namespace DedexBundle\Entity\Ern32;

/**
 * Class representing SocietyAffiliationType
 *
 * A Composite containing details of a society
 *  affiliation.
 * XSD Type: SocietyAffiliation
 */
class SocietyAffiliationType
{

    /**
     * A Territory to which the affiliation
     *  details apply. Either this Element or ExcludedTerritory shall be present, but
     *  not both. The use of ISO TerritoryCodes (or the term 'Worldwide”) is strongly
     *  encouraged; TIS TerritoryCodes should only be used if both MessageSender and
     *  MessageRecipient are familiar with this standard.
     *
     * @var \DedexBundle\Entity\Ern32\CurrentTerritoryCodeType[] $territoryCode
     */
    private $territoryCode = [
        
    ];

    /**
     * A Territory to which the affiliation
     *  details do not apply. Either this Element or Territory shall be present, but
     *  not both. The use of ISO TerritoryCodes (or the term 'Worldwide”) is strongly
     *  encouraged; TIS TerritoryCodes should only be used if both MessageSender and
     *  MessageRecipient are familiar with this standard.
     *
     * @var \DedexBundle\Entity\Ern32\CurrentTerritoryCodeType[] $excludedTerritoryCode
     */
    private $excludedTerritoryCode = [
        
    ];

    /**
     * A Composite containing details of a
     *  MusicRightsSociety.
     *
     * @var \DedexBundle\Entity\Ern32\PartyDescriptorType $musicRightsSociety
     */
    private $musicRightsSociety = null;

    /**
     * Adds as territoryCode
     *
     * A Territory to which the affiliation
     *  details apply. Either this Element or ExcludedTerritory shall be present, but
     *  not both. The use of ISO TerritoryCodes (or the term 'Worldwide”) is strongly
     *  encouraged; TIS TerritoryCodes should only be used if both MessageSender and
     *  MessageRecipient are familiar with this standard.
     *
     * @return self
     * @param \DedexBundle\Entity\Ern32\CurrentTerritoryCodeType $territoryCode
     */
    public function addToTerritoryCode(\DedexBundle\Entity\Ern32\CurrentTerritoryCodeType $territoryCode)
    {
        $this->territoryCode[] = $territoryCode;
        return $this;
    }

    /**
     * isset territoryCode
     *
     * A Territory to which the affiliation
     *  details apply. Either this Element or ExcludedTerritory shall be present, but
     *  not both. The use of ISO TerritoryCodes (or the term 'Worldwide”) is strongly
     *  encouraged; TIS TerritoryCodes should only be used if both MessageSender and
     *  MessageRecipient are familiar with this standard.
     *
     * @param int|string $index
     * @return bool
     */
    public function issetTerritoryCode($index)
    {
        return isset($this->territoryCode[$index]);
    }

    /**
     * unset territoryCode
     *
     * A Territory to which the affiliation
     *  details apply. Either this Element or ExcludedTerritory shall be present, but
     *  not both. The use of ISO TerritoryCodes (or the term 'Worldwide”) is strongly
     *  encouraged; TIS TerritoryCodes should only be used if both MessageSender and
     *  MessageRecipient are familiar with this standard.
     *
     * @param int|string $index
     * @return void
     */
    public function unsetTerritoryCode($index)
    {
        unset($this->territoryCode[$index]);
    }

    /**
     * Gets as territoryCode
     *
     * A Territory to which the affiliation
     *  details apply. Either this Element or ExcludedTerritory shall be present, but
     *  not both. The use of ISO TerritoryCodes (or the term 'Worldwide”) is strongly
     *  encouraged; TIS TerritoryCodes should only be used if both MessageSender and
     *  MessageRecipient are familiar with this standard.
     *
     * @return \DedexBundle\Entity\Ern32\CurrentTerritoryCodeType[]
     */
    public function getTerritoryCode()
    {
        return $this->territoryCode;
    }

    /**
     * Sets a new territoryCode
     *
     * A Territory to which the affiliation
     *  details apply. Either this Element or ExcludedTerritory shall be present, but
     *  not both. The use of ISO TerritoryCodes (or the term 'Worldwide”) is strongly
     *  encouraged; TIS TerritoryCodes should only be used if both MessageSender and
     *  MessageRecipient are familiar with this standard.
     *
     * @param \DedexBundle\Entity\Ern32\CurrentTerritoryCodeType[] $territoryCode
     * @return self
     */
    public function setTerritoryCode(array $territoryCode)
    {
        $this->territoryCode = $territoryCode;
        return $this;
    }

    /**
     * Adds as excludedTerritoryCode
     *
     * A Territory to which the affiliation
     *  details do not apply. Either this Element or Territory shall be present, but
     *  not both. The use of ISO TerritoryCodes (or the term 'Worldwide”) is strongly
     *  encouraged; TIS TerritoryCodes should only be used if both MessageSender and
     *  MessageRecipient are familiar with this standard.
     *
     * @return self
     * @param \DedexBundle\Entity\Ern32\CurrentTerritoryCodeType $excludedTerritoryCode
     */
    public function addToExcludedTerritoryCode(\DedexBundle\Entity\Ern32\CurrentTerritoryCodeType $excludedTerritoryCode)
    {
        $this->excludedTerritoryCode[] = $excludedTerritoryCode;
        return $this;
    }

    /**
     * isset excludedTerritoryCode
     *
     * A Territory to which the affiliation
     *  details do not apply. Either this Element or Territory shall be present, but
     *  not both. The use of ISO TerritoryCodes (or the term 'Worldwide”) is strongly
     *  encouraged; TIS TerritoryCodes should only be used if both MessageSender and
     *  MessageRecipient are familiar with this standard.
     *
     * @param int|string $index
     * @return bool
     */
    public function issetExcludedTerritoryCode($index)
    {
        return isset($this->excludedTerritoryCode[$index]);
    }

    /**
     * unset excludedTerritoryCode
     *
     * A Territory to which the affiliation
     *  details do not apply. Either this Element or Territory shall be present, but
     *  not both. The use of ISO TerritoryCodes (or the term 'Worldwide”) is strongly
     *  encouraged; TIS TerritoryCodes should only be used if both MessageSender and
     *  MessageRecipient are familiar with this standard.
     *
     * @param int|string $index
     * @return void
     */
    public function unsetExcludedTerritoryCode($index)
    {
        unset($this->excludedTerritoryCode[$index]);
    }

    /**
     * Gets as excludedTerritoryCode
     *
     * A Territory to which the affiliation
     *  details do not apply. Either this Element or Territory shall be present, but
     *  not both. The use of ISO TerritoryCodes (or the term 'Worldwide”) is strongly
     *  encouraged; TIS TerritoryCodes should only be used if both MessageSender and
     *  MessageRecipient are familiar with this standard.
     *
     * @return \DedexBundle\Entity\Ern32\CurrentTerritoryCodeType[]
     */
    public function getExcludedTerritoryCode()
    {
        return $this->excludedTerritoryCode;
    }

    /**
     * Sets a new excludedTerritoryCode
     *
     * A Territory to which the affiliation
     *  details do not apply. Either this Element or Territory shall be present, but
     *  not both. The use of ISO TerritoryCodes (or the term 'Worldwide”) is strongly
     *  encouraged; TIS TerritoryCodes should only be used if both MessageSender and
     *  MessageRecipient are familiar with this standard.
     *
     * @param \DedexBundle\Entity\Ern32\CurrentTerritoryCodeType[] $excludedTerritoryCode
     * @return self
     */
    public function setExcludedTerritoryCode(array $excludedTerritoryCode)
    {
        $this->excludedTerritoryCode = $excludedTerritoryCode;
        return $this;
    }

    /**
     * Gets as musicRightsSociety
     *
     * A Composite containing details of a
     *  MusicRightsSociety.
     *
     * @return \DedexBundle\Entity\Ern32\PartyDescriptorType
     */
    public function getMusicRightsSociety()
    {
        return $this->musicRightsSociety;
    }

    /**
     * Sets a new musicRightsSociety
     *
     * A Composite containing details of a
     *  MusicRightsSociety.
     *
     * @param \DedexBundle\Entity\Ern32\PartyDescriptorType $musicRightsSociety
     * @return self
     */
    public function setMusicRightsSociety(\DedexBundle\Entity\Ern32\PartyDescriptorType $musicRightsSociety)
    {
        $this->musicRightsSociety = $musicRightsSociety;
        return $this;
    }


}

